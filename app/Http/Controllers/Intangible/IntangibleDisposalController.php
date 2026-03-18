<?php

namespace App\Http\Controllers\Intangible;

use App\Http\Controllers\Controller;
use App\Models\Intangible\IntangibleAsset;
use App\Models\Intangible\IntangibleDisposal;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IntangibleDisposalController extends Controller
{
    /**
     * Show disposal form.
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $assets = IntangibleAsset::where('company_id', $user->company_id)
            ->forBranch($branchId)
            ->where('status', '!=', 'disposed')
            ->orderBy('name')
            ->get();

        $selectedAssetId = $request->input('asset_id');

        return view('intangible.disposal.create', compact('assets', 'selectedAssetId'));
    }

    /**
     * Process disposal, calculate gain/loss and post to GL.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'intangible_asset_id' => 'required|exists:intangible_assets,id',
            'disposal_date' => 'required|date',
            'proceeds' => 'required|numeric',
            'reason' => 'nullable|string',
        ]);

        $asset = IntangibleAsset::where('company_id', $user->company_id)
            ->forBranch($branchId)
            ->findOrFail($validated['intangible_asset_id']);

        $category = $asset->category;
        if (!$category) {
            return back()
                ->withInput()
                ->withErrors(['intangible_asset_id' => 'Selected asset has no category assigned. Please assign a category with all required GL accounts.']);
        }

        $missingAccounts = [];
        if (!$category->cost_account_id) {
            $missingAccounts[] = 'Intangible Asset – Cost';
        }
        if (!$category->accumulated_amortisation_account_id) {
            $missingAccounts[] = 'Accumulated Amortisation';
        }
        if (!$category->accumulated_impairment_account_id) {
            $missingAccounts[] = 'Accumulated Impairment';
        }
        if (!$category->disposal_gain_loss_account_id) {
            $missingAccounts[] = 'Gain/Loss on Disposal';
        }

        if (!empty($missingAccounts)) {
            $categoryName = $category->name ?? 'Unknown';
            $missingList = implode(', ', $missingAccounts);
            return back()
                ->withInput()
                ->withErrors(['intangible_asset_id' => "Category '{$categoryName}' is missing required GL accounts: {$missingList}. Go to Intangible Assets → Categories → Edit this category and map all accounts in the 'GL Account Mapping' section."]);
        }

        $nbv = (float) $asset->nbv;
        $proceeds = (float) $validated['proceeds'];
        $gainLoss = $proceeds - $nbv; // positive = gain, negative = loss

        DB::beginTransaction();
        try {
            $journal = Journal::create([
                'branch_id' => $branchId,
                'date' => $validated['disposal_date'],
                'reference' => 'IDP-' . $asset->code . '-' . date('Ymd', strtotime($validated['disposal_date'])),
                'reference_type' => 'Intangible Disposal',
                'description' => "Disposal of intangible asset {$asset->name} ({$asset->code})",
                'user_id' => $user->id,
            ]);

            $journalItems = [];

            // Dr Accumulated Amortisation
            if ($asset->accumulated_amortisation > 0) {
                $journalItems[] = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $category->accumulated_amortisation_account_id,
                    'amount' => $asset->accumulated_amortisation,
                    'nature' => 'debit',
                    'description' => 'Reverse accumulated amortisation on disposal',
                ]);
            }

            // Dr Accumulated Impairment
            if ($asset->accumulated_impairment > 0) {
                $journalItems[] = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $category->accumulated_impairment_account_id,
                    'amount' => $asset->accumulated_impairment,
                    'nature' => 'debit',
                    'description' => 'Reverse accumulated impairment on disposal',
                ]);
            }

            // Cr Intangible Asset – Cost (remove cost)
            if ($asset->cost > 0) {
                $journalItems[] = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $category->cost_account_id,
                    'amount' => $asset->cost,
                    'nature' => 'credit',
                    'description' => 'Remove intangible asset cost on disposal',
                ]);
            }

            // Gain/Loss on disposal (net balancing figure, excluding cash movement)
            if (abs($gainLoss) > 0.0001) {
                $journalItems[] = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $category->disposal_gain_loss_account_id,
                    'amount' => abs($gainLoss),
                    'nature' => $gainLoss >= 0 ? 'credit' : 'debit',
                    'description' => 'Gain/Loss on disposal of intangible asset',
                ]);
            }

            // GL transactions for all journal lines
            foreach ($journalItems as $item) {
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

            // Record disposal event
            IntangibleDisposal::create([
                'intangible_asset_id' => $asset->id,
                'company_id' => $user->company_id,
                'branch_id' => $branchId,
                'disposal_date' => $validated['disposal_date'],
                'proceeds' => $proceeds,
                'nbv_at_disposal' => $nbv,
                'gain_loss' => $gainLoss,
                'status' => 'posted',
                'journal_id' => $journal->id,
                'gl_posted' => true,
                'reason' => $validated['reason'] ?? null,
            ]);

            // Update asset
            $asset->status = 'disposed';
            $asset->nbv = 0;
            $asset->save();

            DB::commit();

            return redirect()
                ->route('assets.intangible.index')
                ->with('success', "Disposal recorded for asset {$asset->code}. Gain/Loss: " . number_format($gainLoss, 2));
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Intangible disposal failed', [
                'asset_id' => $asset->id ?? null,
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['intangible_asset_id' => 'Failed to record disposal: ' . $e->getMessage()]);
        }
    }
}


