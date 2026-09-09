<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $today         = Carbon::today();
        $startOfMonth  = Carbon::now()->startOfMonth();
        $startOfLastMo = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMo   = Carbon::now()->subMonth()->endOfMonth();

        // ---- Patients ----
        $totalPatients      = DB::table('patients')->count();
        $patientsBeforeThisMo = DB::table('patients')->where('created_at', '<', $startOfMonth)->count();
        $newPatientsThisMonth = DB::table('patients')->where('created_at', '>=', $startOfMonth)->count();
        $newPatientsLastMonth  = DB::table('patients')
            ->whereBetween('created_at', [$startOfLastMo, $endOfLastMo])->count();

        // ---- Medicines (count only, no trend logic — stock isn't "growth") ----
        $totalMedicines = Schema::hasTable('medicines') ? DB::table('medicines')->count() : 0;

        // ---- Income: billings + payments ----
        $dailyIncome     = DB::table('payments')->where('status', 'success')->whereDate('payment_date', $today)->sum('amount');
        $yesterdayIncome = DB::table('payments')->where('status', 'success')->whereDate('payment_date', $today->copy()->subDay())->sum('amount');

        // ---- Expense ----
        $dailyExpense     = Schema::hasTable('expenses') ? DB::table('expenses')->whereDate('expense_date', $today)->sum('amount') : 0;
        $yesterdayExpense = Schema::hasTable('expenses') ? DB::table('expenses')->whereDate('expense_date', $today->copy()->subDay())->sum('amount') : 0;

        $dailyProfit     = $dailyIncome - $dailyExpense;
        $yesterdayProfit = $yesterdayIncome - $yesterdayExpense;

        // ---- Room occupancy ----
        $totalRooms      = DB::table('rooms')->count();
        $occupiedRooms   = DB::table('rooms')->where('status', 'occupied')->count();
        $availableRooms  = DB::table('rooms')->where('status', 'available')->count();
        $occupancyPercent = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        // ---- Weekly admissions this month (Week 1–4) ----
        $weeklyAdmissions = [0, 0, 0, 0];
        DB::table('admissions')
            ->whereYear('admission_date', $today->year)
            ->whereMonth('admission_date', $today->month)
            ->pluck('admission_date')
            ->each(function ($date) use (&$weeklyAdmissions) {
                $day  = Carbon::parse($date)->day;
                $week = min((int) floor(($day - 1) / 7), 3);
                $weeklyAdmissions[$week]++;
            });

        // ---- Income vs Expense — last 6 months ----
        $khmerMonths = ['មករា','កុម្ភៈ','មីនា','មេសា','ឧសភា','មិថុនា','កក្កដា','សីហា','កញ្ញា','តុលា','វិច្ឆិកា','ធ្នូ'];
        $months = $incomeByMonth = $expenseByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i);
            $months[] = $khmerMonths[$m->month - 1];
            $incomeByMonth[] = (float) DB::table('payments')->where('status', 'success')
                ->whereYear('payment_date', $m->year)->whereMonth('payment_date', $m->month)->sum('amount');
            $expenseByMonth[] = Schema::hasTable('expenses') ? (float) DB::table('expenses')
                ->whereYear('expense_date', $m->year)->whereMonth('expense_date', $m->month)->sum('amount') : 0;
        }

        // ---- Department breakdown (needs rooms.department_id — see note) ----
        $departmentBreakdown = [];
        if (Schema::hasColumn('rooms', 'department_id')) {
            $activeAdmissions = DB::table('admissions')
                ->join('rooms', 'admissions.room_id', '=', 'rooms.room_id')
                ->join('departments', 'rooms.department_id', '=', 'departments.department_id')
                ->where('admissions.status', 'admitted')
                ->select('departments.department_name', DB::raw('count(*) as total'))
                ->groupBy('departments.department_name')
                ->orderByDesc('total')
                ->get();

            $sumActive = $activeAdmissions->sum('total');
            $departmentBreakdown = $activeAdmissions->map(function ($row) use ($sumActive) {
                return [
                    'name'    => $row->department_name,
                    'total'   => $row->total,
                    'percent' => $sumActive > 0 ? round(($row->total / $sumActive) * 100) : 0,
                ];
            })->toArray();
        }

        return view('form.dashboard.dashboard', [
            'totalPatients'        => $totalPatients,
            'totalPatientsTrend'   => $this->percentChange($totalPatients, $patientsBeforeThisMo),
            'newPatientsThisMonth' => $newPatientsThisMonth,
            'newPatientsTrend'     => $this->percentChange($newPatientsThisMonth, $newPatientsLastMonth),
            'totalMedicines'       => $totalMedicines,

            'dailyIncome'      => $dailyIncome,
            'incomeTrend'      => $this->percentChange($dailyIncome, $yesterdayIncome),
            'dailyExpense'     => $dailyExpense,
            'expenseTrend'     => $this->percentChange($dailyExpense, $yesterdayExpense),
            'dailyProfit'      => $dailyProfit,
            'profitTrend'      => $this->percentChange($dailyProfit, $yesterdayProfit),

            'totalRooms'        => $totalRooms,
            'occupiedRooms'     => $occupiedRooms,
            'availableRooms'    => $availableRooms,
            'occupancyPercent'  => $occupancyPercent,

            'weeklyAdmissions' => $weeklyAdmissions,
            'months'           => $months,
            'incomeByMonth'    => $incomeByMonth,
            'expenseByMonth'   => $expenseByMonth,

            'departmentBreakdown' => $departmentBreakdown,

            // placeholders — no source table yet
            'todayAppointments' => 0,
            'emergencyCases'    => 0,
        ]);
    }

    private function percentChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
