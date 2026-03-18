<?php

namespace App\Services\Hr;

use App\Models\Hr\Employee;
use App\Models\Hr\EmploymentStatusHistory;
use App\Models\Hr\Contract;
use App\Models\Hr\PositionAssignment;
use App\Models\Hr\EmployeeCompliance;
use App\Models\Hr\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeService
{
    /**
     * Update employee employment status with effective dating
     */
    public function updateEmploymentStatus(
        Employee $employee,
        string $status,
        \DateTime $effectiveDate,
        ?string $reason = null,
        ?int $changedBy = null
    ): EmploymentStatusHistory {
        DB::beginTransaction();
        try {
            // End previous status if exists
            $previousStatus = $employee->employmentStatusHistory()
                ->whereNull('end_date')
                ->orWhere('end_date', '>=', $effectiveDate)
                ->latest('effective_date')
                ->first();

            if ($previousStatus && $previousStatus->effective_date < $effectiveDate) {
                $previousStatus->update(['end_date' => $effectiveDate->copy()->subDay()]);
            }

            // Create new status record
            $statusHistory = EmploymentStatusHistory::create([
                'employee_id' => $employee->id,
                'status' => $status,
                'effective_date' => $effectiveDate,
                'reason' => $reason,
                'changed_by' => $changedBy ?? auth()->id(),
            ]);

            // Update employee status field (for backward compatibility)
            $employee->update(['status' => $status]);

            DB::commit();
            return $statusHistory;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update employment status', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create or update employee contract
     */
    public function createContract(
        Employee $employee,
        array $contractData
    ): Contract {
        DB::beginTransaction();
        try {
            // End previous active contract if exists
            $previousContract = $employee->contracts()
                ->where('status', 'active')
                ->latest('start_date')
                ->first();

            if ($previousContract) {
                $previousContract->update(['status' => 'terminated']);
            }

            $contract = Contract::create(array_merge($contractData, [
                'employee_id' => $employee->id,
            ]));

            DB::commit();
            return $contract;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create contract', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Assign employee to position with effective dating
     */
    public function assignPosition(
        Employee $employee,
        int $positionId,
        \DateTime $effectiveDate,
        bool $isActing = false,
        float $actingAllowancePercent = 0
    ): PositionAssignment {
        DB::beginTransaction();
        try {
            // Check position availability
            $position = Position::findOrFail($positionId);
            if (!$position->hasAvailableHeadcount() && !$isActing) {
                throw new \Exception('Position has no available headcount');
            }

            // End previous assignment if exists
            $previousAssignment = $employee->positionAssignments()
                ->whereNull('end_date')
                ->orWhere('end_date', '>=', $effectiveDate)
                ->latest('effective_date')
                ->first();

            if ($previousAssignment && $previousAssignment->effective_date < $effectiveDate) {
                $previousAssignment->update(['end_date' => $effectiveDate->copy()->subDay()]);
            }

            // Create new assignment
            $assignment = PositionAssignment::create([
                'employee_id' => $employee->id,
                'position_id' => $positionId,
                'effective_date' => $effectiveDate,
                'is_acting' => $isActing,
                'acting_allowance_percent' => $actingAllowancePercent,
            ]);

            // Update position filled headcount if not acting
            if (!$isActing) {
                $position->increment('filled_headcount');
            }

            // Update employee position_id (for backward compatibility)
            $employee->update(['position_id' => $positionId]);

            DB::commit();
            return $assignment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to assign position', [
                'employee_id' => $employee->id,
                'position_id' => $positionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update employee compliance record
     */
    public function updateCompliance(
        Employee $employee,
        string $complianceType,
        ?string $complianceNumber = null,
        bool $isValid = false,
        ?\DateTime $expiryDate = null
    ): EmployeeCompliance {
        $compliance = EmployeeCompliance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'compliance_type' => $complianceType,
            ],
            [
                'compliance_number' => $complianceNumber,
                'is_valid' => $isValid,
                'expiry_date' => $expiryDate,
                'last_verified_at' => $isValid ? now() : null,
            ]
        );

        return $compliance;
    }

    /**
     * Check if employee can be included in payroll
     */
    public function canIncludeInPayroll(Employee $employee): array
    {
        $issues = [];

        // Check employment status
        $currentStatus = $employee->getCurrentEmploymentStatus();
        if (!$currentStatus || $currentStatus->status !== 'active') {
            $issues[] = 'Employee is not active';
        }

        // Check compliance
        if (!$employee->isCompliantForPayroll()) {
            $issues[] = 'Employee compliance is incomplete';
        }

        // Check active contract
        $activeContract = $employee->activeContract;
        if (!$activeContract) {
            $issues[] = 'No active contract found';
        }

        // Check position assignment
        $currentAssignment = $employee->currentPositionAssignment;
        if (!$currentAssignment) {
            $issues[] = 'No active position assignment';
        }

        return [
            'can_include' => empty($issues),
            'issues' => $issues,
        ];
    }
}

