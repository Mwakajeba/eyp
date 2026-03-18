<?php

namespace App\Imports;

use App\Models\Hr\Employee;
use App\Models\Hr\Department;
use App\Models\Hr\Position;
use App\Models\Hr\TradeUnion;
use App\Models\Branch;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class EmployeeImport implements ToCollection, WithHeadingRow
{
    private $companyId;
    private $defaultBranchId;
    private $importedCount = 0;
    private $errors = [];

    public function __construct($companyId, $defaultBranchId = null)
    {
        $this->companyId = $companyId;
        $this->defaultBranchId = $defaultBranchId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because Excel rows start at 1 and we have header row
            
            try {
                // Skip empty rows - check if all required fields are empty
                $hasData = false;
                $requiredFields = ['first_name', 'last_name', 'phone_number', 'date_of_birth', 'gender'];
                foreach ($requiredFields as $field) {
                    if (!empty(trim($row[$field] ?? ''))) {
                        $hasData = true;
                        break;
                    }
                }
                
                if (!$hasData) {
                    continue; // Skip completely empty rows
                }

                // Parse dates before validation
                $parsedRow = $row->toArray();
                
                // Parse date_of_birth
                if (!empty($parsedRow['date_of_birth'])) {
                    try {
                        $parsedRow['date_of_birth'] = $this->parseDate(trim($parsedRow['date_of_birth']));
                    } catch (\Exception $e) {
                        $this->errors[] = [
                            'row' => $rowNumber,
                            'errors' => ["Invalid date format for Date of Birth: '{$row['date_of_birth']}'. Use YYYY-MM-DD format or Excel date format."]
                        ];
                        continue;
                    }
                }
                
                // Parse date_of_employment
                if (!empty($parsedRow['date_of_employment'])) {
                    try {
                        $parsedRow['date_of_employment'] = $this->parseDate(trim($parsedRow['date_of_employment']));
                    } catch (\Exception $e) {
                        $this->errors[] = [
                            'row' => $rowNumber,
                            'errors' => ["Invalid date format for Date of Employment: '{$row['date_of_employment']}'. Use YYYY-MM-DD format or Excel date format."]
                        ];
                        continue;
                    }
                }

                // Trim string fields
                foreach ($parsedRow as $key => $value) {
                    if (is_string($value)) {
                        $parsedRow[$key] = trim($value);
                    }
                }

                // Validate row data
                $validator = Validator::make($parsedRow, [
                    'employee_number' => 'nullable|string|max:255',
                    'first_name' => 'required|string|max:255',
                    'middle_name' => 'nullable|string|max:255',
                    'last_name' => 'required|string|max:255',
                    'email' => 'nullable|email|max:255',
                    'phone_number' => 'required|string|max:255',
                    'date_of_birth' => 'required|date',
                    'gender' => 'required|in:male,female,other',
                    'marital_status' => 'required|in:single,married,divorced,widowed',
                    'country' => 'required|string|max:255',
                    'region' => 'required|string|max:255',
                    'district' => 'required|string|max:255',
                    'current_physical_location' => 'required|string',
                    'basic_salary' => 'required|numeric|min:0',
                    'identity_document_type' => 'required|in:national_id,passport,driving_license,voters_id',
                    'identity_number' => 'required|string|max:255',
                    'employment_type' => 'nullable|string|max:255', // Allow any string, will be mapped later
                    'date_of_employment' => 'required|date',
                    'designation' => 'nullable|string|max:255',
                    'tin' => 'nullable|string|max:255',
                    'bank_name' => 'nullable|string|max:255',
                    'bank_account_number' => 'nullable|string|max:255',
                    'department_name' => 'nullable|string|max:255',
                    'position_title' => 'nullable|string|max:255',
                    'branch_name' => 'nullable|string|max:255',
                    'trade_union_name' => 'nullable|string|max:255',
                ]);

                if ($validator->fails()) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => $validator->errors()->all()
                    ];
                    continue;
                }

                // Check for duplicate employee number, email, or phone
                $duplicateEmployee = null;
                $duplicateField = '';

                if (!empty($parsedRow['employee_number'])) {
                    $duplicateEmployee = Employee::where('company_id', $this->companyId)
                        ->where('employee_number', $parsedRow['employee_number'])
                        ->first();
                    if ($duplicateEmployee) {
                        $duplicateField = 'employee number';
                    }
                }

                // Check for email duplicates
                if (!$duplicateEmployee && !empty($parsedRow['email'])) {
                    $emailLower = strtolower(trim($parsedRow['email']));
                    
                    // Check both employee and user tables for email duplicates
                    $duplicateEmployee = Employee::where('company_id', $this->companyId)
                        ->whereRaw('LOWER(email) = ?', [$emailLower])
                        ->first();
                    
                    if (!$duplicateEmployee) {
                        $duplicateUser = User::where('company_id', $this->companyId)
                            ->whereRaw('LOWER(email) = ?', [$emailLower])
                            ->first();
                        if ($duplicateUser) {
                            // Try to find the associated employee
                            $associatedEmployee = Employee::where('user_id', $duplicateUser->id)->first();
                            if ($associatedEmployee) {
                                $duplicateEmployee = $associatedEmployee;
                            }
                        }
                    }
                    
                    if ($duplicateEmployee) {
                        $duplicateField = 'email';
                    }
                }

                // Check for phone duplicates
                if (!$duplicateEmployee && !empty($parsedRow['phone_number'])) {
                    $formattedPhone = $this->formatPhoneNumber($parsedRow['phone_number']);
                    
                    // Check users table for phone duplicates
                    $duplicateUser = User::where('company_id', $this->companyId)
                        ->where('phone', $formattedPhone)
                        ->first();
                    
                    if ($duplicateUser) {
                        // Try to find the associated employee
                        $associatedEmployee = Employee::where('user_id', $duplicateUser->id)->first();
                        if ($associatedEmployee) {
                            $duplicateEmployee = $associatedEmployee;
                            $duplicateField = 'phone number';
                        }
                    }
                }

                if ($duplicateEmployee) {
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'errors' => ["Employee with this {$duplicateField} already exists (Employee: {$duplicateEmployee->first_name} {$duplicateEmployee->last_name})"]
                    ];
                    continue;
                }

                // Find or create related entities
                $departmentId = $this->findOrCreateDepartment($parsedRow['department_name'] ?? null);
                $positionId = $this->findOrCreatePosition($parsedRow['position_title'] ?? null, $departmentId);
                $branchId = $this->findBranch($parsedRow['branch_name'] ?? null) ?? $this->defaultBranchId;
                $tradeUnionId = $this->findTradeUnion($parsedRow['trade_union_name'] ?? null);

                // Generate employee number if not provided
                $employeeNumber = $parsedRow['employee_number'] ?? null;
                if (empty($employeeNumber)) {
                    $employeeNumber = $this->generateEmployeeNumber();
                }

                // Map employment type to valid enum values
                $employmentType = $this->mapEmploymentType($parsedRow['employment_type'] ?? 'full_time');

                // Create user and employee in transaction
                DB::beginTransaction();
                
                try {
                    // Create user account first
                    $employeeUser = $this->createUserForEmployee($parsedRow);

                    // Create employee record
                    Employee::create([
                        'company_id' => $this->companyId,
                        'branch_id' => $branchId,
                        'user_id' => $employeeUser->id,
                        'department_id' => $departmentId,
                        'position_id' => $positionId,
                        'trade_union_id' => $tradeUnionId,
                        'employee_number' => $employeeNumber,
                        'first_name' => $parsedRow['first_name'],
                        'middle_name' => $parsedRow['middle_name'] ?? null,
                        'last_name' => $parsedRow['last_name'],
                        'date_of_birth' => Carbon::parse($parsedRow['date_of_birth']),
                        'gender' => $parsedRow['gender'],
                        'marital_status' => $parsedRow['marital_status'],
                        'country' => $parsedRow['country'],
                        'region' => $parsedRow['region'],
                        'district' => $parsedRow['district'],
                        'current_physical_location' => $parsedRow['current_physical_location'],
                        'email' => $parsedRow['email'] ?? null,
                        'phone_number' => $this->formatPhoneNumber($parsedRow['phone_number']),
                        'basic_salary' => $parsedRow['basic_salary'],
                        'identity_document_type' => $parsedRow['identity_document_type'],
                        'identity_number' => $parsedRow['identity_number'],
                        'employment_type' => $employmentType,
                        'date_of_employment' => Carbon::parse($parsedRow['date_of_employment']),
                        'designation' => $parsedRow['designation'] ?? null,
                        'tin' => $parsedRow['tin'] ?? null,
                        'bank_name' => $parsedRow['bank_name'] ?? null,
                        'bank_account_number' => $parsedRow['bank_account_number'] ?? null,
                        'status' => 'active',
                        'include_in_payroll' => true,
                    ]);

                    DB::commit();
                    $this->importedCount++;
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e; // Re-throw to be caught by outer catch block
                }

            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'errors' => ['Error processing row: ' . $e->getMessage()]
                ];
            }
        }
    }

    private function createUserForEmployee($parsedRow)
    {
        // Generate full name
        $fullName = trim(($parsedRow['first_name'] ?? '') . ' ' . ($parsedRow['middle_name'] ?? '') . ' ' . ($parsedRow['last_name'] ?? ''));
        
        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($parsedRow['phone_number'] ?? '');
        
        // Check if user with this email or phone already exists
        $existingUser = null;
        if (!empty($parsedRow['email'])) {
            $existingUser = User::where('email', $parsedRow['email'])
                ->where('company_id', $this->companyId)
                ->first();
        }
        
        if (!$existingUser && !empty($formattedPhone)) {
            $existingUser = User::where('phone', $formattedPhone)
                ->where('company_id', $this->companyId)
                ->first();
        }
        
        if ($existingUser) {
            return $existingUser;
        }
        
        // Create new user
        $userData = [
            'name' => $fullName,
            'phone' => $formattedPhone,
            'password' => Hash::make('password123'), // Default password - should be changed on first login
            'company_id' => $this->companyId,
            'status' => 'active',
            'is_active' => 'yes',
        ];
        
        // Only add email if provided and not empty
        if (!empty($parsedRow['email'])) {
            $userData['email'] = $parsedRow['email'];
        }
        
        $user = User::create($userData);
        
        // Assign employee role if it exists
        $employeeRole = Role::where('name', 'employee')->first();
        if ($employeeRole) {
            $user->assignRole($employeeRole);
        }
        
        // Assign user to branch if provided
        $branchId = $this->findBranch($parsedRow['branch_name'] ?? null) ?? $this->defaultBranchId;
        if ($branchId) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $user->branches()->attach($branchId);
            }
        }
        
        return $user;
    }

    private function findOrCreateDepartment($departmentName)
    {
        if (empty($departmentName)) {
            return null;
        }

        $department = Department::where('company_id', $this->companyId)
            ->where('name', $departmentName)
            ->first();

        if (!$department) {
            $department = Department::create([
                'company_id' => $this->companyId,
                'name' => $departmentName,
            ]);
        }

        return $department->id;
    }

    private function findOrCreatePosition($positionTitle, $departmentId = null)
    {
        if (empty($positionTitle)) {
            return null;
        }

        $position = Position::where('company_id', $this->companyId)
            ->where('title', $positionTitle)
            ->first();

        if (!$position) {
            $position = Position::create([
                'company_id' => $this->companyId,
                'department_id' => $departmentId,
                'title' => $positionTitle,
            ]);
        }

        return $position->id;
    }

    private function findBranch($branchName)
    {
        if (empty($branchName)) {
            return null;
        }

        $branch = Branch::where('company_id', $this->companyId)
            ->where('name', $branchName)
            ->first();

        return $branch ? $branch->id : null;
    }

    private function findTradeUnion($tradeUnionName)
    {
        if (empty($tradeUnionName)) {
            return null;
        }

        $tradeUnion = TradeUnion::where('company_id', $this->companyId)
            ->where('name', $tradeUnionName)
            ->first();

        return $tradeUnion ? $tradeUnion->id : null;
    }

    private function generateEmployeeNumber()
    {
        $lastEmployee = Employee::where('company_id', $this->companyId)
            ->where('employee_number', 'like', 'EMP%')
            ->orderBy('employee_number', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastEmployee) {
            $lastNumber = (int) str_replace('EMP', '', $lastEmployee->employee_number);
            $nextNumber = $lastNumber + 1;
        }

        return 'EMP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

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

    private function mapEmploymentType($employmentType)
    {
        // Map common employment type values to valid enum values
        $mappings = [
            'permanent' => 'full_time',
            'temporary' => 'contract',
            'full-time' => 'full_time',
            'part-time' => 'part_time',
            'fulltime' => 'full_time',
            'parttime' => 'part_time',
            'contractor' => 'contract',
            'internship' => 'intern',
        ];

        $normalized = strtolower(trim($employmentType));
        
        // Check if it's already a valid enum value
        $validEnums = ['full_time', 'part_time', 'contract', 'intern'];
        if (in_array($normalized, $validEnums)) {
            return $normalized;
        }
        
        // Return mapped value if exists, otherwise default to full_time
        return $mappings[$normalized] ?? 'full_time';
    }

    /**
     * Parse date string from various formats including Excel serial dates
     */
    private function parseDate(string $dateString): string
    {
        $dateString = trim($dateString);

        // Check if it's an Excel serial date (typically 5-digit number)
        if (preg_match('/^\d{5,6}$/', $dateString)) {
            $serialDate = (int) $dateString;

            // Excel dates start from January 1, 1900 (serial date 1)
            // But Excel has a bug where it considers 1900-02-29 as a valid date (it wasn't a leap year)
            // So we need to adjust for that
            if ($serialDate > 60) {
                $serialDate--; // Adjust for the non-existent 1900-02-29
            }

            // Convert to Unix timestamp: Excel epoch is 1900-01-01
            $excelEpoch = strtotime('1900-01-01');
            $timestamp = $excelEpoch + ($serialDate - 1) * 86400; // 86400 seconds per day

            return date('Y-m-d', $timestamp);
        }

        // Try to parse as standard date format
        try {
            // Try common date formats
            $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'];
            
            foreach ($formats as $format) {
                $parsed = \DateTime::createFromFormat($format, $dateString);
                if ($parsed && $parsed->format($format) === $dateString) {
                    return $parsed->format('Y-m-d');
                }
            }
            
            // Try Carbon's flexible parsing
            $carbon = Carbon::parse($dateString);
            return $carbon->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Unable to parse date: {$dateString}");
        }
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
