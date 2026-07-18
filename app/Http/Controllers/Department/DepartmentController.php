<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        //search
        $query = Department::query();
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('department_id', 'LIKE', $searchTerm)
                    ->orWhere('department_name', 'LIKE', $searchTerm)
                    ->orWhere('description', 'LIKE', $searchTerm);

            });
        }
        //filter
        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }
        //pagination
        $department = $query->paginate(10)
            ->appends($request->query());
        //count total department
        $totalDepartment = Department::count();
        //count active department
        // $activeDepartment = Department::where('is_active', true)->count();
        return view('form.department.department', compact('department', 'totalDepartment'));

    }
    public function store(Request $request)
    {
        $request->validate([

            'department_name' => 'required|string|max:150',
            'description' => 'required|string',

        ]);

        $department = Department::create([
            'department_name' => $request->department_name,
            'description' => $request->description,

        ]);

        return redirect()->back()->with(['success' => true, 'data' => $department]);
    }
    public function edit($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return redirect()->back()->with('error', 'រកមិនឃើញដេប៉ាតឺម៉ង់');
        }
        return response()->json($department);
    }
    public function update(Request $request, $id)
    {
        $department = Department::find($id);
        if (!$department) {
            return redirect()->back()->with('error', 'រកមិនឃើញដេប៉ាតឺម៉ង់');
        }
        $department->update([
            'department_name' => $request->department_name,
            'description' => $request->description,
        ]);
        return redirect()->back()->with(['success' => true, 'data' => $department]);
    }
    public function destroy($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return redirect()->back()->with('error', 'រកមិនឃើញដេប៉ាតឺម៉ង់');
        }
        $department->delete();
        return redirect()->back()->with(['success' => true, 'data' => $department]);
    }
}
