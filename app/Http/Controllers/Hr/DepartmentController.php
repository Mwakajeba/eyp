<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\Department;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $branchId = current_branch_id();
            $departments = Department::query()
                ->where('company_id', current_company_id());
            
            if ($branchId) {
                $departments->where('branch_id', $branchId);
            } else {
                $departments->whereNull('branch_id');
            }
            
            $departments->orderBy('name');

            return DataTables::of($departments)
                ->addIndexColumn()
                ->addColumn('hod_display', function ($department) {
                    return $department->hod ?: '-';
                })
                ->addColumn('description_display', function ($department) {
                    return $department->description ? \Str::limit($department->description, 50) : '-';
                })
                ->addColumn('action', function ($department) {
                    $editBtn = '<a href="' . route('hr.departments.edit', $department) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $department->hash_id . '" data-name="' . $department->name . '"><i class="bx bx-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('hr-payroll.departments.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.departments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $branchId = current_branch_id();
        
        $validated = $request->validate([
            'name' => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('hr_departments')->where(function ($query) use ($branchId) {
                    $query->where('company_id', current_company_id());
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    } else {
                        $query->whereNull('branch_id');
                    }
                })
            ],
            'hod' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $department = Department::create([
            'company_id' => current_company_id(),
            'branch_id' => $branchId,
            'name' => $validated['name'],
            'hod' => $validated['hod'],
            'description' => $validated['description'],
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Department created successfully.'
            ]);
        }

        return redirect()->route('hr.departments.index')->with('success', 'Department created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        // Verify department belongs to user's company
        if ($department->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to department.');
        }
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        // Verify department belongs to user's company
        if ($department->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to department.');
        }
        return view('hr-payroll.departments.edit', compact('department'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        // Verify department belongs to user's company
        if ($department->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to department.');
        }
        
        $branchId = current_branch_id();
        
        // Build validation rules
        $rules = [
            'name' => [
                'required', 
                'string', 
                'max:255',
                function ($attribute, $value, $fail) use ($department, $branchId) {
                    // Only check uniqueness if name has changed
                    if ($value === $department->name) {
                        return; // Name hasn't changed, no need to validate
                    }
                    
                    // Check if name already exists in same company and branch
                    $query = Department::where('company_id', current_company_id())
                        ->where('name', $value)
                        ->where('id', '!=', $department->id);
                    
                    if ($branchId) {
                        $query->where('branch_id', $branchId);
                    } else {
                        $query->whereNull('branch_id');
                    }
                    
                    if ($query->exists()) {
                        $fail('The name has already been taken in this branch.');
                    }
                }
            ],
            'hod' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
        
        $validated = $request->validate($rules);

        $department->update([
            'name' => $validated['name'],
            'hod' => $validated['hod'],
            'description' => $validated['description'],
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Department updated successfully.'
            ]);
        }

        return redirect()->route('hr.departments.index')->with('success', 'Department updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        // Verify department belongs to user's company
        if ($department->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access to department.');
        }

        // Check if department has employees
        $employeeCount = $department->employees()->count();
        if ($employeeCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete department "' . $department->name . '" because it has ' . $employeeCount . ' employee(s). Please reassign employees first.'
            ], 400);
        }

        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.'
        ]);
    }
}
