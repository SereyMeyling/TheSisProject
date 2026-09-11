<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Patient;
use App\Models\Employee;
use App\Models\User;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\LabOrder;
use App\Models\Prescription;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Room;
use App\Models\Admission;
use App\Models\Pharmacy\MedicineBatch;
use App\Models\Pharmacy\Medicine;
use App\Models\Pharmacy\Sale;
use App\Models\Setting\BackupLog;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
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
        $totalEmployees = Employee::count();
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
        $emergencyCases = 0;

        $totalRooms = Room::count();
        $availableRooms = Room::where('status', 'available')->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();
        $occupancyPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;
        $activePatientsTotal = Admission::where('status', 'admitted')->distinct('patient_id')->count('patient_id');

        $departmentBreakdown = Department::withCount('employees')->get()->map(function ($dept) use ($activePatientsTotal) {
            $count = $dept->employees_count;
            return [
                'name'    => $dept->department_name,
                'total'   => $count,
                'percent' => $activePatientsTotal > 0 ? round(($count / $activePatientsTotal) * 100) : 0,
            ];
        })->filter(fn($d) => $d['total'] > 0)->values()->toArray();

        // Last 6 months income/expense chart data
        $months = [];
        $incomeByMonth = [];
        $expenseByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M');

            $incomeByMonth[] = InvoicePayment::whereYear('paid_at', $date->year)
                ->whereMonth('paid_at', $date->month)
                ->sum('amount')
                + Sale::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_amount');

           
            $expenseByMonth[] = 0;
        }

        // Last 4 weeks of admissions
        $weeklyAdmissions = [];
        for ($i = 3; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();
            $weeklyAdmissions[] = Admission::whereBetween('created_at', [$start, $end])->count();
        }

        return view('form.dashboard.dashboard', compact(
            'totalPatients',
            'totalUsers',
            'totalEmployees',
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
            'months',
            'incomeByMonth',
            'expenseByMonth',
            'weeklyAdmissions'
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
        $lowStockBatches = MedicineBatch::with('medicine')
            ->where('remaining_quantity', '<=', 10)
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
            'lowStockBatches',
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
}
