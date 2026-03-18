<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Assets\AssetOpening;
use App\Models\Assets\AssetCategory;
use App\Models\Assets\AssetDepreciation;
use App\Models\SystemSetting;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Models\GlTransaction;
use Vinkla\Hashids\Facades\Hashids;

class OpeningAssetsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        $openings = AssetOpening::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->orderByDesc('opening_date')
            ->paginate(25);

        return view('assets.openings.index', compact('openings'));
    }

    public function create()
    {
        $assets = Asset::with('taxClass')
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'asset_category_id', 'tax_class_id', 'purchase_cost']);
        $categories = AssetCategory::orderBy('name')->get(['id', 'name']);
        $taxPools = json_decode(SystemSetting::where('key', 'asset_tax_pools')->value('value') ?? '[]', true);
        return view('assets.openings.create', compact('assets', 'categories', 'taxPools'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        try {
            $validated = $request->validate([
                'asset_id' => 'nullable|exists:assets,id',
                'asset_code' => 'nullable|string|max:50',
                'asset_name' => 'required_without:asset_id|nullable|string|max:255',
                'asset_category_id' => 'nullable|exists:asset_categories,id',
                'tax_pool_class' => 'nullable|string|max:50',
                'opening_date' => 'required|date',
                'opening_cost' => 'required|numeric|min:0',
                'opening_accum_depr' => 'nullable|numeric|min:0',
                'opening_nbv' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'gl_post' => 'nullable|boolean',
            ]);

            return DB::transaction(function () use ($validated, $user) {
                $openingNbv = $validated['opening_nbv'] ?? ((float)($validated['opening_cost'] ?? 0) - (float)($validated['opening_accum_depr'] ?? 0));

                // Determine defaults from linked asset if provided
                $linkedAsset = isset($validated['asset_id']) && $validated['asset_id'] ? Asset::find($validated['asset_id']) : null;

                // Resolve branch id robustly
                $branchIdResolved = $user->branch_id ?? session('branch_id');
                if (!$branchIdResolved) {
                    throw new \Exception('Branch is not selected. Please select a branch and try again.');
                }

                $opening = AssetOpening::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchIdResolved,
                    'asset_id' => $validated['asset_id'] ?? null,
                    'asset_code' => $validated['asset_code'] ?? null,
                    'asset_name' => $validated['asset_name'] ?? ($linkedAsset?->name ?? ''),
                    'asset_category_id' => $validated['asset_category_id'] ?? ($linkedAsset?->asset_category_id ?? null),
                    'tax_pool_class' => $linkedAsset?->taxClass?->class_code ?? ($validated['tax_pool_class'] ?? null),
                    'opening_date' => Carbon::parse($validated['opening_date'])->format('Y-m-d'),
                    'opening_cost' => (float)$validated['opening_cost'],
                    'opening_accum_depr' => (float)($validated['opening_accum_depr'] ?? 0),
                    'opening_nbv' => max((float)$openingNbv, 0),
                    'notes' => $validated['notes'] ?? null,
                    'gl_post' => (bool)($validated['gl_post'] ?? false),
                    'gl_posted' => false,
                    'created_by' => $user->id,
                ]);

                // GL posting for opening balances
                if ($opening->gl_post) {
                    $category = $opening->asset_category_id ? AssetCategory::find($opening->asset_category_id) : null;
                    $assetAccountId = (int) ($category?->asset_account_id
                        ?: (SystemSetting::where('key', 'asset_default_asset_account')->value('value') ?? 0));
                    $accumDeprAccountId = (int) ($category?->accum_depr_account_id
                        ?: (SystemSetting::where('key', 'asset_default_accumulated_depreciation_account')->value('value')
                            ?? SystemSetting::where('key', 'asset_default_accum_depr_account')->value('value') ?? 0));
                    // Get opening equity account with fallback to Retained Earnings by name
                    $openingEquityAccountId = (int) (
                        SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value')
                        ?? ChartAccount::where('account_name', 'Retained Earnings')->value('id')
                        ?? SystemSetting::where('key', 'asset_opening_balance_equity_account_id')->value('value')
                        ?? 63
                    );

                    if (!$assetAccountId || !$openingEquityAccountId) {
                        throw new \Exception('Asset Opening posting accounts are not configured.');
                    }

                    $date = $opening->opening_date;
                    $descBase = 'Opening balance - ' . ($opening->asset_name ?? 'Asset');
                    $branchId = $opening->branch_id;
                    $userId = $user->id;

                    if ($opening->opening_cost > 0) {
                        GlTransaction::create([
                            'chart_account_id' => $assetAccountId,
                            'asset_id' => $opening->asset_id,
                            'amount' => $opening->opening_cost,
                            'nature' => 'debit',
                            'transaction_id' => $opening->id,
                            'transaction_type' => 'asset_opening',
                            'date' => $date,
                            'description' => $descBase,
                            'branch_id' => $branchId,
                            'user_id' => $userId,
                        ]);
                        GlTransaction::create([
                            'chart_account_id' => $openingEquityAccountId,
                            'asset_id' => $opening->asset_id,
                            'amount' => $opening->opening_cost,
                            'nature' => 'credit',
                            'transaction_id' => $opening->id,
                            'transaction_type' => 'asset_opening',
                            'date' => $date,
                            'description' => $descBase,
                            'branch_id' => $branchId,
                            'user_id' => $userId,
                        ]);
                    }

                    if ($opening->opening_accum_depr > 0 && $accumDeprAccountId) {
                        GlTransaction::create([
                            'chart_account_id' => $openingEquityAccountId,
                            'asset_id' => $opening->asset_id,
                            'amount' => $opening->opening_accum_depr,
                            'nature' => 'debit',
                            'transaction_id' => $opening->id,
                            'transaction_type' => 'asset_opening',
                            'date' => $date,
                            'description' => $descBase,
                            'branch_id' => $branchId,
                            'user_id' => $userId,
                        ]);
                        GlTransaction::create([
                            'chart_account_id' => $accumDeprAccountId,
                            'asset_id' => $opening->asset_id,
                            'amount' => $opening->opening_accum_depr,
                            'nature' => 'credit',
                            'transaction_id' => $opening->id,
                            'transaction_type' => 'asset_opening',
                            'date' => $date,
                            'description' => $descBase,
                            'branch_id' => $branchId,
                            'user_id' => $userId,
                        ]);
                    }

                    $opening->update(['gl_posted' => true]);
                }

                // Save opening balance in depreciation table for tracking history
                // This is done for all opening balances, whether linked to an asset or not
                $asset = $opening->asset_id ? Asset::find($opening->asset_id) : null;

                if ($opening->asset_id) {
                    // Get previous book value (should be the asset's purchase cost if no previous depreciations)
                    $previousBookValue = $asset ? $asset->purchase_cost : $opening->opening_cost;
                    $previousAccumDepr = AssetDepreciation::getAccumulatedDepreciation($opening->asset_id, $opening->opening_date, $user->company_id);

                    // Calculate book value before and after
                    $bookValueBefore = $previousBookValue - $previousAccumDepr;
                    $bookValueAfter = $opening->opening_nbv;
                } else {
                    // For manual entries without linked asset, use opening values directly
                    $bookValueBefore = $opening->opening_cost;
                    $bookValueAfter = $opening->opening_nbv;
                }

                AssetDepreciation::create([
                    'company_id' => $user->company_id,
                    'branch_id' => $branchIdResolved,
                    'asset_id' => $opening->asset_id, // Can be null for manual entries
                    'asset_opening_id' => $opening->id,
                    'type' => 'opening_balance',
                    'depreciation_date' => $opening->opening_date,
                    'depreciation_amount' => 0, // Opening balance doesn't add depreciation, it sets it
                    'accumulated_depreciation' => $opening->opening_accum_depr,
                    'book_value_before' => $bookValueBefore,
                    'book_value_after' => $bookValueAfter,
                    'description' => 'Opening balance - ' . ($opening->asset_name ?? 'Asset'),
                    'gl_posted' => $opening->gl_posted,
                    'created_by' => $user->id,
                ]);

                // Update linked asset: purchase date, cost, capitalization date, and current NBV
                if ($asset) {
                    $asset->purchase_cost = $opening->opening_cost;
                    $asset->purchase_date = $opening->opening_date;
                    if (empty($asset->capitalization_date)) {
                        $asset->capitalization_date = $opening->opening_date;
                    }
                    $asset->current_nbv = $bookValueAfter;
                    $asset->save();
                }

                return redirect()->route('assets.openings.index')->with('success', 'Opening balance recorded.');
            });
        } catch (\Throwable $e) {
            \Log::error('asset.openings.store.failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
                'user_id' => $user->id,
            ]);
            return back()->withInput()->withErrors(['error' => 'Failed to save opening asset: ' . $e->getMessage()]);
        }
    }

    public function data(Request $request)
    {
        $query = AssetOpening::query()
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id));

        return DataTables::of($query)
            ->editColumn('opening_date', fn($o) => Carbon::parse($o->opening_date)->format('Y-m-d'))
            ->addColumn('category_name', function ($o) {
                $c = AssetCategory::find($o->asset_category_id);
                return $c?->name;
            })
            ->addColumn('id_hashed', fn($o) => Hashids::encode($o->id))
            ->addColumn('gl_status', function ($o) {
                if ($o->gl_posted) return '<span class="badge bg-success">Posted</span>';
                if ($o->gl_post) return '<span class="badge bg-warning text-dark">To Post</span>';
                return '<span class="badge bg-secondary">N/A</span>';
            })
            ->rawColumns(['gl_status'])
            ->make(true);
    }

    public function show($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $opening = AssetOpening::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->findOrFail($decodedId);
        $category = $opening->asset_category_id ? AssetCategory::find($opening->asset_category_id) : null;
        $glTransactions = $opening->glTransactions()->with('chartAccount')->orderBy('date')->orderBy('nature')->get();
        return view('assets.openings.show', compact('opening', 'category', 'glTransactions'));
    }

    public function destroy($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? $id;
        $opening = AssetOpening::where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->findOrFail($decodedId);

        try {
            DB::transaction(function () use ($opening) {
                // delete related GL transactions for this opening
                GlTransaction::where('transaction_type', 'asset_opening')
                    ->where('transaction_id', $opening->id)
                    ->delete();

                // delete related depreciation entries
                AssetDepreciation::where('asset_opening_id', $opening->id)->delete();

                $opening->delete();
            });
            return redirect()->route('assets.openings.index')->with('success', 'Opening asset deleted.');
        } catch (\Throwable $e) {
            \Log::error('asset.openings.destroy.failed', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to delete: ' . $e->getMessage()]);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|mimes:csv,txt|max:10240',
            'csv_file' => 'nullable|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $uploaded = $request->file('csv_file') ?: $request->file('file');
            if (!$uploaded) {
                return response()->json(['success' => false, 'message' => 'Please upload a CSV file.'], 422);
            }
            $path = $uploaded->getRealPath();
            $rows = array_map('str_getcsv', file($path));
            if (empty($rows) || count($rows) < 2) {
                return response()->json(['success' => false, 'message' => 'The CSV file is empty.'], 422);
            }
            $header = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));

            $required = ['asset_name', 'opening_date', 'opening_cost'];
            foreach ($required as $col) {
                if (!in_array($col, $header)) {
                    return response()->json(['success' => false, 'message' => "Missing required column: {$col}"], 422);
                }
            }

            $imported = 0;
            $errors = [];
            $user = Auth::user();
            $branchIdResolved = $user->branch_id ?? session('branch_id');
            if (!$branchIdResolved) {
                return response()->json(['success' => false, 'message' => 'Branch is not selected.'], 422);
            }

            foreach ($rows as $rowIndex => $row) {
                if (count($row) !== count($header)) {
                    $row = array_pad($row, count($header), null);
                }
                $data = array_combine($header, $row);

                if (empty(trim($data['asset_name'] ?? ''))) {
                    continue;
                }

                try {
                    // Resolve asset by code or name
                    $assetId = null;
                    $assetCode = trim($data['asset_code'] ?? '');
                    $assetName = trim($data['asset_name'] ?? '');

                    if ($assetCode) {
                        $asset = Asset::where('company_id', $user->company_id)
                            ->where('code', $assetCode)
                            ->first();
                        if ($asset) {
                            $assetId = $asset->id;
                        }
                    }

                    if (!$assetId && $assetName) {
                        $asset = Asset::where('company_id', $user->company_id)
                            ->where('name', $assetName)
                            ->first();
                        if ($asset) {
                            $assetId = $asset->id;
                        }
                    }

                    // Resolve category
                    $categoryId = null;
                    $categoryName = trim($data['category_name'] ?? '');
                    if ($categoryName) {
                        $category = AssetCategory::where('name', $categoryName)->first();
                        if ($category) {
                            $categoryId = $category->id;
                        }
                    }
                    if (!$categoryId && $assetId) {
                        $asset = Asset::find($assetId);
                        $categoryId = $asset?->asset_category_id;
                    }

                    // Parse dates and numerics
                    $openingDate = !empty($data['opening_date']) ? Carbon::parse($data['opening_date']) : now();
                    $openingCost = isset($data['opening_cost']) && is_numeric($data['opening_cost']) ? (float)$data['opening_cost'] : 0;
                    $openingAccumDepr = isset($data['opening_accum_depr']) && is_numeric($data['opening_accum_depr']) ? (float)$data['opening_accum_depr'] : 0;
                    $openingNbv = isset($data['opening_nbv']) && is_numeric($data['opening_nbv'])
                        ? (float)$data['opening_nbv']
                        : max($openingCost - $openingAccumDepr, 0);
                    $taxPoolClass = trim($data['tax_pool_class'] ?? ($data['class'] ?? ''));
                    if ($assetId) {
                        $asset = Asset::find($assetId);
                        $taxPoolClass = $taxPoolClass ?: $asset?->taxClass?->class_code;
                    }

                    $glPost = isset($data['gl_post']) && in_array(strtolower(trim($data['gl_post'])), ['1', 'yes', 'true', 'y']);

                    $opening = AssetOpening::create([
                        'company_id' => $user->company_id,
                        'branch_id' => $branchIdResolved,
                        'asset_id' => $assetId,
                        'asset_code' => $assetCode ?: ($assetId ? Asset::find($assetId)?->code : null),
                        'asset_name' => $assetName,
                        'asset_category_id' => $categoryId,
                        'tax_pool_class' => $taxPoolClass ?: null,
                        'opening_date' => $openingDate->format('Y-m-d'),
                        'opening_cost' => $openingCost,
                        'opening_accum_depr' => $openingAccumDepr,
                        'opening_nbv' => $openingNbv,
                        'notes' => trim($data['notes'] ?? ''),
                        'gl_post' => $glPost,
                        'gl_posted' => false,
                        'created_by' => $user->id,
                    ]);

                    // GL posting for opening balances during import
                    if ($glPost) {
                        $category = $categoryId ? AssetCategory::find($categoryId) : null;
                        $assetAccountId = (int) ($category?->asset_account_id
                            ?: (SystemSetting::where('key', 'asset_default_asset_account')->value('value') ?? 0));
                        $accumDeprAccountId = (int) ($category?->accum_depr_account_id
                            ?: (SystemSetting::where('key', 'asset_default_accumulated_depreciation_account')->value('value')
                                ?? SystemSetting::where('key', 'asset_default_accum_depr_account')->value('value') ?? 0));
                        // Get opening equity account with fallback to Retained Earnings by name
                        // Get opening equity account with fallback to Retained Earnings by name
                        $openingEquityAccountId = (int) (
                            SystemSetting::where('key', 'inventory_default_opening_balance_account')->value('value')
                            ?? ChartAccount::where('account_name', 'Retained Earnings')->value('id')
                            ?? SystemSetting::where('key', 'asset_opening_balance_equity_account_id')->value('value')
                            ?? 63
                        );

                        if ($assetAccountId && $openingEquityAccountId) {
                            $date = $opening->opening_date;
                            $descBase = 'Opening balance - ' . $assetName;
                            $branchId = $opening->branch_id;
                            $userId = $user->id;

                            if ($opening->opening_cost > 0) {
                                GlTransaction::create([
                                    'chart_account_id' => $assetAccountId,
                                    'asset_id' => $opening->asset_id,
                                    'amount' => $opening->opening_cost,
                                    'nature' => 'debit',
                                    'transaction_id' => $opening->id,
                                    'transaction_type' => 'asset_opening',
                                    'date' => $date,
                                    'description' => $descBase,
                                    'branch_id' => $branchId,
                                    'user_id' => $userId,
                                ]);
                                GlTransaction::create([
                                    'chart_account_id' => $openingEquityAccountId,
                                    'asset_id' => $opening->asset_id,
                                    'amount' => $opening->opening_cost,
                                    'nature' => 'credit',
                                    'transaction_id' => $opening->id,
                                    'transaction_type' => 'asset_opening',
                                    'date' => $date,
                                    'description' => $descBase,
                                    'branch_id' => $branchId,
                                    'user_id' => $userId,
                                ]);
                            }

                            if ($opening->opening_accum_depr > 0 && $accumDeprAccountId) {
                                GlTransaction::create([
                                    'chart_account_id' => $openingEquityAccountId,
                                    'asset_id' => $opening->asset_id,
                                    'amount' => $opening->opening_accum_depr,
                                    'nature' => 'debit',
                                    'transaction_id' => $opening->id,
                                    'transaction_type' => 'asset_opening',
                                    'date' => $date,
                                    'description' => $descBase,
                                    'branch_id' => $branchId,
                                    'user_id' => $userId,
                                ]);
                                GlTransaction::create([
                                    'chart_account_id' => $accumDeprAccountId,
                                    'asset_id' => $opening->asset_id,
                                    'amount' => $opening->opening_accum_depr,
                                    'nature' => 'credit',
                                    'transaction_id' => $opening->id,
                                    'transaction_type' => 'asset_opening',
                                    'date' => $date,
                                    'description' => $descBase,
                                    'branch_id' => $branchId,
                                    'user_id' => $userId,
                                ]);
                            }

                            $opening->update(['gl_posted' => true]);
                        }
                    }

                    // Save in depreciation table for all opening balances (linked or not)
                    $asset = $assetId ? Asset::find($assetId) : null;

                    if ($assetId) {
                        // For linked assets, calculate book value considering previous depreciations
                        $previousBookValue = $asset ? $asset->purchase_cost : $openingCost;
                        $previousAccumDepr = AssetDepreciation::getAccumulatedDepreciation($assetId, $openingDate, $user->company_id);
                        $bookValueBefore = $previousBookValue - $previousAccumDepr;
                        $bookValueAfter = $openingNbv;
                    } else {
                        // For manual entries without linked asset, use opening values directly
                        $bookValueBefore = $openingCost;
                        $bookValueAfter = $openingNbv;
                    }

                    AssetDepreciation::create([
                        'company_id' => $user->company_id,
                        'branch_id' => $branchIdResolved,
                        'asset_id' => $assetId, // Can be null for manual entries
                        'asset_opening_id' => $opening->id,
                        'type' => 'opening_balance',
                        'depreciation_date' => $openingDate,
                        'depreciation_amount' => 0,
                        'accumulated_depreciation' => $openingAccumDepr,
                        'book_value_before' => $bookValueBefore,
                        'book_value_after' => $bookValueAfter,
                        'description' => 'Opening balance - ' . $assetName,
                        'gl_posted' => $opening->gl_posted, // Use the actual GL posted status
                        'created_by' => $user->id,
                    ]);

                    // Update linked asset: purchase date, cost, capitalization date, and current NBV
                    if ($asset) {
                        $asset->purchase_cost = $openingCost;
                        $asset->purchase_date = $openingDate;
                        if (empty($asset->capitalization_date)) {
                            $asset->capitalization_date = $openingDate;
                        }
                        $asset->current_nbv = $bookValueAfter;
                        $asset->save();
                    }

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = 'Row ' . ($rowIndex + 2) . ': ' . $e->getMessage();
                    continue;
                }
            }

            $message = "Successfully imported {$imported} opening balances.";
            if (!empty($errors)) {
                $message .= ' ' . count($errors) . ' errors occurred.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $user = Auth::user();

        // Get assets that already have opening balances
        $assetsWithOpenings = AssetOpening::where('company_id', $user->company_id)
            ->whereNotNull('asset_id')
            ->pluck('asset_id')
            ->toArray();

        // Get available assets (excluding those with existing opening balances)
        $availableAssets = Asset::where('company_id', $user->company_id)
            ->when($user->branch_id, fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereNotIn('id', $assetsWithOpenings)
            ->with(['category', 'taxClass'])
            ->orderBy('name')
            ->limit(10) // Limit to 10 sample assets
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="opening_assets_template.csv"',
        ];

        $columns = [
            'asset_code',
            'asset_name',
            'category_name',
            'tax_pool_class',
            'opening_date',
            'opening_cost',
            'opening_accum_depr',
            'opening_nbv',
            'notes',
            'gl_post'
        ];

        // Generate sample data from available assets
        $sampleData = [];
        if ($availableAssets->count() > 0) {
            foreach ($availableAssets as $asset) {
                $sampleData[] = [
                    $asset->code ?? '',
                    $asset->name,
                    $asset->category?->name ?? '',
                    $asset->taxClass?->class_code ?? '',
                    now()->format('Y-m-d'),
                    $asset->purchase_cost ?? '0',
                    '0',
                    $asset->purchase_cost ?? '0',
                    'Opening balance',
                    'yes'
                ];
            }
        } else {
            // If no assets available, use generic examples
            $sampleData = [
                ['ASSET-001', 'Sample Asset 1', 'Computers', 'Class 1', now()->format('Y-m-d'), '1500000', '0', '1500000', 'Opening balance', 'yes'],
                ['ASSET-002', 'Sample Asset 2', 'Motor Vehicles', 'Class 2', now()->format('Y-m-d'), '45000000', '0', '45000000', 'Opening balance', 'yes'],
            ];
        }

        $callback = function () use ($columns, $sampleData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $columns);
            foreach ($sampleData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
