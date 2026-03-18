<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\EmployeeCompliance;
use App\Models\Hr\Employee;
use App\Services\Hr\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeComplianceController extends Controller
{
    protected $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $compliance = EmployeeCompliance::whereHas('employee', function ($q) {
                $q->where('company_id', current_company_id());
            })
            ->with('employee')
            ->orderBy('hr_employee_compliance.created_at', 'desc');

            return DataTables::of($compliance)
                ->addIndexColumn()
                ->addColumn('employee_name', function ($compliance) {
                    return $compliance->employee->full_name;
                })
                ->addColumn('compliance_type', function ($compliance) {
                    return strtoupper($compliance->compliance_type);
                })
                ->addColumn('status_badge', function ($compliance) {
                    $badge = $compliance->status_badge_color;
                    $text = $compliance->isValid() ? 'Valid' : 'Invalid';
                    return '<span class="badge bg-' . $badge . '">' . $text . '</span>';
                })
                ->addColumn('expiry_date', function ($compliance) {
                    return $compliance->expiry_date ? $compliance->expiry_date->format('d M Y') : 'N/A';
                })
                ->addColumn('action', function ($compliance) {
                    $viewBtn = '<a href="' . route('hr.employee-compliance.show', $compliance->id) . '" class="btn btn-sm btn-outline-info me-1"><i class="bx bx-show"></i></a>';
                    $editBtn = '<a href="' . route('hr.employee-compliance.edit', $compliance->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bx bx-edit"></i></a>';
                    $deleteBtn = '<button class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $compliance->id . '"><i class="bx bx-trash"></i></button>';
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->orderColumn('created_at', 'hr_employee_compliance.created_at $1')
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        // Dashboard statistics
        $today = now();
        $companyId = current_company_id();
        
        $stats = [
            'total' => EmployeeCompliance::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count(),
            'valid' => EmployeeCompliance::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->where('is_valid', true)
                ->where(function($q) use ($today) {
                    $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today);
                })
                ->count(),
            'expired' => EmployeeCompliance::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->where(function($q) use ($today) {
                    $q->where('is_valid', false)
                      ->orWhere(function($q2) use ($today) {
                          $q2->whereNotNull('expiry_date')->where('expiry_date', '<', $today);
                      });
                })
                ->count(),
            'expiring_soon' => EmployeeCompliance::whereHas('employee', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->where('is_valid', true)
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '>=', $today)
                ->where('expiry_date', '<=', $today->copy()->addDays(30))
                ->count(),
        ];

        return view('hr-payroll.employee-compliance.index', compact('stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $employees = Employee::where('company_id', current_company_id())
            ->orderBy('first_name')
            ->get();

        // Check if employee_id and compliance_type are provided (for pre-filling)
        $employeeId = $request->employee_id;
        $complianceType = $request->compliance_type;
        $existingCompliance = null;

        if ($employeeId && $complianceType) {
            $existingCompliance = EmployeeCompliance::where('employee_id', $employeeId)
                ->where('compliance_type', $complianceType)
                ->first();
        }

        return view('hr-payroll.employee-compliance.create', compact('employees', 'existingCompliance'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'compliance_details' => 'required|array|min:1',
            'compliance_details.*.compliance_type' => 'required|in:paye,pension,nhif,wcf,sdl',
            'compliance_details.*.compliance_number' => 'required|string|max:100',
            'compliance_details.*.validity_from' => 'required|date',
            'compliance_details.*.validity_to' => 'nullable|date|after:compliance_details.*.validity_from',
            'compliance_details.*.is_valid' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($validated['employee_id']);

            $createdCount = 0;
            $updatedCount = 0;
            $errors = [];

            // Process each compliance detail line
            foreach ($validated['compliance_details'] as $index => $detail) {
                // Validate date ranges
                if (!empty($detail['validity_to']) && !empty($detail['validity_from'])) {
                    if (strtotime($detail['validity_to']) < strtotime($detail['validity_from'])) {
                        $errors["compliance_details.{$index}.validity_to"] = 'Validity To date must be after Validity From date.';
                        continue;
                    }
                }

                // Check if compliance record already exists for this type
                $existingCompliance = EmployeeCompliance::where('employee_id', $validated['employee_id'])
                    ->where('compliance_type', $detail['compliance_type'])
                    ->first();

                $isUpdate = $existingCompliance !== null;

                // Prepare compliance_details array for this record
                $complianceDetails = [[
                    'compliance_number' => $detail['compliance_number'],
                    'validity_from' => $detail['validity_from'],
                    'validity_to' => $detail['validity_to'] ?? null,
                ]];

                if ($existingCompliance) {
                    // Update existing record
                    $existingCompliance->update([
                        'compliance_number' => $detail['compliance_number'],
                        'compliance_details' => $complianceDetails,
                        'is_valid' => $detail['is_valid'] ?? false,
                        'expiry_date' => !empty($detail['validity_to']) ? new \DateTime($detail['validity_to']) : null,
                        'last_verified_at' => ($detail['is_valid'] ?? false) ? now() : null,
                    ]);
                    $updatedCount++;
                } else {
                    // Create new record
                    EmployeeCompliance::create([
                        'employee_id' => $validated['employee_id'],
                        'compliance_type' => $detail['compliance_type'],
                        'compliance_number' => $detail['compliance_number'],
                        'compliance_details' => $complianceDetails,
                        'is_valid' => $detail['is_valid'] ?? false,
                        'expiry_date' => !empty($detail['validity_to']) ? new \DateTime($detail['validity_to']) : null,
                        'last_verified_at' => ($detail['is_valid'] ?? false) ? now() : null,
                    ]);
                    $createdCount++;
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return back()->withInput()->withErrors($errors);
            }

            DB::commit();

            $message = '';
            if ($createdCount > 0 && $updatedCount > 0) {
                $message = "Successfully created {$createdCount} and updated {$updatedCount} compliance record(s).";
            } elseif ($createdCount > 0) {
                $message = "Successfully created {$createdCount} compliance record(s).";
            } elseif ($updatedCount > 0) {
                $message = "Successfully updated {$updatedCount} compliance record(s).";
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('hr.employee-compliance.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create compliance record: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeCompliance $employeeCompliance)
    {
        if ($employeeCompliance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $employeeCompliance->load('employee');

        return view('hr-payroll.employee-compliance.show', compact('employeeCompliance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeCompliance $employeeCompliance)
    {
        if ($employeeCompliance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        return view('hr-payroll.employee-compliance.edit', compact('employeeCompliance'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeCompliance $employeeCompliance)
    {
        if ($employeeCompliance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'compliance_number' => 'nullable|string|max:100',
            'compliance_details' => 'nullable|array',
            'compliance_details.*.compliance_number' => 'required_with:compliance_details|string|max:100',
            'compliance_details.*.validity_from' => 'required_with:compliance_details|date',
            'compliance_details.*.validity_to' => 'nullable|date|after:compliance_details.*.validity_from',
            'is_valid' => 'boolean',
            'expiry_date' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            // Process compliance_details
            $complianceDetails = null;
            if (!empty($validated['compliance_details'])) {
                // Validate date ranges
                foreach ($validated['compliance_details'] as $index => $detail) {
                    if (!empty($detail['validity_to']) && !empty($detail['validity_from'])) {
                        if (strtotime($detail['validity_to']) < strtotime($detail['validity_from'])) {
                            return back()->withInput()->withErrors([
                                "compliance_details.{$index}.validity_to" => 'Validity To date must be after Validity From date.'
                            ]);
                        }
                    }
                }
                $complianceDetails = $validated['compliance_details'];
            }

            // Maintain backward compatibility: if compliance_number is provided but no compliance_details,
            // and current record has no compliance_details, create a default compliance detail entry
            if (empty($complianceDetails) && !empty($validated['compliance_number']) && empty($employeeCompliance->compliance_details)) {
                $complianceDetails = [[
                    'compliance_number' => $validated['compliance_number'],
                    'validity_from' => $validated['expiry_date'] ?? now()->format('Y-m-d'),
                    'validity_to' => $validated['expiry_date'] ?? null,
                ]];
            }

            $employeeCompliance->update([
                'compliance_number' => $validated['compliance_number'] ?? $employeeCompliance->compliance_number,
                'compliance_details' => $complianceDetails ?? $employeeCompliance->compliance_details,
                'is_valid' => $validated['is_valid'] ?? $employeeCompliance->is_valid,
                'expiry_date' => isset($validated['expiry_date']) ? new \DateTime($validated['expiry_date']) : $employeeCompliance->expiry_date,
                'last_verified_at' => ($validated['is_valid'] ?? $employeeCompliance->is_valid) ? now() : null,
            ]);

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Compliance record updated successfully.'
                ]);
            }

            return redirect()->route('hr.employee-compliance.index')
                ->with('success', 'Compliance record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to update compliance record: ' . $e->getMessage()]);
        }
    }

    /**
     * Check if compliance record already exists for employee and type
     */
    public function checkExisting(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:hr_employees,id',
                'compliance_type' => 'required|in:paye,pension,nhif,wcf,sdl',
            ]);

            $employee = Employee::where('company_id', current_company_id())
                ->findOrFail($request->employee_id);

            $existing = EmployeeCompliance::where('employee_id', $request->employee_id)
                ->where('compliance_type', $request->compliance_type)
                ->first();

            if ($existing) {
                return response()->json([
                    'exists' => true,
                    'compliance_type' => $existing->compliance_type,
                    'compliance_number' => $existing->compliance_number,
                    'is_valid' => $existing->isValid(),
                    'expiry_date' => $existing->expiry_date ? $existing->expiry_date->format('d M Y') : null,
                ]);
            }

            return response()->json([
                'exists' => false,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'exists' => false,
                'error' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'exists' => false,
                'error' => 'An error occurred while checking for existing compliance.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeCompliance $employeeCompliance)
    {
        if ($employeeCompliance->employee->company_id !== current_company_id()) {
            abort(403, 'Unauthorized access.');
        }

        $employeeCompliance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compliance record deleted successfully.'
        ]);
    }
}
