<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\Employee;
use App\Models\Hr\Department;
use App\Models\Hr\Position;
use App\Models\Hr\TradeUnion;
use App\Models\Hr\FileType;
use App\Models\Hr\Document;
use App\Models\Hr\JobGrade;
use App\Services\Hr\PositionService;
use App\Models\Branch;
use App\Models\User;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeeImport;
use App\Exports\EmployeeTemplateExport;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUser = auth()->user();
        $companyId = $currentUser->company_id;
        
        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->with(['department', 'position', 'branch'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Dashboard statistics
        $stats = [
            'total' => Employee::where('company_id', $companyId)->count(),
            'active' => Employee::where('company_id', $companyId)->where('status', 'active')->count(),
            'inactive' => Employee::where('company_id', $companyId)->where('status', '!=', 'active')->count(),
            'new_this_month' => Employee::where('company_id', $companyId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return view('hr-payroll.employees.index', compact('employees', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currentUser = auth()->user();
        $currentBranchId = current_branch_id();
        
        // Filter departments by current branch if available
        $departmentsQuery = Department::where('company_id', $currentUser->company_id);
        if ($currentBranchId) {
            $departmentsQuery->where(function($q) use ($currentBranchId) {
                $q->where('branch_id', $currentBranchId)
                  ->orWhereNull('branch_id');
            });
        }
        $departments = $departmentsQuery->orderBy('name')->get();
        
        $positions = Position::where('company_id', $currentUser->company_id)
            ->with('grade')
            ->orderBy('title')
            ->get();
        $tradeUnions = TradeUnion::where('company_id', $currentUser->company_id)->where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('company_id', $currentUser->company_id)->orderBy('name')->get();

        // Generate next employee number
        $lastEmployee = Employee::where('company_id', $currentUser->company_id)
            ->where('employee_number', 'like', 'EMP%')
            ->orderBy('employee_number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastEmployee) {
            $lastNumber = (int) str_replace('EMP', '', $lastEmployee->employee_number);
            $nextNumber = $lastNumber + 1;
        }

        $nextEmployeeNumber = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        $currentBranchId = current_branch_id();
        
        // Get countries list
        $countries = function_exists('get_countries_list') ? get_countries_list() : ['Tanzania' => 'Tanzania'];
        $tanzaniaRegions = function_exists('get_tanzania_regions') ? get_tanzania_regions() : [];
        $tanzaniaDistricts = function_exists('get_tanzania_districts') ? get_tanzania_districts() : [];

        return view('hr-payroll.employees.create', compact('departments', 'positions', 'tradeUnions', 'branches', 'nextEmployeeNumber', 'currentBranchId', 'countries', 'tanzaniaRegions', 'tanzaniaDistricts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();

        // Debug: Log the incoming request
        \Log::info('Employee creation request', [
            'request_data' => $request->except(['_token']),
            'user_id' => $currentUser->id,
            'company_id' => $currentUser->company_id
        ]);

        try {
            $validated = $request->validate([
            // Basic Information
            'employee_number' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female,other'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],

            // Location Information
            'country' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'current_physical_location' => ['required', 'string'],

            // Contact Information
            'email' => [
                'nullable', 
                'email', 
                'max:255',
                function ($attribute, $value, $fail) use ($currentUser) {
                    if (!empty($value)) {
                        $emailLower = strtolower(trim($value));
                        // Check in hr_employees table
                        $existsInEmployees = Employee::where('company_id', $currentUser->company_id)
                            ->whereRaw('LOWER(email) = ?', [$emailLower])
                            ->exists();
                        // Check in users table
                        $existsInUsers = User::where('company_id', $currentUser->company_id)
                            ->whereRaw('LOWER(email) = ?', [$emailLower])
                            ->exists();
                        if ($existsInEmployees || $existsInUsers) {
                            $fail('This email address is already registered in the system.');
                        }
                    }
                }
            ],
            'phone_number' => [
                'required', 
                'string', 
                'max:255',
                function ($attribute, $value, $fail) use ($currentUser) {
                    $formattedPhone = $this->formatPhoneNumber($value);
                    $exists = User::where('company_id', $currentUser->company_id)
                        ->where('phone', $formattedPhone)
                        ->exists();
                    if ($exists) {
                        $fail('This phone number is already registered in the system.');
                    }
                }
            ],

            // Employment Information
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'identity_document_type' => ['required', 'string', 'max:255'],
            'identity_number' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,casual,intern'],
            'date_of_employment' => ['required', 'date'],
            'designation' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:255'],

            // Banking Information
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],

            // Department and Position
            'department_id' => ['nullable', 'exists:hr_departments,id'],
            'position_id' => ['nullable', 'exists:hr_positions,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],

            // NHIF Information
            'has_nhif' => ['boolean'],
            'nhif_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nhif_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nhif_member_number' => ['nullable', 'string', 'max:255'],

            // Pension Information
            'has_pension' => ['boolean'],
            'social_fund_type' => ['nullable', 'string', 'max:255'],
            'social_fund_number' => ['nullable', 'string', 'max:255'],
            'pension_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pension_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Trade Union Information
            'has_trade_union' => ['boolean'],
            'trade_union_id' => ['nullable', 'exists:hr_trade_unions,id'],
            'trade_union_category' => ['nullable', 'in:amount,percentage'],
            'trade_union_value' => ['nullable', 'numeric', 'min:0'],

            // Additional Benefits
            'has_wcf' => ['boolean'],
            'wcf_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'wcf_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_heslb' => ['boolean'],
            'heslb_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'heslb_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_sdl' => ['boolean'],
            'sdl_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sdl_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'email.unique' => 'This email address is already registered in the system.',
            'phone_number.unique' => 'This phone number is already registered in the system.',
        ]);

        // Process trade union value based on category
        if (isset($validated['trade_union_category']) && isset($validated['trade_union_value'])) {
            if ($validated['trade_union_category'] === 'amount') {
                $validated['trade_union_amount'] = $validated['trade_union_value'];
                $validated['trade_union_percent'] = null;
            } elseif ($validated['trade_union_category'] === 'percentage') {
                $validated['trade_union_percent'] = $validated['trade_union_value'];
                $validated['trade_union_amount'] = null;
            }
        }
        unset($validated['trade_union_value']);
        
        // Validate trade union information when has_trade_union is true
        if ($request->has('has_trade_union') && $request->has_trade_union) {
            if (empty($validated['trade_union_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['trade_union_id' => 'Trade Union is required when Trade Union is enabled.']);
            }
            if (empty($validated['trade_union_category']) || empty($request->trade_union_value)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['trade_union_value' => 'Trade Union amount or percentage is required when Trade Union is enabled.']);
            }
        }

        // Generate employee number if not provided
        if (empty($validated['employee_number'])) {
            $lastEmployee = Employee::where('company_id', $currentUser->company_id)
                ->where('employee_number', 'like', 'EMP%')
                ->orderBy('employee_number', 'desc')
                ->first();

            $nextNumber = 1;
            if ($lastEmployee) {
                $lastNumber = (int) str_replace('EMP', '', $lastEmployee->employee_number);
                $nextNumber = $lastNumber + 1;
            }

            $validated['employee_number'] = 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        // Handle checkbox fields that might not be sent when unchecked
        $validated['include_in_payroll'] = $request->has('include_in_payroll');
        $validated['has_wcf'] = $request->has('has_wcf');
        $validated['has_heslb'] = $request->has('has_heslb');
        $validated['has_sdl'] = $request->has('has_sdl');

        // Create user account for the employee
        $employeeUser = User::create([
            'name' => trim($validated['first_name'] . ' ' . $validated['middle_name'] . ' ' . $validated['last_name']),
            'phone' => $this->formatPhoneNumber($validated['phone_number']),
            'email' => $validated['email'] ?? null,
            'password' => Hash::make('password123'), // Default password, should be changed on first login
            'company_id' => $currentUser->company_id,
            'status' => 'active',
            'is_active' => 'yes',
        ]);

        // Assign branch to user if provided
        if (!empty($validated['branch_id'])) {
            $employeeUser->branches()->attach($validated['branch_id']);
        }

        // Assign employee role
        $employeeRole = Role::where('name', 'employee')->first();
        if ($employeeRole) {
            $employeeUser->assignRole($employeeRole);
        }

        // Create employee record with user_id
        $employee = Employee::create(array_merge($validated, [
            'company_id' => $currentUser->company_id,
            'user_id' => $employeeUser->id,
        ]));

        return redirect()->route('hr.employees.index')->with('success', 'Employee created successfully with user account.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Employee creation validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['_token'])
            ]);

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput($request->except(['_token']));
        } catch (\Exception $e) {
            \Log::error('Employee creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->except(['_token'])
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'An error occurred while creating the employee. Please try again.'])
                ->withInput($request->except(['_token']));
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $currentUser = auth()->user();
        
        // Verify employee belongs to user's company
        if ($employee->company_id !== $currentUser->company_id) {
            abort(403, 'Unauthorized access to employee.');
        }
        
        $employee->load(['department', 'position', 'branch', 'documents.fileType']);

        $fileTypes = FileType::where('company_id', $currentUser->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('hr-payroll.employees.show', compact('employee', 'fileTypes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $currentUser = auth()->user();
        
        // Verify employee belongs to user's company
        if ($employee->company_id !== $currentUser->company_id) {
            abort(403, 'Unauthorized access to employee.');
        }
        
        $currentBranchId = current_branch_id();
        
        // Filter departments by current branch if available
        $departmentsQuery = Department::where('company_id', $currentUser->company_id);
        if ($currentBranchId) {
            $departmentsQuery->where(function($q) use ($currentBranchId) {
                $q->where('branch_id', $currentBranchId)
                  ->orWhereNull('branch_id');
            });
        }
        $departments = $departmentsQuery->orderBy('name')->get();
        
        $positions = Position::where('company_id', $currentUser->company_id)->orderBy('title')->get();
        $tradeUnions = TradeUnion::where('company_id', $currentUser->company_id)->where('is_active', true)->orderBy('name')->get();
        $branches = Branch::where('company_id', $currentUser->company_id)->orderBy('name')->get();
        
        // Get countries list
        $countries = function_exists('get_countries_list') ? get_countries_list() : ['Tanzania' => 'Tanzania'];
        $tanzaniaRegions = function_exists('get_tanzania_regions') ? get_tanzania_regions() : [];
        $tanzaniaDistricts = function_exists('get_tanzania_districts') ? get_tanzania_districts() : [];

        return view('hr-payroll.employees.edit', compact('employee', 'departments', 'positions', 'tradeUnions', 'branches', 'currentBranchId', 'countries', 'tanzaniaRegions', 'tanzaniaDistricts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $currentUser = auth()->user();
        
        // Verify employee belongs to user's company
        if ($employee->company_id !== $currentUser->company_id) {
            abort(403, 'Unauthorized access to employee.');
        }

        $validated = $request->validate([
            // Basic Information
            'employee_number' => ['required', 'string', 'max:255', Rule::unique('hr_employees')->ignore($employee->id)->where(fn($q) => $q->where('company_id', $currentUser->company_id))],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female,other'],
            'marital_status' => ['required', 'in:single,married,divorced,widowed'],

            // Location Information
            'country' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'current_physical_location' => ['required', 'string'],

            // Contact Information
            'email' => [
                'nullable', 
                'email', 
                'max:255',
                function ($attribute, $value, $fail) use ($currentUser, $employee) {
                    if (!empty($value)) {
                        $emailLower = strtolower(trim($value));
                        // Check in hr_employees table
                        $existsInEmployees = Employee::where('company_id', $currentUser->company_id)
                            ->where('id', '!=', $employee->id)
                            ->whereRaw('LOWER(email) = ?', [$emailLower])
                            ->exists();
                        // Check in users table
                        $existsInUsers = User::where('company_id', $currentUser->company_id)
                            ->when($employee->user_id, fn($q) => $q->where('id', '!=', $employee->user_id))
                            ->whereRaw('LOWER(email) = ?', [$emailLower])
                            ->exists();
                        if ($existsInEmployees || $existsInUsers) {
                            $fail('This email address is already registered in the system.');
                        }
                    }
                }
            ],
            'phone_number' => [
                'required', 
                'string', 
                'max:255',
                function ($attribute, $value, $fail) use ($currentUser, $employee) {
                    $formattedPhone = $this->formatPhoneNumber($value);
                    $exists = User::where('company_id', $currentUser->company_id)
                        ->where('phone', $formattedPhone)
                        ->when($employee->user_id, fn($q) => $q->where('id', '!=', $employee->user_id))
                        ->exists();
                    if ($exists) {
                        $fail('This phone number is already registered in the system.');
                    }
                }
            ],

            // Employment Information
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'identity_document_type' => ['required', 'string', 'max:255'],
            'identity_number' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,casual,intern'],
            'date_of_employment' => ['required', 'date'],
            'designation' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:255'],

            // Banking Information
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:255'],

            // Department and Position
            'department_id' => ['nullable', 'exists:hr_departments,id'],
            'position_id' => ['nullable', 'exists:hr_positions,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],

            // Status
            'status' => ['required', 'in:active,inactive,terminated,on_leave'],

            // NHIF Information
            'has_nhif' => ['boolean'],
            'nhif_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nhif_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nhif_member_number' => ['nullable', 'string', 'max:255'],

            // Pension Information
            'has_pension' => ['boolean'],
            'social_fund_type' => ['nullable', 'string', 'max:255'],
            'social_fund_number' => ['nullable', 'string', 'max:255'],
            'pension_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'pension_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Trade Union Information
            'has_trade_union' => ['boolean'],
            'trade_union_id' => ['nullable', 'exists:hr_trade_unions,id'],
            'trade_union_category' => ['nullable', 'in:amount,percentage'],
            'trade_union_value' => ['nullable', 'numeric', 'min:0'],

            // Additional Benefits
            'has_wcf' => ['boolean'],
            'wcf_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'wcf_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_heslb' => ['boolean'],
            'heslb_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'heslb_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_sdl' => ['boolean'],
            'sdl_employee_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sdl_employer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            'email.unique' => 'This email address is already registered in the system.',
            'phone_number.unique' => 'This phone number is already registered in the system.',
        ]);

        // Process trade union value based on category
        if (isset($validated['trade_union_category']) && isset($validated['trade_union_value'])) {
            if ($validated['trade_union_category'] === 'amount') {
                $validated['trade_union_amount'] = $validated['trade_union_value'];
                $validated['trade_union_percent'] = null;
            } elseif ($validated['trade_union_category'] === 'percentage') {
                $validated['trade_union_percent'] = $validated['trade_union_value'];
                $validated['trade_union_amount'] = null;
            }
        }
        unset($validated['trade_union_value']);
        
        // Validate trade union information when has_trade_union is true
        if ($request->has('has_trade_union') && $request->has_trade_union) {
            if (empty($validated['trade_union_id'])) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['trade_union_id' => 'Trade Union is required when Trade Union is enabled.']);
            }
            if (empty($validated['trade_union_category']) || empty($request->trade_union_value)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['trade_union_value' => 'Trade Union amount or percentage is required when Trade Union is enabled.']);
            }
        }
        
        // Validate basic_salary against position's grade if position is selected
        if (!empty($validated['position_id']) && !empty($validated['basic_salary'])) {
            $position = Position::with('grade')->find($validated['position_id']);
            if ($position && $position->grade) {
                if (!$position->grade->isSalaryInRange($validated['basic_salary'])) {
                    $min = $position->grade->minimum_salary ? number_format($position->grade->minimum_salary, 2) : 'N/A';
                    $max = $position->grade->maximum_salary ? number_format($position->grade->maximum_salary, 2) : 'N/A';
                    return redirect()->back()
                        ->withInput()
                        ->withErrors([
                            'basic_salary' => "Basic salary ({$validated['basic_salary']}) is outside the acceptable range for position's grade ({$min} - {$max})."
                        ]);
                }
            }
        }

        // Handle checkbox fields that might not be sent when unchecked
        $validated['include_in_payroll'] = $request->has('include_in_payroll');
        $validated['has_wcf'] = $request->has('has_wcf');
        $validated['has_heslb'] = $request->has('has_heslb');
        $validated['has_sdl'] = $request->has('has_sdl');

        // Update employee record
        $employee->update($validated);

        // Update associated user account if it exists
        if ($employee->user) {
            $employee->user->update([
                'name' => trim($validated['first_name'] . ' ' . $validated['middle_name'] . ' ' . $validated['last_name']),
                'phone' => $this->formatPhoneNumber($validated['phone_number']),
                'email' => $validated['email'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'is_active' => ($validated['status'] ?? 'active') === 'active' ? 'yes' : 'no',
            ]);

            // Update branch assignment if provided
            if (isset($validated['branch_id'])) {
                $employee->user->branches()->sync([$validated['branch_id']]);
            }
        }

        return redirect()->route('hr.employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $currentUser = auth()->user();
        
        // Verify employee belongs to user's company
        if ($employee->company_id !== $currentUser->company_id) {
            abort(403, 'Unauthorized access to employee.');
        }

        // Delete related records that don't have cascade delete
        $employee->allowances()->delete();
        $employee->externalLoans()->delete();

        // The following will be automatically deleted due to cascade delete:
        // - hr_documents (has onDelete('cascade'))
        // - hr_salary_advances (has onDelete('cascade'))
        // - gl_transactions will set employee_id to null (has onDelete('set null'))

        // Store user_id before deleting employee
        $userId = $employee->user_id;

        // Delete employee record
        $employee->delete();

        // Delete associated user account if it exists
        if ($userId) {
            $employeeUser = User::find($userId);
            if ($employeeUser) {
                $employeeUser->delete();
            }
        }

        return redirect()->route('hr.employees.index')->with('success', 'Employee and associated user account deleted successfully.');
    }

    /**
     * Store a document for the employee.
     */
    public function storeDocument(Request $request, Employee $employee)
    {
        $currentUser = auth()->user();

        // Verify employee belongs to user's company
        if ($employee->company_id !== $currentUser->company_id) {
            abort(403, 'Unauthorized access to employee.');
        }

        try {
            $validated = $request->validate([
                'file_type_id' => ['required', 'exists:hr_file_types,id'],
                'file' => ['required', 'file', 'max:10240'], // 10MB max
                'expiry_date' => ['nullable', 'date'],
            ]);

            // Get file type to validate file
            $fileType = FileType::where('company_id', $currentUser->company_id)
                ->where('id', $validated['file_type_id'])
                ->firstOrFail();

            // Validate file extension if file type has restrictions
            if ($fileType->allowed_extensions) {
                $fileExtension = strtolower($request->file('file')->getClientOriginalExtension());
                if (!in_array($fileExtension, $fileType->allowed_extensions)) {
                    $errorMessage = 'File extension not allowed. Allowed extensions: ' . implode(', ', $fileType->allowed_extensions);
                    
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage,
                            'errors' => ['file' => [$errorMessage]]
                        ], 422);
                    }
                    
                    return back()->withErrors(['file' => $errorMessage]);
                }
            }

            // Validate file size if file type has restrictions
            if ($fileType->max_file_size) {
                $fileSizeKB = $request->file('file')->getSize() / 1024;
                if ($fileSizeKB > $fileType->max_file_size) {
                    $errorMessage = 'File size exceeds maximum allowed size of ' . $fileType->max_file_size_human;
                    
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errorMessage,
                            'errors' => ['file' => [$errorMessage]]
                        ], 422);
                    }
                    
                    return back()->withErrors(['file' => $errorMessage]);
                }
            }

            // Store the file
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('hr_documents', $fileName, 'public');

            // Create document record
            Document::create([
                'employee_id' => $employee->id,
                'file_type_id' => $validated['file_type_id'],
                'document_type' => null, // Using file_type_id instead
                'title' => $fileType->name . ' - ' . $file->getClientOriginalName(), // Auto-generate title from file type and file name
                'description' => null, // Simplified form doesn't include description
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_extension' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'is_required' => false, // Default to false since simplified form doesn't include this
                'expiry_date' => $validated['expiry_date'],
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully.',
                    'data' => [
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $fileType->name
                    ]
                ]);
            }

            return redirect()->route('hr.employees.show', $employee)->with('success', 'Document uploaded successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while uploading the document: ' . $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['general' => 'An error occurred while uploading the document.']);
        }
    }

    /**
     * Download a document.
     */
    public function downloadDocument(Document $document)
    {
        $currentUser = auth()->user();

        // Verify document belongs to user's company through employee
        if ($document->employee->company_id !== $currentUser->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        $filePath = storage_path('app/public/' . $document->file_path);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $document->file_name);
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(Document $document)
    {
        $currentUser = auth()->user();

        // Verify document belongs to user's company through employee
        if ($document->employee->company_id !== $currentUser->company_id) {
            abort(403, 'Unauthorized access to document.');
        }

        $employee = $document->employee;

        // Delete the physical file
        $filePath = storage_path('app/public/' . $document->file_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete the database record
        $document->delete();

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Document deleted successfully.');
    }

    /**
     * Format phone number to 255 format
     *
     * @param string $phone
     * @return string
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any spaces, dashes, or other characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If starts with +255, remove the +
        if (str_starts_with($phone, '+255')) {
            return substr($phone, 1);
        }

        // If starts with 0, remove 0 and add 255
        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }

        // If already starts with 255, return as is
        if (str_starts_with($phone, '255')) {
            return $phone;
        }

        // If it's a 9-digit number (Tanzania mobile), add 255
        if (strlen($phone) === 9) {
            return '255' . $phone;
        }

        // Return as is if no pattern matches
        return $phone;
    }

    /**
     * Show import form
     */
    public function showImport()
    {
        $currentUser = auth()->user();
        $branches = Branch::where('company_id', $currentUser->company_id)->orderBy('name')->get();
        
        return view('hr-payroll.employees.import', compact('branches'));
    }

    /**
     * Download employee import template
     */
    public function downloadTemplate()
    {
        return Excel::download(new EmployeeTemplateExport, 'employee_import_template.xlsx');
    }

    /**
     * Import employees from Excel file
     */
    public function import(Request $request)
    {
        $currentUser = auth()->user();
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
            'default_branch' => 'required|exists:branches,id'
        ]);

        // Verify branch belongs to user's company
        $defaultBranch = Branch::where('id', $request->default_branch)
            ->where('company_id', $currentUser->company_id)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            $import = new EmployeeImport($currentUser->company_id, $defaultBranch->id);
            Excel::import($import, $request->file('file'));

            DB::commit();

            $importedCount = $import->getImportedCount();
            $errors = $import->getErrors();

            if ($importedCount > 0 && empty($errors)) {
                return redirect()->route('hr.employees.index')
                    ->with('success', "Successfully imported {$importedCount} employees.");
            } elseif ($importedCount > 0 && !empty($errors)) {
                return redirect()->route('hr.employees.import')
                    ->with('warning', "Imported {$importedCount} employees with some errors.")
                    ->with('import_errors', $errors);
            } else {
                return redirect()->route('hr.employees.import')
                    ->with('error', 'No employees were imported.')
                    ->with('import_errors', $errors);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Employee import failed: ' . $e->getMessage());
            
            return redirect()->route('hr.employees.import')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if email is unique via AJAX
     */
    public function checkEmailUnique(Request $request)
    {
        $currentUser = auth()->user();
        $email = $request->input('email');
        $employeeId = $request->input('employee_id'); // For edit mode
        
        if (empty($email)) {
            return response()->json(['available' => true]);
        }
        
        // Check in hr_employees table
        $existsInEmployees = Employee::where('company_id', $currentUser->company_id)
            ->where('email', $email)
            ->when($employeeId, fn($q) => $q->where('id', '!=', $employeeId))
            ->exists();
            
        // Check in users table
        $existsInUsers = User::where('company_id', $currentUser->company_id)
            ->where('email', $email)
            ->when($employeeId, function($q) use ($employeeId) {
                $employee = Employee::find($employeeId);
                return $employee && $employee->user_id ? $q->where('id', '!=', $employee->user_id) : $q;
            })
            ->exists();
        
        return response()->json([
            'available' => !($existsInEmployees || $existsInUsers),
            'message' => ($existsInEmployees || $existsInUsers) ? 'This email address is already registered in the system.' : null
        ]);
    }

    /**
     * Check if phone is unique via AJAX
     */
    public function checkPhoneUnique(Request $request)
    {
        $currentUser = auth()->user();
        $phone = $this->formatPhoneNumber($request->input('phone'));
        $employeeId = $request->input('employee_id'); // For edit mode
        
        if (empty($phone)) {
            return response()->json(['available' => true]);
        }
        
        // Check in users table
        $existsInUsers = User::where('company_id', $currentUser->company_id)
            ->where('phone', $phone)
            ->when($employeeId, function($q) use ($employeeId) {
                $employee = Employee::find($employeeId);
                return $employee && $employee->user_id ? $q->where('id', '!=', $employee->user_id) : $q;
            })
            ->exists();
        
        return response()->json([
            'available' => !$existsInUsers,
            'message' => $existsInUsers ? 'This phone number is already registered in the system.' : null
        ]);
    }
}
