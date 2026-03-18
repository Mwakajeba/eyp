<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hr\Department;
use App\Models\Company;
use App\Models\Branch;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all companies
        $companies = Company::all();

        foreach ($companies as $company) {
            // Get all branches for this company
            $branches = Branch::where('company_id', $company->id)->get();

            // If no branches, create departments at company level (branch_id = null)
            if ($branches->isEmpty()) {
                $this->createDepartmentsForCompany($company, null);
            } else {
                // Create departments for each branch
                foreach ($branches as $branch) {
                    $this->createDepartmentsForCompany($company, $branch->id);
                }
            }
        }
    }

    /**
     * Create common departments for a company/branch
     */
    private function createDepartmentsForCompany(Company $company, ?int $branchId): void
    {
        $departments = [
            'Finance & Accounting',
            'Human Resources',
            'Operations',
            'Sales & Marketing',
            'IT & Technology',
            'Administration',
            'Procurement',
            'Customer Service',
            'Production',
            'Quality Assurance',
        ];

        foreach ($departments as $deptName) {
            // Check if department already exists
            $exists = Department::where('company_id', $company->id)
                ->where('branch_id', $branchId)
                ->where('name', $deptName)
                ->exists();

            if (!$exists) {
                Department::create([
                    'company_id' => $company->id,
                    'branch_id' => $branchId,
                    'name' => $deptName,
                    'parent_id' => null,
                ]);
            }
        }
    }
}
