<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Services\Asset\AssetDeferredTaxService;
use App\Models\Assets\AssetDeferredTax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DeferredTaxController extends Controller
{
    protected $deferredTaxService;

    public function __construct(AssetDeferredTaxService $deferredTaxService)
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
        $this->deferredTaxService = $deferredTaxService;
    }

    /**
     * Display deferred tax management page
     */
    public function index()
    {
        return view('assets.deferred-tax.index');
    }

    /**
     * Process deferred tax calculation for a tax year
     */
    public function process(Request $request)
    {
        $request->validate([
            'tax_year' => 'required|integer|min:2000|max:2100',
            'post_to_gl' => 'sometimes|boolean',
        ]);

        $taxYear = (int) $request->input('tax_year');
        $postToGL = (bool) $request->input('post_to_gl', false);
        $companyId = Auth::user()->company_id;
        $branchId = Auth::user()->branch_id;

        try {
            $result = $this->deferredTaxService->processDeferredTax(
                $taxYear,
                $companyId,
                $branchId,
                $postToGL
            );

            $message = "Processed deferred tax for {$result['total_processed']} assets.";
            if ($result['total_errors'] > 0) {
                $message .= " {$result['total_errors']} errors occurred.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Deferred tax processing failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display deferred tax schedule
     */
    public function schedule(Request $request)
    {
        $taxYear = $request->input('tax_year', Carbon::now()->year);

        return view('assets.deferred-tax.schedule', compact('taxYear'));
    }

    /**
     * Get deferred tax schedule data
     */
    public function scheduleData(Request $request)
    {
        $taxYear = $request->input('tax_year', Carbon::now()->year);

        $query = AssetDeferredTax::where('tax_year', $taxYear)
            ->where('company_id', Auth::user()->company_id)
            ->when(Auth::user()->branch_id, fn($q) => $q->where('branch_id', Auth::user()->branch_id))
            ->with(['asset']);

        return DataTables::of($query)
            ->addColumn('asset_name', function ($dt) {
                if ($dt->asset) {
                    $encodedId = Hashids::encode($dt->asset->id);
                    return '<a href="' . route('assets.registry.show', $encodedId)
                        . '" class="text-primary">'
                        . e($dt->asset->name) . '</a>'
                        . '<br><small class="text-muted">' . e($dt->asset->code) . '</small>';
                }
                return '<span class="text-muted">Asset not found</span>';
            })
            ->addColumn('tax_base_formatted', function ($dt) {
                return 'TZS ' . number_format($dt->tax_base_carrying_amount, 2);
            })
            ->addColumn('accounting_carrying_formatted', function ($dt) {
                return 'TZS ' . number_format($dt->accounting_carrying_amount, 2);
            })
            ->addColumn('temporary_difference_formatted', function ($dt) {
                $class = $dt->temporary_difference >= 0 ? 'text-danger' : 'text-success';
                return '<span class="' . $class . '">'
                    . number_format($dt->temporary_difference, 2) . '</span>';
            })
            ->addColumn('deferred_tax_liability_formatted', function ($dt) {
                return 'TZS ' . number_format($dt->deferred_tax_liability, 2);
            })
            ->addColumn('deferred_tax_asset_formatted', function ($dt) {
                return 'TZS ' . number_format($dt->deferred_tax_asset, 2);
            })
            ->addColumn('net_deferred_tax_formatted', function ($dt) {
                $class = $dt->net_deferred_tax >= 0 ? 'text-danger' : 'text-success';
                return '<span class="' . $class . ' fw-semibold">'
                    . number_format($dt->net_deferred_tax, 2) . '</span>';
            })
            ->addColumn('opening_balance_formatted', function ($dt) {
                return 'TZS ' . number_format($dt->opening_balance, 2);
            })
            ->addColumn('movement_formatted', function ($dt) {
                $class = $dt->movement >= 0 ? 'text-danger' : 'text-success';
                return '<span class="' . $class . '">'
                    . ($dt->movement >= 0 ? '+' : '') . number_format($dt->movement, 2) . '</span>';
            })
            ->addColumn('closing_balance_formatted', function ($dt) {
                return 'TZS ' . number_format($dt->closing_balance, 2);
            })
            ->addColumn('is_posted_badge', function ($dt) {
                if ($dt->is_posted) {
                    return '<span class="badge bg-success">Posted</span>';
                }
                return '<span class="badge bg-secondary">Not Posted</span>';
            })
            ->addColumn('actions', function ($dt) {
                if ($dt->asset) {
                    $encodedId = Hashids::encode($dt->asset->id);
                    return '<a href="' . route('assets.registry.show', $encodedId)
                        . '" class="btn btn-sm btn-outline-primary" title="View Asset">'
                        . '<i class="bx bx-show"></i></a>';
                }
                return '';
            })
            ->rawColumns([
                'asset_name',
                'temporary_difference_formatted',
                'net_deferred_tax_formatted',
                'movement_formatted',
                'is_posted_badge',
                'actions'
            ])
            ->make(true);
    }
}
