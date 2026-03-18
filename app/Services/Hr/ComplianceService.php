<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeCompliance;
use Illuminate\Support\Collection;

class ComplianceService
{
    /**
     * Get compliance status for employee
     */
    public function getComplianceStatus(Employee $employee): array
    {
        $complianceTypes = [
            EmployeeCompliance::TYPE_PAYE,
            EmployeeCompliance::TYPE_PENSION,
            EmployeeCompliance::TYPE_NHIF,
            EmployeeCompliance::TYPE_WCF,
            EmployeeCompliance::TYPE_SDL,
        ];

        $status = [];
        foreach ($complianceTypes as $type) {
            $compliance = $employee->complianceRecords()
                ->where('compliance_type', $type)
                ->first();

            $status[$type] = [
                'exists' => $compliance !== null,
                'is_valid' => $compliance ? $compliance->isValid() : false,
                'compliance_number' => $compliance->compliance_number ?? null,
                'expiry_date' => $compliance->expiry_date ?? null,
                'badge_color' => $compliance ? $compliance->status_badge_color : 'secondary',
            ];
        }

        return $status;
    }

    /**
     * Get employees with compliance issues
     */
    public function getEmployeesWithComplianceIssues(int $companyId): Collection
    {
        return Employee::where('company_id', $companyId)
            ->where('include_in_payroll', true)
            ->get()
            ->filter(function ($employee) {
                return !$employee->isCompliantForPayroll();
            });
    }

    /**
     * Get expiring compliance records
     */
    public function getExpiringCompliance(int $companyId, int $days = 30): Collection
    {
        return EmployeeCompliance::whereHas('employee', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
        ->expiring($days)
        ->with('employee')
        ->get();
    }

    /**
     * Bulk update compliance status
     */
    public function bulkUpdateCompliance(array $updates): void
    {
        foreach ($updates as $update) {
            EmployeeCompliance::updateOrCreate(
                [
                    'employee_id' => $update['employee_id'],
                    'compliance_type' => $update['compliance_type'],
                ],
                [
                    'compliance_number' => $update['compliance_number'] ?? null,
                    'is_valid' => $update['is_valid'] ?? false,
                    'expiry_date' => $update['expiry_date'] ?? null,
                    'last_verified_at' => $update['is_valid'] ? now() : null,
                ]
            );
        }
    }
}

