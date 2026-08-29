<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'role:admin']);
    }

    /**
     * Display a listing of employees with search, filters, and summary stats.
     */
    public function index(Request $request)
    {
        $employees = $this->getFilteredEmployees($request);
        $totalEmployees = Employee::count();
        $totalDoctors = Employee::where('role', 'doctor')->count();
        $totalNurses = Employee::where('role', 'nurse')->count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $departments = Department::orderBy('department_name', 'asc')->get();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('form.employee.partials.table', compact('employees'))->render(),
                'total' => $totalEmployees,
                'doctors' => $totalDoctors,
                'nurses' => $totalNurses,
                'active' => $activeEmployees,
            ]);
        }

        return view('form.employee.employee', compact(
            'employees',
            'totalEmployees',
            'totalDoctors',
            'totalNurses',
            'activeEmployees',
            'departments'
        ));
    }

    /**
     * Filter query for employee search and pagination.
     */
    protected function getFilteredEmployees(Request $request)
    {
        $query = Employee::with('medicalRecords')
            ->join('departments', 'employees.department_id', '=', 'departments.department_id')
            ->select('employees.*', 'departments.department_name');

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('employees.employee_code', 'LIKE', $searchTerm)
                    ->orWhere('employees.first_name', 'LIKE', $searchTerm)
                    ->orWhere('employees.last_name', 'LIKE', $searchTerm)
                    ->orWhere('employees.phone', 'LIKE', $searchTerm)
                    ->orWhere('employees.specialization', 'LIKE', $searchTerm)
                    ->orWhere('departments.department_name', 'LIKE', $searchTerm);
            });
        }

        if ($request->filled('role')) {
            $query->where('employees.role', $request->role);
        }

        if ($request->filled('department_id')) {
            $query->where('employees.department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('employees.status', $request->status);
        }

        $query->orderBy('employees.employee_id', 'desc');

        return $query->paginate(10)->appends($request->query());
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'department_id'  => 'required|exists:departments,department_id',
            'employee_code'  => 'nullable|string|max:20|unique:employees,employee_code',
            'first_name'     => 'required|string|max:50',
            'last_name'      => 'required|string|max:50',
            'role'           => 'required|in:doctor,nurse,pharmacist,lab_technician,admin',
            'specialization' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'status'         => 'required|in:active,inactive',
        ]);

        $employeeCode = $request->employee_code;
        if (empty($employeeCode)) {
            $maxId = Employee::max('employee_id') ?? 0;
            $employeeCode = 'EMP-' . str_pad($maxId + 1, 4, '0', STR_PAD_LEFT);
        }

        Employee::create([
            'department_id'  => $request->department_id,
            'employee_code'  => $employeeCode,
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'role'           => $request->role,
            'specialization' => $request->specialization,
            'phone'          => $request->phone,
            'status'         => $request->status,
        ]);

        return redirect()->back()->with(['success' => 'បុគ្គលិកត្រូវបានបន្ថែមដោយជោគជ័យ']);
    }

    /**
     * Show the form for editing the specified employee (returns JSON).
     */
    public function edit($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['error' => 'រកមិនឃើញបុគ្គលិក'], 404);
        }

        return response()->json($employee);
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញបុគ្គលិក']);
        }

        $request->validate([
            'department_id'  => 'required|exists:departments,department_id',
            'employee_code'  => 'required|string|max:20|unique:employees,employee_code,' . $id . ',employee_id',
            'first_name'     => 'required|string|max:50',
            'last_name'      => 'required|string|max:50',
            'role'           => 'required|in:doctor,nurse,pharmacist,lab_technician,admin',
            'specialization' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'status'         => 'required|in:active,inactive',
        ]);

        $employee->update([
            'department_id'  => $request->department_id,
            'employee_code'  => $request->employee_code,
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'role'           => $request->role,
            'specialization' => $request->specialization,
            'phone'          => $request->phone,
            'status'         => $request->status,
        ]);

        return redirect()->back()->with(['success' => 'ព័ត៌មានបុគ្គលិកត្រូវបានកែប្រែដោយជោគជ័យ']);
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy($id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញបុគ្គលិក']);
        }

        $employee->delete();

        return redirect()->back()->with(['success' => 'បុគ្គលិកត្រូវបានលុបដោយជោគជ័យ']);
    }
}
