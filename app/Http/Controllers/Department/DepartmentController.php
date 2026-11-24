<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $query->orderBy('department_id', 'asc');
        return $query->paginate(10)->appends($request->query());
    }



    /**
     * Shared rules for store and update; lengths match the departments table (M11).
     */
    protected function rules(): array
    {
        return [
            'department_name' => 'required|string|max:100',
            'description' => 'required|string|max:255',
        ];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with(['error' => $validator->errors()->first()]);
        }
        $validated = $validator->validated();

        Department::create([
            'department_name' => $validated['department_name'],
            'description' => $validated['description'],
        ]);

        return redirect()->back()->with(['success' => 'ដេប៉ាតឺម៉ង់ត្រូវបានបន្ថែមដោយជោគជ័យ']);
    }

    public function edit($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញដេប៉ាតឺម៉ង់']);
        }
        return response()->json($department);
    }

    public function update(Request $request, $id)
    {
        $department = Department::find($id);
        if (!$department) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញដេប៉ាតឺម៉ង់']);
        }
        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return redirect()->back()->withInput()->with(['error' => $validator->errors()->first()]);
        }
        $validated = $validator->validated();

        $department->update([
            'department_name' => $validated['department_name'],
            'description' => $validated['description'],
        ]);
        return redirect()->back()->with(['success' => 'ដេប៉ាតឺម៉ង់ត្រូវបានកែប្រែដោយជោគជ័យ']);
    }

    public function destroy($id)
    {
        $department = Department::find($id);
        if (!$department) {
            return redirect()->back()->with(['error' => 'រកមិនឃើញដេប៉ាតឺម៉ង់']);
        }
        $department->delete();
        return redirect()->back()->with(['success' => 'ដេប៉ាតឺម៉ង់ត្រូវបានលុបដោយជោគជ័យ']);
    }
}
