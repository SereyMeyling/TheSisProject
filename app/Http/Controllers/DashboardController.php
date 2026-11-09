<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Patient;
use App\Models\User;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Room;
use App\Models\Expense;
use App\Models\Admission;
use App\Models\Pharmacy\MedicineBatch;
use App\Models\Pharmacy\Medicine;
use App\Models\Pharmacy\Sale;
use App\Models\Setting\BackupLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {

        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        if ($user->hasRole('doctor')) {
            return $this->doctorDashboard();
        }

        if ($user->hasRole('pharmacist')) {
            return $this->pharmacistDashboard();
        }

        if ($user->hasRole('cashier')) {
            return $this->cashierDashboard();
        }

        if ($user->hasRole('nurse')) {
            return $this->nurseDashboard();
        }

        return view('form.home.home');
    }

    private function adminDashboard()
    {
        $totalPatients = Patient::count();
        $totalUsers = User::count();
        $totalusers = User::count();
        $totalMedicines = Medicine::count();

        $todayInvoiceRev = InvoicePayment::whereDate('paid_at', today())->sum('amount');
        $todaySaleRev = Sale::whereDate('created_at', today())->sum('total_amount');
        $todayRevenue = $todayInvoiceRev + $todaySaleRev;

        $totalInvoiceRev = InvoicePayment::sum('amount');
        $totalSaleRev = Sale::sum('total_amount');
        $totalRevenue = $totalInvoiceRev + $totalSaleRev;

        $backupLogs = class_exists(BackupLog::class) ? BackupLog::latest()->take(5)->get() : collect();
        $recentUsers = User::latest()->take(5)->get();

        // --- vars the dashboard.blade.php view also needs ---

        $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
        $emergencyCases = Appointment::whereDate('appointment_date', today())
            ->where('is_emergency', true)
            ->count();

        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $occupancyPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
        // $activePatientsTotal = Admission::where('status', 'admitted')->distinct('patient_id')->count('patient_id');

        $deptNames = Department::pluck('department_name', 'department_id');

        $admittedByDept = Admission::where('status', 'admitted')
            ->selectRaw('department_id, COUNT(DISTINCT patient_id) as total')
            ->groupBy('department_id')
            ->get();

        $admittedTotal = $admittedByDept->sum('total');

        $departmentBreakdown = $admittedByDept->map(fn($row) => [
            'name' => $row->department_id ? ($deptNames[$row->department_id] ?? 'មិនបានកំណត់') : 'មិនបានកំណត់',
            'total' => (int) $row->total,
            'percent' => $admittedTotal > 0 ? round($row->total / $admittedTotal * 100) : 0,
        ])->sortByDesc('total')->values()->toArray();

        // Last 6 months income/expense chart data
        $finance = [
            'yearly' => $this->monthlyFinance(now()->startOfYear(), now()->endOfYear()),
            'monthly' => $this->monthlyFinance(now()->startOfMonth()->subMonths(5), now()->endOfMonth()),
        ];

        $patientSeries = [
            'weekly' => $this->weeklyNewPatients(8),
            'monthly' => $this->monthlyNewPatients(now()->startOfYear(), now()->endOfYear()),
        ];


        // ---------- Admin dashboard phase 1 ----------
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        // Pending = unpaid + partial invoices (Excel "Pending")
        $unpaidCount = Invoice::whereIn('status', ['unpaid', 'partial'])->count();
        $unpaidBalance = Invoice::whereIn('status', ['unpaid', 'partial'])->sum('balance');

        // Pharmacy alerts (same low-stock rule as the pharmacist dashboard)
        $lowStockAll = Medicine::where('is_active', true)
            ->select('medicines.medicine_id', 'medicines.medicine_name', 'medicines.reorder_level')
            ->selectSub(
                MedicineBatch::selectRaw('COALESCE(SUM(remaining_quantity), 0)')
                    ->whereColumn('medicine_batches.medicine_id', 'medicines.medicine_id'),
                'stock_total'
            )
            ->havingRaw('stock_total <= COALESCE(medicines.reorder_level, 20)')
            ->orderBy('stock_total', 'asc')
            ->get();
        $lowStockCount = $lowStockAll->count();
        $lowStockList = $lowStockAll->take(5);

        $expiringAll = MedicineBatch::with('medicine')->expiringSoon(30)->get();
        $expiringCount = $expiringAll->count();
        $expiringList = $expiringAll->take(5);

        // This month: income, expense, payment methods
        $monthIncome = InvoicePayment::whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount')
            + Sale::whereBetween('created_at', [$monthStart, $monthEnd])->sum('total_amount');

        $monthExpense = (float) MedicineBatch::whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('COALESCE(SUM(purchase_price * quantity_initial), 0) as t')->value('t')
            + (float) Expense::whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum('amount');

        $paymentMix = InvoicePayment::whereBetween('paid_at', [$monthStart, $monthEnd])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method')
            ->map(fn($v) => round((float) $v, 2))
            ->toArray();

        // Disease + visit-type statistics for the selected month (?month=YYYY-MM)
        $month = $this->resolveMonth(request('month'));
        $selectedMonth = $month->format('Y-m');
        $diseaseStats = $this->diseaseStats($month);
        $visitTypeStats = $this->visitTypeStats($month);

        // Today's patients table
        $todayVisits = $this->todayVisits(15);


        return view('form.dashboard.dashboard', compact(
            'totalPatients',
            'totalUsers',
            'totalusers',
            'totalMedicines',
            'todayRevenue',
            'totalRevenue',
            'backupLogs',
            'recentUsers',
            'todayAppointments',
            'emergencyCases',
            'totalRooms',
            'availableRooms',
            'occupancyPercent',
            'departmentBreakdown',
            'finance',
            'patientSeries',
            'unpaidCount',
            'unpaidBalance',
            'lowStockCount',
            'lowStockList',
            'expiringCount',
            'expiringList',
            'monthIncome',
            'monthExpense',
            'paymentMix',
            'selectedMonth',
            'diseaseStats',
            'visitTypeStats',
            'todayVisits'
        ));
    }

    private function doctorDashboard()
    {
        $todayAppointments = Appointment::with('patient')
            ->whereDate('appointment_date', today())
            ->latest()
            ->get();

        $todayAppointmentsCount = $todayAppointments->count();

        $pendingAppointments = Appointment::with('patient')
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        $pendingLabOrders = LabOrder::with('medicalRecord.patient')
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        $recentMedicalRecords = MedicalRecord::with('patient')
            ->latest()
            ->take(5)
            ->get();

        return view('form.dashboard.doctor', compact(
            'todayAppointments',
            'todayAppointmentsCount',
            'pendingAppointments',
            'pendingLabOrders',
            'recentMedicalRecords'
        ));
    }

    private function pharmacistDashboard()
    {
        $lowStockMedicines = Medicine::where('is_active', true)
            ->select(
                'medicines.medicine_id',
                'medicines.medicine_name',
                'medicines.reorder_level'
            )
            ->selectSub(
                MedicineBatch::selectRaw('COALESCE(SUM(remaining_quantity), 0)')
                    ->whereColumn(
                        'medicine_batches.medicine_id',
                        'medicines.medicine_id'
                    ),
                'stock_total'
            )
            ->havingRaw(
                'stock_total <= COALESCE(medicines.reorder_level, 20)'
            )
            ->orderBy('stock_total', 'asc')
            ->take(10)
            ->get();

        $expiringBatches = MedicineBatch::with('medicine')
            ->expiringSoon(30)
            ->take(10)
            ->get();

        $pendingPrescriptions = Prescription::with('medicalRecord.patient')
            ->latest()
            ->take(10)
            ->get();

        $todaySalesTotal = Sale::whereDate('created_at', today())->sum('total_amount');
        $todaySalesCount = Sale::whereDate('created_at', today())->count();
        $recentSales = Sale::latest()->take(5)->get();

        return view('form.dashboard.pharmacy', compact(
            'lowStockMedicines',
            'expiringBatches',
            'pendingPrescriptions',
            'todaySalesTotal',
            'todaySalesCount',
            'recentSales'
        ));
    }

    private function cashierDashboard()
    {
        $unpaidInvoicesCount = Invoice::where('status', 'unpaid')->count();
        $unpaidInvoicesTotal = Invoice::where('status', 'unpaid')->sum('total_amount');

        $todayCashRevenue = InvoicePayment::whereDate('paid_at', today())
            ->where('payment_method', 'cash')
            ->sum('amount');

        $todayKhqrRevenue = InvoicePayment::whereDate('paid_at', today())
            ->where('payment_method', 'khqr')
            ->sum('amount');

        $todayTotalRevenue = InvoicePayment::whereDate('paid_at', today())->sum('amount');

        $recentPayments = InvoicePayment::with(['invoice.patient', 'processor'])
            ->latest()
            ->take(10)
            ->get();

        $unpaidInvoicesList = Invoice::with('patient')
            ->where('status', 'unpaid')
            ->latest()
            ->take(5)
            ->get();

        return view('form.dashboard.cashier', compact(
            'unpaidInvoicesCount',
            'unpaidInvoicesTotal',
            'todayCashRevenue',
            'todayKhqrRevenue',
            'todayTotalRevenue',
            'recentPayments',
            'unpaidInvoicesList'
        ));
    }

    private function nurseDashboard()
    {
        $totalRooms = Room::count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $availableRooms = Room::where('status', 'available')->count();

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $activeAdmissions = Admission::with(['patient', 'room'])
            ->where('status', 'admitted')
            ->latest()
            ->take(10)
            ->get();

        $recentPatients = Patient::latest()->take(5)->get();

        return view('form.dashboard.nurse', compact(
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'occupancyRate',
            'activeAdmissions',
            'recentPatients'
        ));
    }


    private function monthlyFinance(Carbon $from, Carbon $to): array
    {
        // 1. Income from invoice payments
        $income = InvoicePayment::selectRaw("
            DATE_FORMAT(paid_at, '%Y-%m') as ym,
            SUM(amount) as total
        ")
            ->whereBetween('paid_at', [$from, $to])
            ->groupBy('ym')
            ->pluck('total', 'ym');

        // 2. Income from sales
        $sales = Sale::selectRaw("
            DATE_FORMAT(created_at, '%Y-%m') as ym,
            SUM(total_amount) as total
        ")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('ym')
            ->pluck('total', 'ym');

        // 3. Medicine purchase expenses
        $expense = MedicineBatch::selectRaw("
            DATE_FORMAT(created_at, '%Y-%m') as ym,
            SUM(purchase_price * quantity_initial) as total
        ")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('ym')
            ->pluck('total', 'ym');

        // 4. Other expenses (doctor fee, services, supplies, etc.)
        $otherExpense = Expense::selectRaw("
            DATE_FORMAT(expense_date, '%Y-%m') as ym,
            SUM(amount) as total
        ")
            ->whereBetween('expense_date', [
                $from->toDateString(),
                $to->toDateString()
            ])
            ->groupBy('ym')
            ->pluck('total', 'ym');

        // 5. Prepare monthly chart data
        $labels = $inc = $exp = [];

        for ($d = $from->copy()->startOfMonth(); $d->lte($to); $d->addMonth()) {

            $key = $d->format('Y-m');

            $labels[] = $d->format('M');

            // Total income = invoice payments + sales
            $inc[] = round(
                ($income[$key] ?? 0) + ($sales[$key] ?? 0),
                2
            );

            // Total expenses = medicine purchases + other expenses
            $exp[] = round(
                ($expense[$key] ?? 0) + ($otherExpense[$key] ?? 0),
                2
            );
        }

        return [
            'labels' => $labels,
            'income' => $inc,
            'expense' => $exp
        ];
    }


    private function weeklyNewPatients(int $weeks): array
    {
        $from = now()->startOfWeek()->subWeeks($weeks - 1);
        $to = now()->endOfWeek();

        $counts = Patient::selectRaw("DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY) as week_start, COUNT(*) as total")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('week_start')->pluck('total', 'week_start');

        $labels = $data = [];
        for ($d = $from->copy(); $d->lte($to); $d->addWeek()) {
            $labels[] = $d->format('d/m') . '–' . $d->copy()->endOfWeek()->format('d/m');
            $data[] = (int) ($counts[$d->format('Y-m-d')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    private function monthlyNewPatients(Carbon $from, Carbon $to): array
    {
        $counts = Patient::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('ym')->pluck('total', 'ym');

        $labels = $data = [];
        for ($d = $from->copy()->startOfMonth(); $d->lte($to); $d->addMonth()) {
            $labels[] = $d->format('M');
            $data[] = (int) ($counts[$d->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }


    private function resolveMonth(?string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('!Y-m', $value ?: now()->format('Y-m'))->startOfMonth();
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }
    private function diseaseStats(Carbon $month): array
    {
        $rows = MedicalRecord::join('patients', 'patients.patient_id', '=', 'medical_records.patient_id')
            ->whereBetween('medical_records.visit_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw("COALESCE(NULLIF(medical_records.diagnosis_group, ''), 'OTHER') as grp, patients.sex, COUNT(*) as total")
            ->groupBy('grp', 'patients.sex')
            ->get();

        $groups = MedicalRecord::DIAGNOSIS_GROUPS;
        $male = $female = array_fill_keys(array_keys($groups), 0);

        foreach ($rows as $r) {
            $key = isset($groups[$r->grp]) ? $r->grp : 'OTHER';
            if ($r->sex === 'male') {
                $male[$key] += (int) $r->total;
            } elseif ($r->sex === 'female') {
                $female[$key] += (int) $r->total;
            }
        }

        return [
            'labels' => array_values($groups),
            'male' => array_values($male),
            'female' => array_values($female),
            'maleTotal' => array_sum($male),
            'femaleTotal' => array_sum($female),
        ];
    }

    private function visitTypeStats(Carbon $month): array
    {
        $counts = MedicalRecord::whereBetween('visit_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw("COALESCE(NULLIF(visit_type, ''), 'unset') as vt, COUNT(*) as total")
            ->groupBy('vt')
            ->pluck('total', 'vt');

        $types = MedicalRecord::VISIT_TYPES + ['unset' => 'មិនបានកំណត់'];
        $labels = $data = [];
        foreach ($types as $key => $label) {
            $labels[] = $label;
            $data[] = (int) ($counts[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    // Today's visits + what each patient paid today + what they still owe
    private function todayVisits(int $limit = 15): array
    {
        $visits = MedicalRecord::with('patient')
            ->whereDate('visit_date', today())
            ->latest('visit_date')
            ->take($limit)
            ->get();

        $total = MedicalRecord::whereDate('visit_date', today())->count();
        $ids = $visits->pluck('patient_id')->unique()->all();

        $paid = InvoicePayment::join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            ->whereDate('invoice_payments.paid_at', today())
            ->whereIn('invoices.patient_id', $ids)
            ->selectRaw('invoices.patient_id, SUM(invoice_payments.amount) as total, GROUP_CONCAT(DISTINCT invoice_payments.payment_method) as methods')
            ->groupBy('invoices.patient_id')
            ->get()
            ->keyBy('patient_id');

        $owing = Invoice::whereIn('status', ['unpaid', 'partial'])
            ->whereIn('patient_id', $ids)
            ->selectRaw('patient_id, SUM(balance) as balance')
            ->groupBy('patient_id')
            ->pluck('balance', 'patient_id');

        return compact('visits', 'total', 'paid', 'owing');
    }

}
