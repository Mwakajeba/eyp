<?php

namespace App\Http\Controllers\Intangible;

use App\Http\Controllers\Controller;
use App\Models\Intangible\IntangibleAsset;
use App\Models\Intangible\IntangibleImpairment;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IntangibleImpairmentController extends Controller
{
    /**
     * Show impairment creation form.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assets = IntangibleAsset::where('company_id', $user->company_id)
            ->forBranch($branchId)
            ->orderBy('name')
            ->get();

        // Ensure NBV is calculated for each asset
        foreach ($assets as $asset) {
            if ($asset->nbv === null || $asset->nbv === '') {
                $asset->recalculateNbv();
            }
        }

        $selectedAssetId = $request->input('asset_id');

        return view('intangible.impairment.create', compact('assets', 'selectedAssetId'));
    }

    /**
     * Store an impairment test/result and post to GL.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'intangible_asset_id' => 'required|exists:intangible_assets,id',
            'impairment_date' => 'required|date',
            'method' => 'required|in:value_in_use,fair_value_less_costs',
            'recoverable_amount' => 'required|numeric|min:0',
            'assumptions' => 'nullable|string',
        ]);

        $asset = IntangibleAsset::where('company_id', $user->company_id)
            ->forBranch($branchId)
            ->findOrFail($validated['intangible_asset_id']);

        $carryingAmountBefore = (float) $asset->nbv;
        $recoverableAmount = (float) $validated['recoverable_amount'];

        if ($carryingAmountBefore <= $recoverableAmount) {
            $message = sprintf(
                'No impairment to recognise: carrying amount (NBV) is %s and recoverable amount is %s. Recoverable amount must be lower than carrying amount to post an impairment loss.',
                number_format($carryingAmountBefore, 2),
                number_format($recoverableAmount, 2)
            );

            return back()
                ->withInput()
                ->withErrors(['recoverable_amount' => $message]);
        }

        $impairmentLoss = $carryingAmountBefore - $recoverableAmount;

        $category = $asset->category;
        if (!$category || !$category->impairment_loss_account_id || !$category->accumulated_impairment_account_id) {
            return back()
                ->withInput()
                ->withErrors(['intangible_asset_id' => 'Selected asset category is missing impairment loss or accumulated impairment account mappings.']);
        }

        DB::beginTransaction();
        try {
            $journal = Journal::create([
                'branch_id' => $branchId,
                'date' => $validated['impairment_date'],
                'reference' => 'IIM-' . $asset->code . '-' . date('Ymd', strtotime($validated['impairment_date'])),
                'reference_type' => 'Intangible Impairment',
                'description' => "Impairment loss for intangible asset {$asset->name} ({$asset->code})",
                'user_id' => $user->id,
            ]);

            // Dr Impairment Loss (P&L)
            $debitItem = JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $category->impairment_loss_account_id,
                'amount' => $impairmentLoss,
                'nature' => 'debit',
                'description' => 'Intangible impairment loss',
            ]);

            // Cr Accumulated Impairment
            $creditItem = JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $category->accumulated_impairment_account_id,
                'amount' => $impairmentLoss,
                'nature' => 'credit',
                'description' => 'Intangible impairment loss',
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

            // Create impairment record
            $impairment = IntangibleImpairment::create([
                'intangible_asset_id' => $asset->id,
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'impairment_date' => $validated['impairment_date'],
                'carrying_amount_before' => $carryingAmountBefore,
                'recoverable_amount' => $recoverableAmount,
                'impairment_loss' => $impairmentLoss,
                'method' => $validated['method'],
                'assumptions' => $validated['assumptions'] ?? null,
                'is_reversal' => false,
                'status' => 'posted',
                'journal_id' => $journal->id,
                'gl_posted' => true,
            ]);

            // Update asset
            $asset->accumulated_impairment += $impairmentLoss;
            $asset->recalculateNbv();
            if ($asset->nbv <= 0.0) {
                $asset->status = 'impaired';
            }
            $asset->save();

            DB::commit();

            return redirect()
                ->route('assets.intangible.index')
                ->with('success', "Impairment of {$impairmentLoss} recorded for asset {$asset->code}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Intangible impairment failed', [
                'asset_id' => $asset->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['intangible_asset_id' => 'Failed to record impairment: ' . $e->getMessage()]);
        }
    }
}


