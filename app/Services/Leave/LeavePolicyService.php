<?php

namespace App\Services\Leave;

use App\Models\Hr\Employee;
use App\Models\Hr\LeaveType;
use App\Models\Hr\PublicHoliday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeavePolicyService
{
    /**
     * Resolve policy version at request time
     */
    public function resolvePolicyVersion(Employee $employee, LeaveType $leaveType, Carbon $onDate): string
    {
        return sprintf('v1_%s_%s', $leaveType->code ?? $leaveType->id, $onDate->format('Y'));
    }

    /**
     * Check if employee is eligible for leave type
     */
    public function isEligible(Employee $employee, LeaveType $leaveType): array
    {
        $eligibility = $leaveType->eligibility ?? [];
        $errors = [];

        // Check employment type
        if (isset($eligibility['employment_types'])) {
            if (!in_array($employee->employment_type, $eligibility['employment_types'])) {
                $errors[] = 'Your employment type is not eligible for this leave type.';
            }
        }

        // Check tenure (months of service)
        if (isset($eligibility['tenure_months'])) {
            $hireDate = $employee->hire_date ?? $employee->date_of_employment;
            if ($hireDate) {
                $tenureMonths = Carbon::parse($hireDate)->diffInMonths(Carbon::now());
                if ($tenureMonths < $eligibility['tenure_months']) {
                    $errors[] = sprintf(
                        'You need at least %d months of service. You have %d months.',
                        $eligibility['tenure_months'],
                        $tenureMonths
                    );
                }
            }
        }

        // Check gender (for maternity/paternity)
        if (isset($eligibility['gender'])) {
            if (strtolower($employee->gender ?? '') !== strtolower($eligibility['gender'])) {
                $errors[] = 'This leave type is only available for ' . $eligibility['gender'] . ' employees.';
            }
        }

        return [
            'eligible' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Compute days equivalent for a date range
     */
    public function computeDaysEquivalent(
        Employee $employee,
        LeaveType $leaveType,
        Carbon $start,
        Carbon $end,
        string $granularity
    ): array {
        $weekendHolidayMode = $leaveType->weekend_holiday_mode ?? [
            'count_weekends' => false,
            'count_public_holidays' => false,
        ];

        $countWeekends = $weekendHolidayMode['count_weekends'] ?? false;
        $countPublicHolidays = $weekendHolidayMode['count_public_holidays'] ?? false;

        $totalDays = 0;
        $breakdown = [
            'total_calendar_days' => 0,
            'weekends_excluded' => 0,
            'holidays_excluded' => 0,
            'working_days' => 0,
        ];

        if ($granularity === 'hourly') {
            // For hourly leaves, calculate based on working hours
            $hours = $start->diffInHours($end);
            $workingHoursPerDay = 8; // Configurable
            $totalDays = round($hours / $workingHoursPerDay, 2);
            $breakdown['hours'] = $hours;
            $breakdown['working_days'] = $totalDays;

            return [
                'days_equivalent' => $totalDays,
                'breakdown' => $breakdown,
            ];
        }

        if ($granularity === 'half_day') {
            return [
                'days_equivalent' => 0.5,
                'breakdown' => [
                    'total_calendar_days' => 1,
                    'working_days' => 0.5,
                ],
            ];
        }

        // Full day calculation
        $period = CarbonPeriod::create($start->startOfDay(), $end->startOfDay());
        $holidays = PublicHoliday::getHolidaysBetween(
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $employee->company_id,
            $employee->branch_id
        )->pluck('date')->map(fn($d) => $d->format('Y-m-d'))->toArray();

        foreach ($period as $date) {
            $breakdown['total_calendar_days']++;

            $isWeekend = $date->isWeekend();
            $isHoliday = in_array($date->format('Y-m-d'), $holidays);

            if ($isWeekend && !$countWeekends) {
                $breakdown['weekends_excluded']++;
                continue;
            }

            if ($isHoliday && !$countPublicHolidays) {
                $breakdown['holidays_excluded']++;
                continue;
            }

            $totalDays++;
        }

        $breakdown['working_days'] = $totalDays;

        return [
            'days_equivalent' => $totalDays,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Check if document is required
     */
    public function mustProvideDocument(LeaveType $leaveType, float $totalDays): bool
    {
        if (!$leaveType->doc_required_after_days) {
            return false;
        }

        return $totalDays > $leaveType->doc_required_after_days;
    }

    /**
     * Validate notice period
     */
    public function validateNoticePeriod(LeaveType $leaveType, Carbon $requestDate, Carbon $leaveStartDate): array
    {
        if (!$leaveType->notice_days || $leaveType->notice_days === 0) {
            return ['valid' => true];
        }

        $daysNotice = $requestDate->diffInDays($leaveStartDate, false);

        if ($daysNotice < $leaveType->notice_days) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'This leave type requires %d days notice. You provided %d days.',
                    $leaveType->notice_days,
                    $daysNotice
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate maximum consecutive days
     */
    public function validateMaxConsecutiveDays(LeaveType $leaveType, float $totalDays): array
    {
        if (!$leaveType->max_consecutive_days) {
            return ['valid' => true];
        }

        if ($totalDays > $leaveType->max_consecutive_days) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Maximum consecutive days for this leave type is %d. You requested %.1f days.',
                    $leaveType->max_consecutive_days,
                    $totalDays
                ),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Validate minimum duration
     */
    public function validateMinDuration(LeaveType $leaveType, Carbon $start, Carbon $end): array
    {
        if (!$leaveType->min_duration_hours || $leaveType->min_duration_hours === 0) {
            return ['valid' => true];
        }

        $hours = $start->diffInHours($end);

        if ($hours < $leaveType->min_duration_hours) {
            return [
                'valid' => false,
                'error' => sprintf(
                    'Minimum duration for this leave type is %d hours. You requested %d hours.',
                    $leaveType->min_duration_hours,
                    $hours
                ),
            ];
        }

        return ['valid' => true];
    }
}

