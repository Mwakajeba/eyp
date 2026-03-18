<?php

namespace App\Http\Controllers\Assets\Hfs;

use App\Http\Controllers\Controller;
use App\Models\Assets\HfsRequest;
use App\Services\Assets\Hfs\HfsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class HfsValuationController extends Controller
{
    protected $hfsService;

    public function __construct(HfsService $hfsService)
    {
        $this->hfsService = $hfsService;
    }

    /**
     * Show the form for creating a new valuation
     */
    public function create($hfsId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::with(['hfsAssets.asset', 'latestValuation'])->findOrFail($decodedId);

        if (!in_array($hfsRequest->status, ['approved', 'in_review'])) {
            return redirect()
                ->route('assets.hfs.requests.show', $hfsId)
                ->with('error', 'Valuation can only be performed on approved HFS requests.');
        }

        $encodedId = Hashids::encode($hfsRequest->id);

        return view('assets.hfs.valuations.create', compact('hfsRequest', 'encodedId'));
    }

    /**
     * Store a newly created valuation
     */
    public function store(Request $request, $hfsId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        if (!in_array($hfsRequest->status, ['approved', 'in_review'])) {
            return redirect()
                ->back()
                ->with('error', 'Valuation can only be performed on approved HFS requests.');
        }

        $validated = $request->validate([
            'valuation_date' => 'required|date',
            'fair_value' => 'required|numeric|min:0',
            'costs_to_sell' => 'required|numeric|min:0',
            'valuator_name' => 'nullable|string|max:255',
            'valuator_license' => 'nullable|string|max:255',
            'valuator_company' => 'nullable|string|max:255',
            'report_ref' => 'nullable|string|max:255',
            'valuation_report_path' => 'nullable|string',
            'is_override' => 'nullable|boolean',
            'override_reason' => 'nullable|string|required_if:is_override,true',
            'notes' => 'nullable|string',
        ]);

        try {
            $result = $this->hfsService->measureHfs($hfsRequest, $validated);

            return redirect()
                ->route('assets.hfs.requests.show', Hashids::encode($hfsRequest->id))
                ->with('success', 'Valuation recorded successfully. ' . 
                    ($result['impairment_amount'] > 0 
                        ? ($result['is_reversal'] ? 'Reversal' : 'Impairment') . ' of ' . number_format($result['impairment_amount'], 2) . ' posted.'
                        : 'No impairment required.'));

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to record valuation: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing valuation (for reversals)
     */
    public function update(Request $request, $hfsId, $valuationId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        $validated = $request->validate([
            'fair_value' => 'required|numeric|min:0',
            'costs_to_sell' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            // Create a new valuation record (measurement history)
            $result = $this->hfsService->measureHfs($hfsRequest, array_merge($validated, [
                'valuation_date' => now(),
            ]));

            return redirect()
                ->route('assets.hfs.requests.show', Hashids::encode($hfsRequest->id))
                ->with('success', 'Valuation updated successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update valuation: ' . $e->getMessage());
        }
    }
}
