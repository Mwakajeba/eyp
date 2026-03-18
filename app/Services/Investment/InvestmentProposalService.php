<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentProposal;
use App\Models\Investment\InvestmentApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvestmentProposalService
{
    /**
     * Create a new investment proposal
     */
    public function create(array $data, User $user): InvestmentProposal
    {
        DB::beginTransaction();
        try {
            $data['company_id'] = $user->company_id;
            $data['branch_id'] = $data['branch_id'] ?? $user->branch_id ?? session('branch_id');
            $data['proposal_number'] = InvestmentProposal::generateProposalNumber($user->company_id);
            $data['status'] = 'DRAFT';
            $data['current_approval_level'] = 1;
            $data['is_fully_approved'] = false;
            $data['created_by'] = $user->id;
            $data['recommended_by'] = $data['recommended_by'] ?? $user->id;

            $proposal = InvestmentProposal::create($data);

            DB::commit();
            return $proposal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create investment proposal', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an investment proposal
     */
    public function update(InvestmentProposal $proposal, array $data, User $user): InvestmentProposal
    {
        // Only allow updates if proposal is in draft or rejected status
        if (!in_array($proposal->status, ['DRAFT', 'REJECTED'])) {
            throw new \Exception('Cannot update proposal that is not in DRAFT or REJECTED status');
        }

        DB::beginTransaction();
        try {
            $data['updated_by'] = $user->id;
            
            // If status was REJECTED and being updated, reset to DRAFT
            if ($proposal->status === 'REJECTED') {
                $data['status'] = 'DRAFT';
                $data['rejected_by'] = null;
                $data['rejected_at'] = null;
                $data['rejection_reason'] = null;
                $data['current_approval_level'] = 1;
            }

            $proposal->update($data);

            DB::commit();
            return $proposal->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update investment proposal', [
                'proposal_id' => $proposal->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Submit proposal for approval
     */
    public function submitForApproval(InvestmentProposal $proposal, User $user): array
    {
        if ($proposal->status !== 'DRAFT') {
            throw new \Exception('Only DRAFT proposals can be submitted for approval');
        }

        DB::beginTransaction();
        try {
            $proposal->status = 'SUBMITTED';
            $proposal->current_approval_level = 1;
            $proposal->updated_by = $user->id;
            $proposal->save();

            // Initialize approval workflow
            $approvalService = app(InvestmentApprovalService::class);
            $approvalService->initializeApprovalWorkflow($proposal);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Proposal submitted for approval',
                'proposal' => $proposal->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit proposal for approval', [
                'proposal_id' => $proposal->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Convert approved proposal to investment master
     */
    public function convertToInvestment(InvestmentProposal $proposal, User $user): array
    {
        if (!$proposal->canBeConverted()) {
            throw new \Exception('Proposal must be fully approved before conversion');
        }

        DB::beginTransaction();
        try {
            $masterService = app(InvestmentMasterService::class);
            
            // Create investment master from proposal - map all available fields
            $investmentData = [
                'company_id' => $proposal->company_id,
                'branch_id' => $proposal->branch_id,
                'instrument_type' => $proposal->instrument_type,
                'issuer' => $proposal->issuer,
                'nominal_amount' => $proposal->proposed_amount, // Map proposed_amount to nominal_amount
                'accounting_class' => $proposal->proposed_accounting_class,
                'currency' => 'TZS', // Default currency
                'day_count' => 'ACT/365', // Default day count convention
                'status' => 'DRAFT', // Will be activated when trade is captured
                'created_by' => $user->id,
            ];

            // Map coupon rate from expected yield if available (for bonds)
            if ($proposal->expected_yield && in_array($proposal->instrument_type, ['T_BOND', 'CORP_BOND', 'FIXED_DEPOSIT'])) {
                // Use expected_yield as initial coupon rate (convert from percentage to decimal)
                $investmentData['coupon_rate'] = $proposal->expected_yield / 100;
            }

            // Calculate maturity date from tenor_days if available
            if ($proposal->tenor_days) {
                // Use today as base date (will be updated when purchase trade is captured)
                $investmentData['maturity_date'] = now()->addDays($proposal->tenor_days);
            }

            // Set default coupon frequency for bonds
            if (in_array($proposal->instrument_type, ['T_BOND', 'CORP_BOND'])) {
                $investmentData['coupon_freq'] = 2; // Default to semi-annual for bonds
            }

            // Generate instrument code
            $investmentData['instrument_code'] = $this->generateInstrumentCode($proposal->company_id, $proposal->instrument_type);

            $investment = $masterService->create($investmentData, $user);

            // Update proposal
            $proposal->converted_to_investment_id = $investment->id;
            $proposal->converted_at = now();
            $proposal->updated_by = $user->id;
            $proposal->save();

            DB::commit();

            return [
                'success' => true,
                'message' => 'Proposal converted to investment',
                'investment' => $investment,
                'proposal' => $proposal->fresh(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to convert proposal to investment', [
                'proposal_id' => $proposal->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate unique instrument code
     */
    protected function generateInstrumentCode(int $companyId, string $instrumentType): string
    {
        $prefix = match($instrumentType) {
            'T_BILL' => 'TB',
            'T_BOND' => 'TBN',
            'FIXED_DEPOSIT' => 'FD',
            'CORP_BOND' => 'CB',
            'EQUITY' => 'EQ',
            'MMF' => 'MMF',
            default => 'INV',
        };

        $year = date('Y');
        $count = \App\Models\Investment\InvestmentMaster::where('company_id', $companyId)
            ->where('instrument_type', $instrumentType)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return $prefix . '-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}

