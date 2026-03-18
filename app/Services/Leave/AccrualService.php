<?php

namespace App\Services\Leave;

use App\Models\Company;
use App\Models\Hr\Employee;
use App\Models\Hr\LeaveAccrualRun;
use App\Models\Hr\LeaveCarryover;
use App\Models\Hr\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccrualService
{
    public function __construct(
        protected BalanceService $balanceService
    ) {}

    /**
     * Run monthly accruals for a company
     */
    public function runMonthly(Carbon $period, Company $company): LeaveAccrualRun
    {
        $run = LeaveAccrualRun::create([
            'company_id' => $company->id,
            'period' => $period,
            'status' => 'running',
        ]);

        try {
            DB::transaction(function () use ($run, $company, $period) {
                $employeesProcessed = 0;

                // Get all active employees
                $employees = Employee::where('company_id', $company->id)
                    ->where('status', 'active')
                    ->get();

                // Get all active leave types with monthly accrual
                $leaveTypes = LeaveType::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->where('accrual_type', 'monthly')
                    ->get();

                foreach ($employees as $employee) {
                    foreach ($leaveTypes as $leaveType) {
                        // Calculate monthly accrual (pro-rated)
                        $monthlyAccrual = $this->calculateMonthlyAccrual($employee, $leaveType, $period);

                        if ($monthlyAccrual > 0) {
                            $this->balanceService->addAccruedDays($employee, $leaveType, $monthlyAccrual);
                        }
                    }

                    $employeesProcessed++;
                }

                $run->markCompleted($employeesProcessed);
            });
        } catch (\Exception $e) {
            Log::error('Accrual run failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            $run->markFailed($e->getMessage());
        }

        return $run->fresh();
    }

    /**
     * Run annual accruals for a company
     */
    public function runAnnual(Carbon $period, Company $company): LeaveAccrualRun
    {
        $run = LeaveAccrualRun::create([
            'company_id' => $company->id,
            'period' => $period,
            'status' => 'running',
            'meta' => ['type' => 'annual'],
        ]);

        try {
            DB::transaction(function () use ($run, $company, $period) {
                $employeesProcessed = 0;

                // Get all active employees
                $employees = Employee::where('company_id', $company->id)
                    ->where('status', 'active')
                    ->get();

                // Get all active leave types with annual accrual
                $leaveTypes = LeaveType::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->where('accrual_type', 'annual')
                    ->get();

                foreach ($employees as $employee) {
                    foreach ($leaveTypes as $leaveType) {
                        // Add full annual entitlement
                        $annualEntitlement = $leaveType->annual_entitlement ?? 0;

                        if ($annualEntitlement > 0) {
                            $balance = $this->balanceService->getCurrentBalance($employee, $leaveType);
                            $balance->update(['opening_days' => $annualEntitlement]);
                        }
                    }

                    $employeesProcessed++;
                }

                $run->markCompleted($employeesProcessed);
            });
        } catch (\Exception $e) {
            Log::error('Annual accrual run failed', [
                'run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);

            $run->markFailed($e->getMessage());
        }

        return $run->fresh();
    }

    /**
     * Calculate monthly accrual for an employee
     */
    protected function calculateMonthlyAccrual(Employee $employee, LeaveType $leaveType, Carbon $period): float
    {
        $annualEntitlement = $leaveType->annual_entitlement ?? 0;

        if ($annualEntitlement === 0) {
            return 0;
        }

        // Monthly accrual = Annual entitlement / 12
        return round($annualEntitlement / 12, 2);
    }

    /**
     * Process year-end carryover
     */
    public function processCarryover(Company $company, Carbon $effectiveDate): int
    {
        $processed = 0;

        try {
            DB::transaction(function () use ($company, $effectiveDate, &$processed) {
                // Get all active employees
                $employees = Employee::where('company_id', $company->id)
                    ->where('status', 'active')
                    ->get();

                // Get all leave types that allow carryover
                $leaveTypes = LeaveType::where('company_id', $company->id)
                    ->where('is_active', true)
                    ->whereNotNull('carryover_cap_days')
                    ->get();

                foreach ($employees as $employee) {
                    foreach ($leaveTypes as $leaveType) {
                        $balance = $this->balanceService->getCurrentBalance($employee, $leaveType);

                        // Calculate available balance (excluding pending)
                        $availableForCarryover = $balance->opening_days
                            + $balance->accrued_days
                            + $balance->adjusted_days
                            + $balance->carried_over_days
                            - $balance->taken_days
                            - $balance->expired_days;

                        if ($availableForCarryover > 0) {
                            // Apply carryover cap
                            $daysToCarry = min($availableForCarryover, $leaveType->carryover_cap_days);

                            // Create carryover record
                            LeaveCarryover::create([
                                'employee_id' => $employee->id,
                                'leave_type_id' => $leaveType->id,
                                'days_carried' => $daysToCarry,
                                'effective_date' => $effectiveDate,
                                'expiry_date' => $leaveType->carryover_expiry_date,
                            ]);

                            // Add to next year's balance
                            $this->balanceService->addCarriedOverDays($employee, $leaveType, $daysToCarry);

                            $processed++;
                        }
                    }
                }
            });
        } catch (\Exception $e) {
            Log::error('Carryover processing failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $processed;
    }

    /**
     * Process expired carryovers
     */
    public function processExpiredCarryovers(Company $company, Carbon $expiryDate): int
    {
        $expired = 0;

        try {
            DB::transaction(function () use ($company, $expiryDate, &$expired) {
                // Get all carryovers that expired
                $carryovers = LeaveCarryover::whereHas('employee', function ($q) use ($company) {
                    $q->where('company_id', $company->id);
                })
                ->where('expiry_date', '<=', $expiryDate)
                ->where('days_carried', '>', 0)
                ->get();

                foreach ($carryovers as $carryover) {
                    // Mark days as expired
                    $this->balanceService->markExpired(
                        $carryover->employee,
                        $carryover->leaveType,
                        $carryover->days_carried
                    );

                    // Zero out the carryover
                    $carryover->update(['days_carried' => 0]);

                    $expired++;
                }
            });
        } catch (\Exception $e) {
            Log::error('Expired carryovers processing failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $expired;
    }
}

