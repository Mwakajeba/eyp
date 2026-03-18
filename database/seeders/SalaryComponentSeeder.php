<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hr\SalaryComponent;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class SalaryComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command->warn('No companies found. Please create a company first.');
            return;
        }

        foreach ($companies as $company) {
            $this->command->info("Seeding salary components for company: {$company->name}");

            // Earnings Components
            $earnings = $this->getEarningsComponents();
            foreach ($earnings as $index => $earning) {
                SalaryComponent::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'component_code' => $earning['component_code'],
                    ],
                    array_merge($earning, [
                        'company_id' => $company->id,
                        'display_order' => $index + 1,
                    ])
                );
            }

            // Deductions Components
            $deductions = $this->getDeductionsComponents();
            foreach ($deductions as $index => $deduction) {
                SalaryComponent::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'component_code' => $deduction['component_code'],
                    ],
                    array_merge($deduction, [
                        'company_id' => $company->id,
                        'display_order' => $index + 1,
                    ])
                );
            }

            $this->command->info("✓ Seeded salary components for {$company->name}");
        }

        $this->command->info('Salary components seeded successfully!');
    }

    /**
     * Get earnings components configuration
     */
    private function getEarningsComponents(): array
    {
        return [
            [
                'component_code' => 'BASIC_SALARY',
                'component_name' => 'Basic Salary',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Core fixed salary amount - the foundation of employee compensation',
                'is_taxable' => true,
                'is_pensionable' => true,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'HOUSE_ALLOWANCE',
                'component_name' => 'House Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for housing expenses',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'TRANSPORT_ALLOWANCE',
                'component_name' => 'Transport Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for transportation costs',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'RESPONSIBILITY_ALLOWANCE',
                'component_name' => 'Responsibility Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Additional pay for additional responsibilities',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_PERCENTAGE,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'ACTING_ALLOWANCE',
                'component_name' => 'Acting Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Pay for acting in a higher position',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_PERCENTAGE,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'MEDICAL_ALLOWANCE',
                'component_name' => 'Medical Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for medical expenses',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'MEAL_ALLOWANCE',
                'component_name' => 'Meal Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for meals',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'COMMUNICATION_ALLOWANCE',
                'component_name' => 'Communication Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for phone and internet expenses',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'ENTERTAINMENT_ALLOWANCE',
                'component_name' => 'Entertainment Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for entertainment expenses',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'PERFORMANCE_BONUS',
                'component_name' => 'Performance Bonus',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Bonus based on performance evaluation',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'COMMISSION',
                'component_name' => 'Sales Commission',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Commission based on sales or performance',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_PERCENTAGE,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'OVERTIME_ALLOWANCE',
                'component_name' => 'Overtime Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Pay for overtime work (usually calculated from attendance)',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'LEAVE_ENCASHMENT',
                'component_name' => 'Leave Encashment',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Payment for unused leave days',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FORMULA,
                'calculation_formula' => '{base} / 30 * {amount}',
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'RISK_ALLOWANCE',
                'component_name' => 'Risk Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for high-risk job positions',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'FUEL_ALLOWANCE',
                'component_name' => 'Fuel Allowance',
                'component_type' => SalaryComponent::TYPE_EARNING,
                'description' => 'Allowance for fuel expenses',
                'is_taxable' => true,
                'is_pensionable' => false,
                'is_nhif_applicable' => true,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
        ];
    }

    /**
     * Get deductions components configuration
     */
    private function getDeductionsComponents(): array
    {
        return [
            [
                'component_code' => 'TRADE_UNION',
                'component_name' => 'Trade Union Dues',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Monthly union membership fees',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'SALARY_ADVANCE',
                'component_name' => 'Salary Advance Recovery',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Recovery of advance payments made to employee',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'EXTERNAL_LOAN',
                'component_name' => 'External Loan Repayment',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Repayment of external loans (e.g., bank loans)',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'COURT_ORDER',
                'component_name' => 'Court Order / Garnishment',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Legal deduction (e.g., child support, court orders)',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'WELFARE_FUND',
                'component_name' => 'Staff Welfare Fund',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Contribution to staff welfare fund',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_PERCENTAGE,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'PRIVATE_INSURANCE',
                'component_name' => 'Private Health Insurance',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Private health insurance premium (if not using NHIF)',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'SAVINGS_SCHEME',
                'component_name' => 'Staff Savings Scheme',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Employee savings contribution',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_PERCENTAGE,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'COOPERATIVE',
                'component_name' => 'Cooperative Society Contribution',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Contribution to cooperative society',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'STAFF_LOAN',
                'component_name' => 'Staff Loan Repayment',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Repayment of staff loan from company',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
            [
                'component_code' => 'OTHER_DEDUCTION',
                'component_name' => 'Other Deduction',
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'description' => 'Other miscellaneous deductions',
                'is_taxable' => false,
                'is_pensionable' => false,
                'is_nhif_applicable' => false,
                'calculation_type' => SalaryComponent::CALC_FIXED,
                'calculation_formula' => null,
                'ceiling_amount' => null,
                'floor_amount' => null,
                'is_active' => true,
            ],
        ];
    }
}

