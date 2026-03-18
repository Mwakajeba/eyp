<?php

namespace App\Http\Controllers\Assets\Hfs;

use App\Http\Controllers\Controller;
use App\Models\Assets\HfsRequest;
use App\Services\Assets\Hfs\HfsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;

class HfsDiscontinuedController extends Controller
{
    protected $hfsService;

    public function __construct(HfsService $hfsService)
    {
        $this->hfsService = $hfsService;
    }

    /**
     * Tag disposal group as discontinued operation
     */
    public function tagAsDiscontinued(Request $request, $hfsId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        if (!$hfsRequest->is_disposal_group) {
            return response()->json([
                'success' => false,
                'message' => 'Only disposal groups can be tagged as discontinued operations.'
            ], 400);
        }

        $validated = $request->validate([
            'effects_on_pnl' => 'nullable|array',
            'effects_on_pnl.revenue' => 'nullable|numeric',
            'effects_on_pnl.expenses' => 'nullable|numeric',
            'effects_on_pnl.pre_tax_profit' => 'nullable|numeric',
            'effects_on_pnl.tax' => 'nullable|numeric',
            'effects_on_pnl.post_tax_profit' => 'nullable|numeric',
            'effects_on_pnl.gain_loss_on_disposal' => 'nullable|numeric',
            'effects_on_pnl.total_impact' => 'nullable|numeric',
        ]);

        try {
            $discontinuedFlag = $this->hfsService->tagAsDiscontinued(
                $hfsRequest,
                $validated['effects_on_pnl'] ?? []
            );

            return response()->json([
                'success' => true,
                'message' => 'Disposal group tagged as discontinued operation successfully.',
                'discontinued_flag' => $discontinuedFlag
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to tag as discontinued: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update discontinued criteria checks
     */
    public function updateCriteria(Request $request, $hfsId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        $validated = $request->validate([
            'criteria_checked' => 'required|array',
            'criteria_checked.is_component' => 'required|boolean',
            'criteria_checked.represents_separate_major_line' => 'required|boolean',
            'criteria_checked.is_part_of_single_plan' => 'required|boolean',
            'criteria_checked.is_disposed_or_classified_hfs' => 'required|boolean',
        ]);

        try {
            $discontinuedFlag = $hfsRequest->discontinuedFlag;
            if (!$discontinuedFlag) {
                $discontinuedFlag = $hfsRequest->discontinuedFlag()->create([
                    'is_discontinued' => false,
                    'criteria_checked' => $validated['criteria_checked'],
                    'created_by' => Auth::id(),
                ]);
            } else {
                $discontinuedFlag->update([
                    'criteria_checked' => $validated['criteria_checked'],
                ]);
            }

            // Check if criteria are met
            $meetsCriteria = $this->hfsService->checkDiscontinuedCriteria($hfsRequest);

            return response()->json([
                'success' => true,
                'message' => 'Criteria updated successfully.',
                'meets_criteria' => $meetsCriteria['meets_criteria'],
                'criteria' => $meetsCriteria['criteria']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update criteria: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Check discontinued criteria
     */
    public function checkCriteria($hfsId)
    {
        $decodedId = Hashids::decode($hfsId)[0] ?? $hfsId;
        $hfsRequest = HfsRequest::findOrFail($decodedId);

        $result = $this->hfsService->checkDiscontinuedCriteria($hfsRequest);

        return response()->json($result);
    }
}
