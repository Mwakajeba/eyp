<?php

namespace App\Http\Controllers\Intangible;

use App\Http\Controllers\Controller;
use App\Models\Intangible\IntangibleAsset;
use App\Models\Intangible\IntangibleCostComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class IntangibleCostComponentController extends Controller
{
    /**
     * Display cost components for an intangible asset
     */
    public function index($asset)
    {
        $decodedId = Hashids::decode($asset)[0] ?? $asset;
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedId);

        $encodedId = Hashids::encode($assetModel->id);
        $totalCostComponents = $assetModel->costComponents()->sum('amount');
        $costDifference = $assetModel->cost - $totalCostComponents;
        $asset = $assetModel;

        return view('intangible.cost-components.index', compact('asset', 'encodedId', 'totalCostComponents', 'costDifference'));
    }

    /**
     * Get cost components data for DataTables
     */
    public function data(Request $request, $asset)
    {
        $decodedId = Hashids::decode($asset)[0] ?? $asset;
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedId);

        $query = IntangibleCostComponent::where('intangible_asset_id', $assetModel->id)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        return DataTables::of($query)
            ->editColumn('date', function (IntangibleCostComponent $component) {
                return $component->date ? $component->date->format('M d, Y') : '-';
            })
            ->editColumn('type', function (IntangibleCostComponent $component) {
                $typeLabels = [
                    'purchase_price' => 'Purchase Price',
                    'legal_fees' => 'Legal Fees',
                    'registration_fees' => 'Registration Fees',
                    'valuation_fees' => 'Valuation Fees',
                    'import_duties' => 'Import Duties & Taxes',
                    'testing_costs' => 'Testing Costs',
                    'other' => 'Other',
                ];
                return $typeLabels[$component->type] ?? ucfirst(str_replace('_', ' ', $component->type));
            })
            ->editColumn('amount', function (IntangibleCostComponent $component) {
                return 'TZS ' . number_format($component->amount, 2);
            })
            ->addColumn('actions', function (IntangibleCostComponent $component) use ($asset) {
                $encodedAssetId = Hashids::encode($component->asset->id);
                $encodedComponentId = Hashids::encode($component->id);
                $actions = '<div class="btn-group btn-group-sm">';
                $actions .= '<a href="' . route('assets.intangible.cost-components.edit', [$encodedAssetId, $encodedComponentId]) . '" class="btn btn-outline-warning" title="Edit"><i class="bx bx-edit"></i></a>';
                
                // Only allow delete if not posted to GL (check if asset has initial_journal_id)
                if (!$component->asset->initial_journal_id) {
                    $actions .= '<button type="button" class="btn btn-outline-danger delete-component" data-id="' . $encodedComponentId . '" title="Delete"><i class="bx bx-trash"></i></button>';
                }
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Show form to create a new cost component
     */
    public function create($asset)
    {
        $decodedId = Hashids::decode($asset)[0] ?? $asset;
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedId);

        $encodedId = Hashids::encode($assetModel->id);
        $totalCostComponents = $assetModel->costComponents()->sum('amount');
        $remainingAmount = max(0, $assetModel->cost - $totalCostComponents);
        $asset = $assetModel;

        return view('intangible.cost-components.create', compact('asset', 'encodedId', 'totalCostComponents', 'remainingAmount'));
    }

    /**
     * Store a newly created cost component
     */
    public function store(Request $request, $asset)
    {
        $decodedId = Hashids::decode($asset)[0] ?? $asset;
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedId);

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:purchase_price,legal_fees,registration_fees,valuation_fees,import_duties,testing_costs,other',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'source_document_id' => 'nullable|integer',
            'source_document_type' => 'nullable|string|max:255',
        ]);

        // Check if adding this amount would exceed asset cost
        $totalCostComponents = $assetModel->costComponents()->sum('amount');
        $newTotal = $totalCostComponents + $validated['amount'];
        
        if ($newTotal > $assetModel->cost) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Total cost components cannot exceed asset cost. Current total: TZS ' . number_format($totalCostComponents, 2) . ', Asset cost: TZS ' . number_format($assetModel->cost, 2)]);
        }

        DB::beginTransaction();
        try {
            $component = new IntangibleCostComponent();
            $component->intangible_asset_id = $assetModel->id;
            $component->company_id = Auth::user()->company_id;
            $component->fill($validated);
            $component->save();

            DB::commit();

            return redirect()
                ->route('assets.intangible.cost-components.index', Hashids::encode($assetModel->id))
                ->with('success', 'Cost component added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to add cost component: ' . $e->getMessage()]);
        }
    }

    /**
     * Show form to edit a cost component
     */
    public function edit($asset, $component)
    {
        $decodedAssetId = Hashids::decode($asset)[0] ?? $asset;
        $decodedComponentId = Hashids::decode($component)[0] ?? $component;
        
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedAssetId);

        $componentModel = IntangibleCostComponent::where('intangible_asset_id', $assetModel->id)
            ->findOrFail($decodedComponentId);

        $encodedAssetId = Hashids::encode($assetModel->id);
        $totalCostComponents = $assetModel->costComponents()->where('id', '!=', $componentModel->id)->sum('amount');
        $remainingAmount = max(0, $assetModel->cost - $totalCostComponents);
        $asset = $assetModel;
        $component = $componentModel;

        return view('intangible.cost-components.edit', compact('asset', 'component', 'encodedAssetId', 'totalCostComponents', 'remainingAmount'));
    }

    /**
     * Update a cost component
     */
    public function update(Request $request, $asset, $component)
    {
        $decodedAssetId = Hashids::decode($asset)[0] ?? $asset;
        $decodedComponentId = Hashids::decode($component)[0] ?? $component;
        
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedAssetId);

        $componentModel = IntangibleCostComponent::where('intangible_asset_id', $assetModel->id)
            ->findOrFail($decodedComponentId);

        // Check if asset is posted to GL
        if ($assetModel->initial_journal_id) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Cannot edit cost components after asset has been posted to GL.']);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:purchase_price,legal_fees,registration_fees,valuation_fees,import_duties,testing_costs,other',
            'description' => 'required|string|max:500',
            'amount' => 'required|numeric|min:0.01',
            'source_document_id' => 'nullable|integer',
            'source_document_type' => 'nullable|string|max:255',
        ]);

        // Check if updating this amount would exceed asset cost
        $totalCostComponents = $assetModel->costComponents()->where('id', '!=', $componentModel->id)->sum('amount');
        $newTotal = $totalCostComponents + $validated['amount'];
        
        if ($newTotal > $assetModel->cost) {
            return back()
                ->withInput()
                ->withErrors(['amount' => 'Total cost components cannot exceed asset cost. Current total (excluding this): TZS ' . number_format($totalCostComponents, 2) . ', Asset cost: TZS ' . number_format($assetModel->cost, 2)]);
        }

        DB::beginTransaction();
        try {
            $componentModel->update($validated);

            DB::commit();

            return redirect()
                ->route('assets.intangible.cost-components.index', Hashids::encode($assetModel->id))
                ->with('success', 'Cost component updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update cost component: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a cost component
     */
    public function destroy($asset, $component)
    {
        $decodedAssetId = Hashids::decode($asset)[0] ?? $asset;
        $decodedComponentId = Hashids::decode($component)[0] ?? $component;
        
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedAssetId);

        $componentModel = IntangibleCostComponent::where('intangible_asset_id', $assetModel->id)
            ->findOrFail($decodedComponentId);

        // Check if asset is posted to GL
        if ($assetModel->initial_journal_id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete cost components after asset has been posted to GL.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $componentModel->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cost component deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cost component: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Export cost component breakdown
     */
    public function export($asset)
    {
        $decodedId = Hashids::decode($asset)[0] ?? $asset;
        $assetModel = IntangibleAsset::where('company_id', Auth::user()->company_id)
            ->findOrFail($decodedId);

        $components = $assetModel->costComponents()->orderBy('date')->get();
        $totalCostComponents = $components->sum('amount');
        $costDifference = $assetModel->cost - $totalCostComponents;

        // Generate CSV
        $filename = 'Cost_Components_' . $assetModel->code . '_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($assetModel, $components, $totalCostComponents, $costDifference) {
            $file = fopen('php://output', 'w');
            
            // Header
            fputcsv($file, ['Intangible Asset Cost Components Breakdown']);
            fputcsv($file, ['Asset Code:', $assetModel->code]);
            fputcsv($file, ['Asset Name:', $assetModel->name]);
            fputcsv($file, ['Acquisition Date:', $assetModel->acquisition_date ? $assetModel->acquisition_date->format('Y-m-d') : 'N/A']);
            fputcsv($file, []);
            
            // Cost components
            fputcsv($file, ['Date', 'Type', 'Description', 'Amount (TZS)']);
            foreach ($components as $component) {
                $typeLabels = [
                    'purchase_price' => 'Purchase Price',
                    'legal_fees' => 'Legal Fees',
                    'registration_fees' => 'Registration Fees',
                    'valuation_fees' => 'Valuation Fees',
                    'import_duties' => 'Import Duties & Taxes',
                    'testing_costs' => 'Testing Costs',
                    'other' => 'Other',
                ];
                $typeLabel = $typeLabels[$component->type] ?? ucfirst(str_replace('_', ' ', $component->type));
                
                fputcsv($file, [
                    $component->date ? $component->date->format('Y-m-d') : '',
                    $typeLabel,
                    $component->description,
                    number_format($component->amount, 2),
                ]);
            }
            
            fputcsv($file, []);
            fputcsv($file, ['Total Cost Components:', number_format($totalCostComponents, 2)]);
            fputcsv($file, ['Asset Cost:', number_format($assetModel->cost, 2)]);
            fputcsv($file, ['Difference:', number_format($costDifference, 2)]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

