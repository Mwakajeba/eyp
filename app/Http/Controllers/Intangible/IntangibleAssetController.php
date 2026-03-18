<?php

namespace App\Http\Controllers\Intangible;

use App\Http\Controllers\Controller;
use App\Models\Intangible\IntangibleAsset;
use App\Models\Intangible\IntangibleAssetCategory;
use App\Models\Journal;
use App\Models\JournalItem;
use App\Models\GlTransaction;
use App\Models\BankAccount;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class IntangibleAssetController extends Controller
{
    /**
     * Display intangible assets register view.
     */
    public function index()
    {
        return view('intangible.assets.index');
    }

    /**
     * Data for DataTables (server-side).
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $query = IntangibleAsset::where('company_id', $user->company_id)
            ->forBranch($branchId)
            ->with('category')
            ->select('intangible_assets.*')
            ->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        return DataTables::of($query)
            ->addColumn('category_name', function (IntangibleAsset $asset) {
                return optional($asset->category)->name ?? '-';
            })
            ->editColumn('cost', function (IntangibleAsset $asset) {
                return number_format($asset->cost, 2);
            })
            ->editColumn('accumulated_amortisation', function (IntangibleAsset $asset) {
                return number_format($asset->accumulated_amortisation, 2);
            })
            ->editColumn('accumulated_impairment', function (IntangibleAsset $asset) {
                return number_format($asset->accumulated_impairment, 2);
            })
            ->editColumn('nbv', function (IntangibleAsset $asset) {
                return number_format($asset->nbv, 2);
            })
            ->addColumn('useful_life_display', function (IntangibleAsset $asset) {
                return $asset->is_indefinite_life ? 'Indefinite' : ($asset->useful_life_months ?? '-');
            })
            ->editColumn('status', function (IntangibleAsset $asset) {
                $label = ucfirst(str_replace('_', ' ', $asset->status));
                return '<span class="badge bg-secondary">' . e($label) . '</span>';
            })
            ->addColumn('actions', function (IntangibleAsset $asset) {
                $encodedId = Hashids::encode($asset->id);
                $costComponentsUrl = route('assets.intangible.cost-components.index', $encodedId);
                $impairUrl = route('assets.intangible.impairments.create', ['asset_id' => $asset->id]);
                $disposeUrl = route('assets.intangible.disposals.create', ['asset_id' => $asset->id]);
                return '<div class="btn-group btn-group-sm" role="group">
                            <a href="' . e($costComponentsUrl) . '" class="btn btn-outline-info" title="Cost Components">
                                <i class="bx bx-list-ul"></i>
                            </a>
                            <a href="' . e($impairUrl) . '" class="btn btn-outline-danger" title="Impairment">
                                <i class="bx bx-trending-down"></i>
                            </a>
                            <a href="' . e($disposeUrl) . '" class="btn btn-outline-secondary" title="Disposal">
                                <i class="bx bx-transfer-alt"></i>
                            </a>
                        </div>';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Show form to create a new intangible asset.
     */
    public function create()
    {
        $user = Auth::user();

        $categories = IntangibleAssetCategory::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        $bankAccounts = BankAccount::where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        return view('intangible.assets.create', compact('categories', 'bankAccounts'));
    }

    /**
     * Store a newly created intangible asset and initial recognition journal.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $branchId = session('branch_id') ?? $user->branch_id ?? null;

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:intangible_assets,code',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:intangible_asset_categories,id',
            'source_type' => 'required|in:purchased,internally_developed,goodwill,indefinite_life,other',
            'acquisition_date' => 'required|date',
            'cost' => 'required|numeric|min:0.01',
            'amount_paid' => 'nullable|numeric|min:0',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'useful_life_months' => 'nullable|integer|min:1',
            'is_indefinite_life' => 'sometimes|boolean',
            'is_goodwill' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'recognition_identifiable' => 'sometimes|boolean',
            'recognition_control' => 'sometimes|boolean',
            'recognition_future_benefits' => 'sometimes|boolean',
            'recognition_reliable_measurement' => 'sometimes|boolean',
        ]);

        // Validate amount_paid doesn't exceed cost
        if (isset($validated['amount_paid']) && $validated['amount_paid'] > $validated['cost']) {
            return back()
                ->withInput()
                ->withErrors(['amount_paid' => 'Amount paid cannot exceed total cost.']);
        }

        // Validate bank account belongs to company
        if (!empty($validated['bank_account_id'])) {
            $bankAccount = BankAccount::where('company_id', $user->company_id)
                ->find($validated['bank_account_id']);
            if (!$bankAccount) {
                return back()
                    ->withInput()
                    ->withErrors(['bank_account_id' => 'Selected bank account does not belong to your company.']);
            }
        }

        // Map indefinite_life to other for storage
        if ($validated['source_type'] === 'indefinite_life') {
            $validated['source_type'] = 'other';
        }

        // Get category to use its type
        $category = IntangibleAssetCategory::where('company_id', $user->company_id)
            ->findOrFail($validated['category_id']);

        // Enforce IAS 38 rules: goodwill & indefinite-life not amortised
        if (!empty($validated['is_goodwill']) || $validated['source_type'] === 'goodwill' || $category->type === 'goodwill') {
            $validated['is_goodwill'] = true;
            $validated['is_indefinite_life'] = true;
            $validated['useful_life_months'] = null;
        } elseif (!empty($validated['is_indefinite_life']) || $validated['source_type'] === 'indefinite_life' || $category->type === 'indefinite_life') {
            $validated['is_indefinite_life'] = true;
            $validated['useful_life_months'] = null;
        }

        // Basic validation: finite life must have useful life
        if (empty($validated['is_indefinite_life']) && empty($validated['is_goodwill']) && empty($validated['useful_life_months'])) {
            return back()
                ->withInput()
                ->withErrors(['useful_life_months' => 'Useful life (in months) is required for finite-life intangibles.']);
        }

        // Determine debit account (intangible cost) from category
        $debitAccountId = $category?->cost_account_id;
        if (!$debitAccountId) {
            return back()
                ->withInput()
                ->withErrors(['category_id' => 'Selected category is missing an Intangible Asset – Cost account mapping.']);
        }

        // Collect recognition checks
        $recognitionChecks = [
            'identifiable' => $validated['recognition_identifiable'] ?? true,
            'control' => $validated['recognition_control'] ?? true,
            'future_benefits' => $validated['recognition_future_benefits'] ?? true,
            'reliable_measurement' => $validated['recognition_reliable_measurement'] ?? true,
        ];

        // Get amount paid
        // If bank account is provided but amount_paid is not provided, assume full payment
        // Otherwise, use provided amount_paid or 0
        $totalCost = $validated['cost'];
        $amountPaid = 0;
        
        if (!empty($validated['bank_account_id'])) {
            $amountPaid = $validated['amount_paid'] ?? $totalCost; // Default to full payment if bank account selected
        } elseif (isset($validated['amount_paid'])) {
            $amountPaid = $validated['amount_paid'];
        }
        
        $remainingBalance = max(0, $totalCost - $amountPaid);

        DB::beginTransaction();
        try {
            $journal = Journal::create([
                'branch_id' => $branchId,
                'date' => $validated['acquisition_date'],
                'reference' => 'IA-' . $validated['code'],
                'reference_type' => 'Intangible Asset Recognition',
                'description' => "Initial recognition of intangible asset {$validated['name']}",
                'user_id' => $user->id,
            ]);

            // Debit: Intangible Asset Cost
            $debitItem = JournalItem::create([
                'journal_id' => $journal->id,
                'chart_account_id' => $debitAccountId,
                'amount' => $totalCost,
                'nature' => 'debit',
                'description' => 'Intangible asset cost',
            ]);

            // Post debit to GL
            GlTransaction::create([
                'chart_account_id' => $debitItem->chart_account_id,
                'amount' => $debitItem->amount,
                'nature' => $debitItem->nature,
                'transaction_id' => $journal->id,
                'transaction_type' => 'journal',
                'date' => $journal->date,
                'description' => $debitItem->description,
                'branch_id' => $branchId,
                'user_id' => $user->id,
            ]);

            // Credit: Bank Account (if bank account is provided and amount paid > 0)
            if (!empty($validated['bank_account_id']) && $amountPaid > 0) {
                $bankAccount = BankAccount::where('company_id', $user->company_id)
                    ->findOrFail($validated['bank_account_id']);

                if (!$bankAccount->chart_account_id) {
                    throw new \Exception('Selected bank account is missing a chart account mapping.');
                }

                $creditBankItem = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $bankAccount->chart_account_id,
                    'amount' => $amountPaid,
                    'nature' => 'credit',
                    'description' => "Payment for intangible asset {$validated['name']}",
                ]);

                // Post credit to GL
                GlTransaction::create([
                    'chart_account_id' => $creditBankItem->chart_account_id,
                    'amount' => $creditBankItem->amount,
                    'nature' => $creditBankItem->nature,
                    'transaction_id' => $journal->id,
                    'transaction_type' => 'journal',
                    'date' => $journal->date,
                    'description' => $creditBankItem->description,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);
            }

            // Credit: Trade Payable (if remaining balance > 0)
            if ($remainingBalance > 0) {
                // Get trade payable account from system settings
                $payableAccountId = (int) (SystemSetting::where('key', 'inventory_default_purchase_payable_account')
                    ->value('value') ?? null);

                if (!$payableAccountId) {
                    throw new \Exception('Trade payable account not configured. Please configure it in System Settings.');
                }

                $creditPayableItem = JournalItem::create([
                    'journal_id' => $journal->id,
                    'chart_account_id' => $payableAccountId,
                    'amount' => $remainingBalance,
                    'nature' => 'credit',
                    'description' => "Trade payable for intangible asset {$validated['name']}",
                ]);

                // Post credit to GL
                GlTransaction::create([
                    'chart_account_id' => $creditPayableItem->chart_account_id,
                    'amount' => $creditPayableItem->amount,
                    'nature' => $creditPayableItem->nature,
                    'transaction_id' => $journal->id,
                    'transaction_type' => 'journal',
                    'date' => $journal->date,
                    'description' => $creditPayableItem->description,
                    'branch_id' => $branchId,
                    'user_id' => $user->id,
                ]);
            }

            $asset = new IntangibleAsset();
            $asset->fill($validated);
            $asset->company_id = $user->company_id;
            $asset->branch_id = $branchId;
            $asset->accumulated_amortisation = 0;
            $asset->accumulated_impairment = 0;
            $asset->nbv = $totalCost;
            $asset->initial_journal_id = $journal->id;
            $asset->recognition_checks = $recognitionChecks;
            $asset->save();

            DB::commit();

            return redirect()
                ->route('assets.intangible.index')
                ->with('success', 'Intangible asset created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create intangible asset: ' . $e->getMessage()]);
        }

        return redirect()
            ->route('assets.intangible.index')
            ->with('success', 'Intangible asset created successfully.');
    }
}


