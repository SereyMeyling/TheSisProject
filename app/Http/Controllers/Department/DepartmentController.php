<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{

    public function index(Request $request)
    {
        $department = $this->getFilteredDepartments($request);
        $totalDepartment = Department::count();
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('form.department.partials.table', compact('department'))->render(),
                'total' => $totalDepartment,
            ]);
        }
        return view('form.department.department', compact('department', 'totalDepartment'));
    }


    protected function getFilteredDepartments(Request $request)
    {
        $query = Department::query()
            ->select(['department_id', 'department_name', 'description', 'created_at']);

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('department_id', 'LIKE', $searchTerm)
                    ->orWhere('department_name', 'LIKE', $searchTerm)
                    ->orWhere('description', 'LIKE', $searchTerm);
            });
        }
        return $query->paginate(10)->appends($request->query());
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

       return redirect()->back()->with(['success' => 'ដេប៉ាតឺម៉ង់ត្រូវបានបន្ថែមដោយជោគជ័យ']);
    }

    public function edit($id)
    {
        $department = Department::find($id);
        if (!$department) {
           return redirect()->back()->with(['error'=> 'រកមិនឃើញដេប៉ាតឺម៉ង់']);
        }
        return response()->json($department);
    }

    public function update(Request $request, $id)
    {
        $department = Department::find($id);
        if (!$department) {
           return redirect()->back()->with(['error'=> 'រកមិនឃើញដេប៉ាតឺម៉ង់']);
        }
        $department->update([
            'department_name' => $request->department_name,
            'description' => $request->description,
        ]);
      return redirect()->back()->with(['success' => 'ដេប៉ាតឺម៉ង់ត្រូវបានកែប្រែដោយជោគជ័យ']);
    }

    public function destroy($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return redirect()->back()->with(['error'=> 'រកមិនឃើញដេប៉ាតឺម៉ង់']);
        }
        $department->delete();
       return redirect()->back()->with(['success' => 'ដេប៉ាតឺម៉ង់ត្រូវបានលុបដោយជោគជ័យ']);
    }
}
