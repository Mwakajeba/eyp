<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Hr\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedLeaveTypes($company);
        }
    }

    /**
     * Seed default leave types for Tanzania
     */
    protected function seedLeaveTypes(Company $company): void
    {
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'AL',
                'description' => 'Annual leave entitlement as per Tanzania Employment and Labour Relations Act',
                'is_paid' => true,
                'allow_half_day' => true,
                'allow_hourly' => false,
                'allow_negative' => false,
                'min_duration_hours' => 4,
                'max_consecutive_days' => 28,
                'notice_days' => 7,
                'doc_required_after_days' => null,
                'encashable' => true,
                'carryover_cap_days' => 7,
                'carryover_expiry_date' => now()->endOfYear()->addMonths(3)->format('Y-m-d'), // March 31st
                'weekend_holiday_mode' => [
                    'count_weekends' => false,
                    'count_public_holidays' => false,
                ],
                'eligibility' => null,
                'is_active' => true,
                'annual_entitlement' => 28, // 28 days per year (Tanzania standard)
                'accrual_type' => 'monthly',
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'description' => 'Sick leave for medical conditions',
                'is_paid' => true,
                'allow_half_day' => true,
                'allow_hourly' => false,
                'allow_negative' => false,
                'min_duration_hours' => 4,
                'max_consecutive_days' => null,
                'notice_days' => 0,
                'doc_required_after_days' => 3, // Medical certificate required after 3 days
                'encashable' => false,
                'carryover_cap_days' => null,
                'carryover_expiry_date' => null,
                'weekend_holiday_mode' => [
                    'count_weekends' => true, // Sick days count weekends
                    'count_public_holidays' => true,
                ],
                'eligibility' => null,
                'is_active' => true,
                'annual_entitlement' => 10, // 10 days per year
                'accrual_type' => 'annual',
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'description' => 'Maternity leave for female employees',
                'is_paid' => true,
                'allow_half_day' => false,
                'allow_hourly' => false,
                'allow_negative' => false,
                'min_duration_hours' => 0,
                'max_consecutive_days' => 84, // 12 weeks (Tanzania standard)
                'notice_days' => 30,
                'doc_required_after_days' => 0, // Medical certificate always required
                'encashable' => false,
                'carryover_cap_days' => null,
                'carryover_expiry_date' => null,
                'weekend_holiday_mode' => [
                    'count_weekends' => true,
                    'count_public_holidays' => true,
                ],
                'eligibility' => [
                    'gender' => 'female',
                ],
                'is_active' => true,
                'annual_entitlement' => 84, // 12 weeks
                'accrual_type' => 'none', // Not accrued, granted when needed
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PL',
                'description' => 'Paternity leave for male employees',
                'is_paid' => true,
                'allow_half_day' => false,
                'allow_hourly' => false,
                'allow_negative' => false,
                'min_duration_hours' => 0,
                'max_consecutive_days' => 7, // 7 days (Tanzania standard)
                'notice_days' => 7,
                'doc_required_after_days' => 0,
                'encashable' => false,
                'carryover_cap_days' => null,
                'carryover_expiry_date' => null,
                'weekend_holiday_mode' => [
                    'count_weekends' => true,
                    'count_public_holidays' => true,
                ],
                'eligibility' => [
                    'gender' => 'male',
                ],
                'is_active' => true,
                'annual_entitlement' => 7,
                'accrual_type' => 'none',
            ],
            [
                'name' => 'Compassionate Leave',
                'code' => 'CL',
                'description' => 'Compassionate leave for family emergencies or bereavement',
                'is_paid' => true,
                'allow_half_day' => false,
                'allow_hourly' => false,
                'allow_negative' => false,
                'min_duration_hours' => 0,
                'max_consecutive_days' => 5,
                'notice_days' => 0, // Emergency leave
                'doc_required_after_days' => null,
                'encashable' => false,
                'carryover_cap_days' => null,
                'carryover_expiry_date' => null,
                'weekend_holiday_mode' => [
                    'count_weekends' => false,
                    'count_public_holidays' => false,
                ],
                'eligibility' => null,
                'is_active' => true,
                'annual_entitlement' => 5,
                'accrual_type' => 'annual',
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'UL',
                'description' => 'Unpaid leave for personal reasons',
                'is_paid' => false,
                'allow_half_day' => true,
                'allow_hourly' => false,
                'allow_negative' => true, // Can go negative
                'min_duration_hours' => 4,
                'max_consecutive_days' => null,
                'notice_days' => 14,
                'doc_required_after_days' => null,
                'encashable' => false,
                'carryover_cap_days' => null,
                'carryover_expiry_date' => null,
                'weekend_holiday_mode' => [
                    'count_weekends' => false,
                    'count_public_holidays' => false,
                ],
                'eligibility' => null,
                'is_active' => true,
                'annual_entitlement' => 0, // Unlimited but unpaid
                'accrual_type' => 'none',
            ],
            [
                'name' => 'Study Leave',
                'code' => 'STL',
                'description' => 'Leave for educational purposes or examinations',
                'is_paid' => true,
                'allow_half_day' => true,
                'allow_hourly' => false,
                'allow_negative' => false,
                'min_duration_hours' => 4,
                'max_consecutive_days' => 10,
                'notice_days' => 14,
                'doc_required_after_days' => 0, // Proof of study required
                'encashable' => false,
                'carryover_cap_days' => null,
                'carryover_expiry_date' => null,
                'weekend_holiday_mode' => [
                    'count_weekends' => false,
                    'count_public_holidays' => false,
                ],
                'eligibility' => [
                    'tenure_months' => 6, // At least 6 months of service
                ],
                'is_active' => true,
                'annual_entitlement' => 10,
                'accrual_type' => 'annual',
            ],
        ];

        foreach ($leaveTypes as $leaveTypeData) {
            LeaveType::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'code' => $leaveTypeData['code'],
                ],
                $leaveTypeData
            );
        }
    }
}

