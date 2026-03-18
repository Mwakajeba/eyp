<?php

namespace App\Services\Intangible;

use App\Models\Intangible\IntangibleAsset;
use App\Models\Intangible\IntangibleAmortisation;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IntangibleAmortisationService
{
    /**
     * Run monthly amortisation for all eligible intangible assets for a given date (month).
     *
     * @return array{processed:int, total_amount:string}
     */
    public function runForMonth(\DateTimeInterface $periodDate): array
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $period = $periodDate->format('Y-m-01');

        $processed = 0;
        $totalAmount = 0.0;

        $assets = IntangibleAsset::where('company_id', $companyId)
            ->forBranch($branchId)
            ->where('status', 'active')
            ->where('is_indefinite_life', false)
            ->where('is_goodwill', false)
            ->where('nbv', '>', 0)
            ->whereDate('acquisition_date', '<=', $period)
            ->with('category')
            ->get();

        foreach ($assets as $asset) {
            if (!$asset->useful_life_months || !$asset->category) {
                continue;
            }

            $expenseAccountId = $asset->category->amortisation_expense_account_id;
            $accumAmortAccountId = $asset->category->accumulated_amortisation_account_id;
            if (!$expenseAccountId || !$accumAmortAccountId) {
                continue;
            }

            $monthlyAmount = (float) $asset->cost / (int) $asset->useful_life_months;
            if ($monthlyAmount <= 0) {
                continue;
            }

            // Do not amortise beyond NBV 0
            $amount = min($monthlyAmount, (float) $asset->nbv);

            if ($amount <= 0) {
                continue;
            }

            DB::beginTransaction();
            try {
                $journal = Journal::create([
                    'branch_id' => $branchId,
                    'date' => $period,
                    'reference' => 'IAM-' . $asset->code . '-' . date('Ym', strtotime($period)),
                    'reference_type' => 'Intangible Amortisation',
                    'description' => "Amortisation for intangible asset {$asset->name} ({$asset->code})",
                    'user_id' => $user->id,
                ]);

                // Dr Amortisation Expense
                $debitItem = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $expenseAccountId,
                    'amount' => $amount,
                    'nature' => 'debit',
                    'description' => 'Intangible amortisation',
                ]);

                // Cr Accumulated Amortisation
                $creditItem = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $accumAmortAccountId,
                    'amount' => $amount,
                    'nature' => 'credit',
                    'description' => 'Intangible amortisation',
                ]);

                // GL transactions
                foreach ([$debitItem, $creditItem] as $item) {
                    GlTransaction::create([
                        'chart_account_id' => $item->chart_account_id,
                        'amount' => $item->amount,
                        'nature' => $item->nature,
                        'transaction_id' => $journal->id,
                        'transaction_type' => 'journal',
                        'date' => $journal->date,
                        'description' => $item->description,
                        'branch_id' => $branchId,
                        'user_id' => $user->id,
                    ]);
                }

                // Update asset balances
                $asset->accumulated_amortisation += $amount;
                $asset->recalculateNbv();
                if ($asset->nbv <= 0.0) {
                    $asset->status = 'fully_amortised';
                }
                $asset->save();

                // Record amortisation history
                IntangibleAmortisation::create([
                    'intangible_asset_id' => $asset->id,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'amortisation_date' => $period,
                    'amount' => $amount,
                    'accumulated_amortisation_after' => $asset->accumulated_amortisation,
                    'nbv_after' => $asset->nbv,
                    'journal_id' => $journal->id,
                    'gl_posted' => true,
                ]);

                DB::commit();

                $processed++;
                $totalAmount += $amount;
            } catch (\Throwable $e) {
                DB::rollBack();
                // Log and continue with next asset
                \Log::error('Intangible amortisation failed', [
                    'asset_id' => $asset->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'total_amount' => number_format($totalAmount, 2),
        ];
    }
}


