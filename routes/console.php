<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monthly asset depreciation processing
Schedule::command('assets:process-depreciation')
    ->monthlyOn(config('app.depreciation_schedule_day', 1), '00:00')
    ->description('Process monthly asset depreciation');

// Schedule monthly loan interest accrual (runs on the 1st of each month for previous month)
Schedule::command('loans:accrue-interest')
    ->monthlyOn(1, '02:00')
    ->description('Accrue interest for all active loans (previous month)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule FX revaluation auto-reversal (runs on the 1st of each month at 00:00)
Schedule::command('fx:auto-reverse')
    ->monthlyOn(1, '00:00')
    ->description('Auto-reverse previous month\'s FX revaluation entries')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule month-end FX revaluation processing (runs on the 1st of each month at 00:05)
// This will reverse previous month's revaluation and create new revaluation for previous month-end
Schedule::command('fx:process-month-end-revaluation')
    ->monthlyOn(1, '00:05')
    ->description('Process month-end FX revaluation (reverses previous month and creates new revaluation)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule monthly investment interest accrual (runs on the 1st of each month for previous month)
Schedule::command('investments:accrue-interest')
    ->monthlyOn(1, '03:00')
    ->description('Accrue interest for all active investments (previous month)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule monthly investment amortization processing (runs on the 1st of each month)
Schedule::command('investments:process-amortization')
    ->monthlyOn(1, '01:00')
    ->description('Generate or update amortization schedules for investments')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule monthly ECL recalculation (runs on the 1st of each month at 04:00)
Schedule::command('investments:recalculate-ecl')
    ->monthlyOn(1, '04:00')
    ->description('Recalculate Expected Credit Loss (ECL) for all investments with forward-looking information')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule daily fair value updates (runs daily at 06:00)
Schedule::command('investments:update-fair-values')
    ->dailyAt('06:00')
    ->description('Update fair values for investments from market feeds')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule daily investment valuations (runs daily at 07:00)
Schedule::command('investments:process-daily-valuations')
    ->dailyAt('07:00')
    ->description('Process daily valuations for FVPL and FVOCI investments')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule purchase approval reminders (runs every 12 hours at 8:00 and 20:00)
Schedule::command('purchase:send-approval-reminders')
    ->twiceDaily(8, 20)
    ->description('Send automated reminders for pending purchase requisitions and purchase orders')
    ->withoutOverlapping();

// Schedule accrual auto-posting (runs on the 1st of each month at 00:00 to post previous month's accruals)
Schedule::command('accruals:auto-post')
    ->monthlyOn(1, '00:00')
    ->description('Auto-post pending accrual journals at month-end (IFRS compliant)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule accrual auto-reversal (runs on the 1st of each month at 00:30)
Schedule::command('accruals:auto-reverse')
    ->monthlyOn(1, '00:30')
    ->description('Auto-reverse previous month\'s accrual entries (IFRS compliant)')
    ->withoutOverlapping()
    ->onOneServer();

// Schedule biometric device sync (runs every minute)
// Pulls attendance logs from ZKTeco devices and creates attendance records
Schedule::command('biometric:sync')
    ->everyMinute()
    ->description('Sync attendance logs from biometric devices and update attendance')
    ->withoutOverlapping()
    ->runInBackground();

// Schedule deletion of expired online bookings (runs every hour)
Schedule::command('bookings:cancel-expired-online')
    ->hourly()
    ->description('Delete online bookings that are older than 2 hours and not confirmed')
    ->withoutOverlapping();
