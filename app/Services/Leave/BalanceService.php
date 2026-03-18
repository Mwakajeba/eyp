<?php

namespace App\Services\Leave;

use App\Models\Hr\Employee;
use App\Models\Hr\LeaveBalance;
use App\Models\Hr\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Get or create current balance for employee and leave type
     */
    public function getCurrentBalance(Employee $employee, LeaveType $leaveType): LeaveBalance
    {
        $currentDate = Carbon::now()->startOfDay();

        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('as_of', $currentDate)
            ->first();

        if (!$balance) {
            // Get or create from previous balance
            $balance = $this->getOrCreateBalance($employee, $leaveType, $currentDate);
        }

        return $balance;
    }

    /**
     * Get or create balance for a specific date
     */
    public function getOrCreateBalance(Employee $employee, LeaveType $leaveType, Carbon $asOf): LeaveBalance
    {
        return LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'as_of' => $asOf,
            ],
            [
                'company_id' => $employee->company_id,
                'opening_days' => $leaveType->annual_entitlement ?? 0,
                'carried_over_days' => 0,
                'accrued_days' => 0,
                'taken_days' => 0,
                'pending_hold_days' => 0,
                'expired_days' => 0,
                'adjusted_days' => 0,
            ]
        );
    }

    /**
     * Get available days for employee and leave type
     */
    public function availableDays(Employee $employee, LeaveType $leaveType): float
    {
        $balance = $this->getCurrentBalance($employee, $leaveType);

        return (float) $balance->available_days;
    }

    /**
     * Check if employee has enough balance
     */
    public function hasEnoughBalance(Employee $employee, LeaveType $leaveType, float $days): bool
    {
        $available = $this->availableDays($employee, $leaveType);

        // If leave type allows negative balance
        if ($leaveType->allow_negative) {
            return true;
        }

        return $available >= $days;
    }

    /**
     * Place hold on balance (when request is submitted)
     */
    public function placeHold(Employee $employee, LeaveType $leaveType, float $days): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->increment('pending_hold_days', $days);
        });
    }

    /**
     * Release hold on balance (when request is cancelled/rejected)
     */
    public function releaseHold(Employee $employee, LeaveType $leaveType, float $days): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->decrement('pending_hold_days', $days);
        });
    }

    /**
     * Mark days as taken (when request is approved)
     */
    public function markTaken(Employee $employee, LeaveType $leaveType, float $days): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->increment('taken_days', $days);
        });
    }

    /**
     * Reverse taken days (when approved leave is cancelled)
     */
    public function reverseTaken(Employee $employee, LeaveType $leaveType, float $days): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->decrement('taken_days', $days);
        });
    }

    /**
     * Add accrued days
     */
    public function addAccruedDays(Employee $employee, LeaveType $leaveType, float $days): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->increment('accrued_days', $days);
        });
    }

    /**
     * Add adjustment
     */
    public function addAdjustment(Employee $employee, LeaveType $leaveType, float $days, string $reason = null): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->increment('adjusted_days', $days);
        });
    }

    /**
     * Add carried over days
     */
    public function addCarriedOverDays(Employee $employee, LeaveType $leaveType, float $days): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->increment('carried_over_days', $days);
        });
    }

    /**
     * Mark days as expired
     */
    public function markExpired(Employee $employee, LeaveType $leaveType, float $days): void
    {
        DB::transaction(function () use ($employee, $leaveType, $days) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $balance->increment('expired_days', $days);
            $balance->decrement('carried_over_days', $days);
        });
    }

    /**
     * Get balance summary for all leave types for an employee
     */
    public function getBalanceSummary(Employee $employee): array
    {
        $leaveTypes = LeaveType::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->get();

        $summary = [];

        foreach ($leaveTypes as $leaveType) {
            $balance = $this->getCurrentBalance($employee, $leaveType);

            $summary[] = [
                'leave_type' => $leaveType,
                'balance' => $balance,
                'available' => $balance->available_days,
            ];
        }

        return $summary;
    }
}

