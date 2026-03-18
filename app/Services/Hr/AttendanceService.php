<?php

namespace App\Services\Hr;

use App\Models\Hr\Attendance;
use App\Models\Hr\Employee;
use App\Models\Hr\EmployeeSchedule;
use App\Models\Hr\WorkSchedule;
use App\Models\Hr\Shift;
use App\Models\Hr\OvertimeRule;
use App\Models\Hr\HolidayCalendar;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Calculate attendance hours from clock in/out times
     */
    public function calculateHours($clockIn, $clockOut, $crossesMidnight = false): ?float
    {
        if (!$clockIn || !$clockOut) {
            return null;
        }

        $start = Carbon::parse($clockIn);
        $end = Carbon::parse($clockOut);

        if ($crossesMidnight && $end->lt($start)) {
            $end->addDay();
        }

        $diffInMinutes = $start->diffInMinutes($end);
        return round($diffInMinutes / 60, 2);
    }

    /**
     * Get employee's schedule for a specific date
     */
    public function getEmployeeScheduleForDate(Employee $employee, Carbon $date): ?EmployeeSchedule
    {
        return EmployeeSchedule::where('employee_id', $employee->id)
            ->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->latest('effective_date')
            ->first();
    }

    /**
     * Get expected hours for employee on a specific date
     */
    public function getExpectedHours(Employee $employee, Carbon $date): ?float
    {
        $schedule = $this->getEmployeeScheduleForDate($employee, $date);

        if (!$schedule || !$schedule->schedule) {
            return null;
        }

        $workSchedule = $schedule->schedule;
        $dayOfWeek = strtolower($date->format('l')); // monday, tuesday, etc.

        // Check if this day is a working day
        $weeklyPattern = $workSchedule->weekly_pattern ?? [];
        if (!isset($weeklyPattern[$dayOfWeek]) || !$weeklyPattern[$dayOfWeek]) {
            return 0; // Not a working day
        }

        return $workSchedule->standard_daily_hours;
    }

    /**
     * Check if date is a holiday
     */
    public function isHoliday(Carbon $date, $companyId, $branchId = null): bool
    {
        $calendars = HolidayCalendar::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) use ($branchId) {
                if ($branchId) {
                    $q->whereNull('branch_id')
                        ->orWhere('branch_id', $branchId);
                } else {
                    $q->whereNull('branch_id');
                }
            })
            ->get();

        foreach ($calendars as $calendar) {
            if ($calendar->isHoliday($date->format('Y-m-d'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine day type (weekday, weekend, holiday)
     */
    public function getDayType(Carbon $date, $companyId, $branchId = null): string
    {
        if ($this->isHoliday($date, $companyId, $branchId)) {
            return 'holiday';
        }

        $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 6 = Saturday
        return ($dayOfWeek == 0 || $dayOfWeek == 6) ? 'weekend' : 'weekday';
    }

    /**
     * Get overtime rate for employee on a specific date
     */
    public function getOvertimeRate(Employee $employee, Carbon $date, $companyId): float
    {
        $dayType = $this->getDayType($date, $companyId, $employee->branch_id);

        // Get employee's grade
        $gradeId = null;
        if ($employee->position && $employee->position->grade_id) {
            $gradeId = $employee->position->grade_id;
        }

        // Find matching overtime rule
        $rule = OvertimeRule::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('day_type', $dayType)
            ->where(function ($q) use ($gradeId) {
                $q->whereNull('grade_id')
                    ->orWhere('grade_id', $gradeId);
            })
            ->orderBy('grade_id', 'desc') // Prefer grade-specific rules
            ->first();

        return $rule ? $rule->overtime_rate : 1.50; // Default 1.5x
    }

    /**
     * Calculate normal and overtime hours
     */
    public function calculateNormalAndOvertimeHours(
        float $actualHours,
        float $expectedHours,
        bool $overtimeEligible = true
    ): array {
        if (!$overtimeEligible || $actualHours <= $expectedHours) {
            return [
                'normal' => min($actualHours, $expectedHours),
                'overtime' => 0
            ];
        }

        return [
            'normal' => $expectedHours,
            'overtime' => $actualHours - $expectedHours
        ];
    }

    /**
     * Calculate late minutes
     */
    public function calculateLateMinutes($clockIn, $expectedStartTime, $gracePeriodMinutes = 0): int
    {
        if (!$clockIn || !$expectedStartTime) {
            return 0;
        }

        $clockInTime = Carbon::parse($clockIn);
        $expectedTime = Carbon::parse($expectedStartTime);
        $expectedTime->addMinutes($gracePeriodMinutes);

        if ($clockInTime->gt($expectedTime)) {
            return $clockInTime->diffInMinutes($expectedTime);
        }

        return 0;
    }

    /**
     * Calculate early exit minutes
     */
    public function calculateEarlyExitMinutes($clockOut, $expectedEndTime, $gracePeriodMinutes = 0): int
    {
        if (!$clockOut || !$expectedEndTime) {
            return 0;
        }

        $clockOutTime = Carbon::parse($clockOut);
        $expectedTime = Carbon::parse($expectedEndTime);
        $expectedTime->subMinutes($gracePeriodMinutes);

        if ($clockOutTime->lt($expectedTime)) {
            return $expectedTime->diffInMinutes($clockOutTime);
        }

        return 0;
    }

    /**
     * Process attendance record - calculate all fields automatically
     */
    public function processAttendance(Attendance $attendance): Attendance
    {
        $employee = $attendance->employee;
        $date = Carbon::parse($attendance->attendance_date);
        $companyId = $employee->company_id;

        // Get employee schedule
        $employeeSchedule = $this->getEmployeeScheduleForDate($employee, $date);

        if ($employeeSchedule) {
            if ($employeeSchedule->schedule) {
                $attendance->schedule_id = $employeeSchedule->schedule_id;
            }
            if ($employeeSchedule->shift) {
                $attendance->shift_id = $employeeSchedule->shift_id;
            }
        }

        // Calculate expected hours
        $expectedHours = $this->getExpectedHours($employee, $date);
        if ($expectedHours !== null) {
            $attendance->expected_hours = $expectedHours;
        }

        $shift = $attendance->shift;
        $crossesMidnight = $shift ? $shift->crosses_midnight : false;

        // Calculate actual hours from clock in/out (regular shift)
        $regularHours = 0;
        if ($attendance->clock_in && $attendance->clock_out) {
            $regularHours = $this->calculateHours($attendance->clock_in, $attendance->clock_out, $crossesMidnight);
        }

        // Calculate break minutes if break punches exist
        if ($attendance->break_start && $attendance->break_end) {
            $breakMinutes = $this->calculateBreakMinutes($attendance->break_start, $attendance->break_end);
            $attendance->break_minutes = $breakMinutes;
            // Subtract break time from regular hours
            $regularHours = max(0, $regularHours - ($breakMinutes / 60));
        }

        // Calculate dedicated overtime hours from overtime clock in/out
        $dedicatedOvertimeHours = 0;
        if ($attendance->overtime_clock_in && $attendance->overtime_clock_out) {
            $dedicatedOvertimeHours = $this->calculateHours(
                $attendance->overtime_clock_in,
                $attendance->overtime_clock_out,
                $crossesMidnight
            );
        }

        // Set actual hours (regular hours only, overtime tracked separately)
        $attendance->actual_hours = round($regularHours, 2);

        // Calculate normal and excess overtime hours from regular shift
        $workSchedule = $attendance->schedule;
        $overtimeEligible = $workSchedule ? $workSchedule->overtime_eligible : true;

        $hours = $this->calculateNormalAndOvertimeHours(
            $attendance->actual_hours ?? 0,
            $attendance->expected_hours ?? 0,
            $overtimeEligible
        );
        $attendance->normal_hours = $hours['normal'];

        // Total overtime = excess from regular shift + dedicated overtime punches
        $totalOvertimeHours = $hours['overtime'] + $dedicatedOvertimeHours;
        $attendance->overtime_hours = round($totalOvertimeHours, 2);

        // Calculate late minutes
        if ($shift && $attendance->clock_in) {
            $lateMinutes = $this->calculateLateMinutes(
                $attendance->clock_in,
                $shift->start_time,
                0 // grace period - can be configured
            );
            $attendance->late_minutes = $lateMinutes;

            if ($lateMinutes > 0 && $attendance->status == 'present') {
                $attendance->status = 'late';
                $attendance->exception_type = 'late';
            }
        }

        // Calculate early exit minutes
        if ($shift && $attendance->clock_out) {
            $earlyExitMinutes = $this->calculateEarlyExitMinutes(
                $attendance->clock_out,
                $shift->end_time,
                0 // grace period
            );
            $attendance->early_exit_minutes = $earlyExitMinutes;

            if ($earlyExitMinutes > 0 && $attendance->status == 'present') {
                $attendance->status = 'early_exit';
                $attendance->exception_type = 'early_exit';
            }
        }

        // Determine status if not set
        if (!$attendance->status) {
            if ($attendance->clock_in && $attendance->clock_out) {
                $attendance->status = 'present';
            } elseif ($attendance->clock_in || $attendance->clock_out) {
                // Has at least one punch - mark as present but with missing punch exception
                $attendance->status = 'present';
                $attendance->exception_type = 'missing_punch';
                if (!$attendance->clock_in) {
                    $attendance->exception_reason = 'Missing clock in';
                } elseif (!$attendance->clock_out) {
                    $attendance->exception_reason = 'Missing clock out';
                }
            } else {
                $attendance->status = 'absent';
                $attendance->exception_type = 'absent';
            }
        }

        return $attendance;
    }

    /**
     * Calculate break minutes from break start/end times
     */
    public function calculateBreakMinutes($breakStart, $breakEnd): int
    {
        if (!$breakStart || !$breakEnd) {
            return 0;
        }

        $start = Carbon::parse($breakStart);
        $end = Carbon::parse($breakEnd);

        if ($end->lt($start)) {
            return 0; // Invalid break - end before start
        }

        return $start->diffInMinutes($end);
    }

    /**
     * Get attendance summary for employee in date range
     */
    public function getAttendanceSummary(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->where('is_approved', true)
            ->get();

        return [
            'total_days' => $attendances->count(),
            'present_days' => $attendances->where('status', 'present')->count(),
            'absent_days' => $attendances->where('status', 'absent')->count(),
            'late_days' => $attendances->where('status', 'late')->count(),
            'total_hours' => $attendances->sum('normal_hours'),
            'total_overtime_hours' => $attendances->sum('overtime_hours'),
            'total_late_minutes' => $attendances->sum('late_minutes'),
        ];
    }

    /**
     * Get approved overtime hours for employee in date range
     */
    public function getApprovedOvertimeHours(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        // Sum overtime hours from the lines table since overtime_hours was moved there
        return \App\Models\Hr\OvertimeRequestLine::whereHas('overtimeRequest', function ($query) use ($employee, $startDate, $endDate) {
            $query->where('employee_id', $employee->id)
                ->whereBetween('overtime_date', [$startDate, $endDate])
                ->where('status', 'approved');
        })
            ->sum('overtime_hours');
    }
}
