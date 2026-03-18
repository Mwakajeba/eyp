<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentValuation;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvestmentRevaluationService
{

    /**
     * Process revaluation for an investment based on valuation
     * Handles FVPL and FVOCI accounting per IFRS 9
     * 
     * @param InvestmentValuation $valuation
     * @param User $user
     * @return Journal
     */
    public function processRevaluation(InvestmentValuation $valuation, User $user): Journal
    {
        DB::beginTransaction();
        try {
            $investment = $valuation->investment;
            
            // Calculate gain/loss
            $gainLoss = $valuation->total_fair_value - $valuation->carrying_amount_before;
            
            // Update valuation with calculated values
            $valuation->unrealized_gain_loss = $gainLoss;
            $valuation->carrying_amount_after = $valuation->total_fair_value;
            
            // Generate journal based on accounting classification
            $journal = null;
            
            switch ($investment->accounting_class) {
                case 'FVPL':
                    $journal = $this->generateFVPLJournal($valuation, $gainLoss, $user);
                    break;
                    
                case 'FVOCI':
                    $journal = $this->generateFVOCIJournal($valuation, $gainLoss, $user);
                    break;
                    
                case 'AMORTISED_COST':
                    // Amortized cost investments don't get revalued to fair value
                    // They use EIR amortization instead
                    throw new \Exception("Amortized cost investments should not be revalued to fair value");
                    
                default:
                    throw new \Exception("Invalid accounting classification: {$investment->accounting_class}");
            }
            
            // Update investment carrying amount
            $investment->carrying_amount = $valuation->total_fair_value;
            $investment->updated_by = $user->id;
            $investment->save();
            
            // Update valuation
            $valuation->posted_journal_id = $journal->id;
            $valuation->is_posted = true;
            $valuation->posted_at = Carbon::now();
            $valuation->status = 'POSTED';
            $valuation->updated_by = $user->id;
            $valuation->save();
            
            DB::commit();
            return $journal;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process investment revaluation', [
                'valuation_id' => $valuation->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate journal for FVPL investments
     * Gains/losses go directly to P&L
     */
    protected function generateFVPLJournal(InvestmentValuation $valuation, float $gainLoss, User $user): Journal
    {
        $investment = $valuation->investment;
        $companyId = $investment->company_id;
        $branchId = $investment->branch_id;
        
        // Create journal
        $journal = Journal::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'journal_date' => $valuation->valuation_date,
            'journal_number' => $this->generateJournalNumber($companyId),
            'description' => "Investment Revaluation - {$investment->instrument_code} - {$valuation->valuation_date->format('Y-m-d')}",
            'reference' => "VAL-{$valuation->id}",
            'status' => 'POSTED',
            'created_by' => $user->id,
        ]);
        
        // Get accounts
        $assetAccount = $investment->gl_asset_account;
        $gainLossAccount = $investment->gl_gain_loss_account;
        
        if (!$assetAccount || !$gainLossAccount) {
            throw new \Exception("GL accounts not configured for investment");
        }
        
        if ($gainLoss > 0) {
            // Gain: Dr Asset, Cr Gain/Loss
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $assetAccount,
                'debit' => abs($gainLoss),
                'credit' => 0,
                'description' => "Fair value increase - {$investment->instrument_code}",
            ]);
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $gainLossAccount,
                'debit' => 0,
                'credit' => abs($gainLoss),
                'description' => "Unrealized gain - {$investment->instrument_code}",
            ]);
        } else {
            // Loss: Dr Gain/Loss, Cr Asset
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $gainLossAccount,
                'debit' => abs($gainLoss),
                'credit' => 0,
                'description' => "Unrealized loss - {$investment->instrument_code}",
            ]);
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $assetAccount,
                'debit' => 0,
                'credit' => abs($gainLoss),
                'description' => "Fair value decrease - {$investment->instrument_code}",
            ]);
        }
        
        return $journal;
    }

    /**
     * Generate journal for FVOCI investments
     * Gains/losses go to OCI reserve (not P&L)
     */
    protected function generateFVOCIJournal(InvestmentValuation $valuation, float $gainLoss, User $user): Journal
    {
        $investment = $valuation->investment;
        $companyId = $investment->company_id;
        $branchId = $investment->branch_id;
        
        // Create journal
        $journal = Journal::create([
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'journal_date' => $valuation->valuation_date,
            'journal_number' => $this->generateJournalNumber($companyId),
            'description' => "Investment Revaluation (FVOCI) - {$investment->instrument_code} - {$valuation->valuation_date->format('Y-m-d')}",
            'reference' => "VAL-{$valuation->id}",
            'status' => 'POSTED',
            'created_by' => $user->id,
        ]);
        
        // Get accounts
        $assetAccount = $investment->gl_asset_account;
        $fvociReserveAccount = $investment->gl_fvoci_reserve_account;
        
        if (!$assetAccount || !$fvociReserveAccount) {
            throw new \Exception("GL accounts not configured for FVOCI investment");
        }
        
        // Update FVOCI reserve
        $fvociReserveChange = $gainLoss;
        $investment->fvoci_reserve = ($investment->fvoci_reserve ?? 0) + $fvociReserveChange;
        $valuation->fvoci_reserve_change = $fvociReserveChange;
        
        if ($gainLoss > 0) {
            // Gain: Dr Asset, Cr FVOCI Reserve
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $assetAccount,
                'debit' => abs($gainLoss),
                'credit' => 0,
                'description' => "Fair value increase (FVOCI) - {$investment->instrument_code}",
            ]);
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $fvociReserveAccount,
                'debit' => 0,
                'credit' => abs($gainLoss),
                'description' => "FVOCI reserve increase - {$investment->instrument_code}",
            ]);
        } else {
            // Loss: Dr FVOCI Reserve, Cr Asset
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $fvociReserveAccount,
                'debit' => abs($gainLoss),
                'credit' => 0,
                'description' => "FVOCI reserve decrease - {$investment->instrument_code}",
            ]);
            
            JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $assetAccount,
                'debit' => 0,
                'credit' => abs($gainLoss),
                'description' => "Fair value decrease (FVOCI) - {$investment->instrument_code}",
            ]);
        }
        
        return $journal;
    }

    /**
     * Generate journal number
     */
    protected function generateJournalNumber(int $companyId): string
    {
        $prefix = 'INV-REVAL';
        $year = date('Y');
        $month = date('m');
        
        $lastJournal = Journal::where('company_id', $companyId)
            ->where('journal_number', 'like', "{$prefix}-{$year}{$month}%")
            ->orderBy('journal_number', 'desc')
            ->first();
        
        if ($lastJournal) {
            $lastNumber = (int) substr($lastJournal->journal_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $newNumber);
    }

    /**
     * Preview revaluation without posting
     */
    public function previewRevaluation(InvestmentValuation $valuation): array
    {
        $investment = $valuation->investment;
        $gainLoss = $valuation->total_fair_value - $valuation->carrying_amount_before;
        
        $preview = [
            'investment_code' => $investment->instrument_code,
            'valuation_date' => $valuation->valuation_date->format('Y-m-d'),
            'carrying_amount_before' => $valuation->carrying_amount_before,
            'fair_value' => $valuation->total_fair_value,
            'gain_loss' => $gainLoss,
            'accounting_class' => $investment->accounting_class,
            'journal_entries' => [],
        ];
        
        // Generate preview journal entries
        switch ($investment->accounting_class) {
            case 'FVPL':
                $preview['journal_entries'] = $this->previewFVPLJournal($valuation, $gainLoss);
                break;
                
            case 'FVOCI':
                $preview['journal_entries'] = $this->previewFVOCIJournal($valuation, $gainLoss);
                break;
        }
        
        return $preview;
    }

    protected function previewFVPLJournal(InvestmentValuation $valuation, float $gainLoss): array
    {
        $investment = $valuation->investment;
        
        if ($gainLoss > 0) {
            return [
                [
                    'account' => $investment->gl_asset_account,
                    'debit' => abs($gainLoss),
                    'credit' => 0,
                    'description' => "Fair value increase",
                ],
                [
                    'account' => $investment->gl_gain_loss_account,
                    'debit' => 0,
                    'credit' => abs($gainLoss),
                    'description' => "Unrealized gain",
                ],
            ];
        } else {
            return [
                [
                    'account' => $investment->gl_gain_loss_account,
                    'debit' => abs($gainLoss),
                    'credit' => 0,
                    'description' => "Unrealized loss",
                ],
                [
                    'account' => $investment->gl_asset_account,
                    'debit' => 0,
                    'credit' => abs($gainLoss),
                    'description' => "Fair value decrease",
                ],
            ];
        }
    }

    protected function previewFVOCIJournal(InvestmentValuation $valuation, float $gainLoss): array
    {
        $investment = $valuation->investment;
        
        if ($gainLoss > 0) {
            return [
                [
                    'account' => $investment->gl_asset_account,
                    'debit' => abs($gainLoss),
                    'credit' => 0,
                    'description' => "Fair value increase (FVOCI)",
                ],
                [
                    'account' => $investment->gl_fvoci_reserve_account,
                    'debit' => 0,
                    'credit' => abs($gainLoss),
                    'description' => "FVOCI reserve increase",
                ],
            ];
        } else {
            return [
                [
                    'account' => $investment->gl_fvoci_reserve_account,
                    'debit' => abs($gainLoss),
                    'credit' => 0,
                    'description' => "FVOCI reserve decrease",
                ],
                [
                    'account' => $investment->gl_asset_account,
                    'debit' => 0,
                    'credit' => abs($gainLoss),
                    'description' => "Fair value decrease (FVOCI)",
                ],
            ];
        }
    }
}

