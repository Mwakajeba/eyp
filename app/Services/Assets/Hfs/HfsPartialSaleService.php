<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsAsset;
use App\Models\Assets\HfsDisposal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling partial sales of HFS disposal groups
 * - Partial sale of disposal group
 * - Update remaining items
 * - Reclassify remaining items if needed
 */
class HfsPartialSaleService
{
    /**
     * Process partial sale of HFS disposal group
     * 
     * @param HfsRequest $hfsRequest
     * @param array $saleData ['sale_proceeds', 'partial_sale_percentage', 'assets_sold' => [asset_ids]]
     * @return array
     */
    public function processPartialSale(HfsRequest $hfsRequest, array $saleData): array
    {
        DB::beginTransaction();
        try {
            $partialPercentage = $saleData['partial_sale_percentage'] ?? 0;
            $assetsSoldIds = $saleData['assets_sold'] ?? [];
            
            if (empty($assetsSoldIds) && $partialPercentage <= 0) {
                throw new \Exception("Either assets_sold array or partial_sale_percentage must be provided");
            }

            // Get assets to be sold
            $assetsToSell = [];
            $assetsToKeep = [];

            if (!empty($assetsSoldIds)) {
                // Specific assets selected for sale
                foreach ($hfsRequest->hfsAssets as $hfsAsset) {
                    if (in_array($hfsAsset->asset_id, $assetsSoldIds)) {
                        $assetsToSell[] = $hfsAsset;
                    } else {
                        $assetsToKeep[] = $hfsAsset;
                    }
                }
            } else {
                // Calculate based on percentage
                $totalAssets = $hfsRequest->hfsAssets->count();
                $assetsToSellCount = (int) ceil($totalAssets * ($partialPercentage / 100));
                
                $assetsToSell = $hfsRequest->hfsAssets->take($assetsToSellCount)->all();
                $assetsToKeep = $hfsRequest->hfsAssets->skip($assetsToSellCount)->all();
            }

            if (empty($assetsToSell)) {
                throw new \Exception("No assets selected for sale");
            }

            // Calculate carrying amounts
            $totalCarryingAmount = $hfsRequest->hfsAssets->sum('current_carrying_amount');
            $soldCarryingAmount = collect($assetsToSell)->sum('current_carrying_amount');
            $remainingCarryingAmount = collect($assetsToKeep)->sum('current_carrying_amount');

            // Calculate sale proceeds allocation
            $totalSaleProceeds = $saleData['sale_proceeds'] ?? 0;
            $soldProceeds = $totalSaleProceeds * ($soldCarryingAmount / $totalCarryingAmount);
            $remainingProceeds = $totalSaleProceeds - $soldProceeds;

            // Create disposal record for sold portion
            $disposal = HfsDisposal::create([
                'hfs_id' => $hfsRequest->id,
                'disposal_date' => $saleData['disposal_date'] ?? now(),
                'sale_proceeds' => $soldProceeds,
                'sale_currency' => $saleData['sale_currency'] ?? (\App\Models\SystemSetting::getValue('functional_currency', $hfsRequest->company->functional_currency ?? 'TZS')),
                'currency_rate' => $saleData['currency_rate'] ?? 1,
                'costs_sold' => $saleData['costs_sold'] ?? 0,
                'carrying_amount_at_disposal' => $soldCarryingAmount,
                'gain_loss_amount' => $soldProceeds - $soldCarryingAmount - ($saleData['costs_sold'] ?? 0),
                'is_partial_sale' => true,
                'partial_sale_percentage' => ($soldCarryingAmount / $totalCarryingAmount) * 100,
                'buyer_name' => $saleData['buyer_name'] ?? null,
                'settlement_reference' => $saleData['settlement_reference'] ?? null,
                'notes' => $saleData['notes'] ?? 'Partial sale of disposal group',
                'created_by' => auth()->id(),
            ]);

            // Update sold assets
            foreach ($assetsToSell as $hfsAsset) {
                if ($hfsAsset->asset_id) {
                    $asset = $hfsAsset->asset;
                    $asset->status = 'disposed';
                    $asset->hfs_status = 'sold';
                    $asset->save();
                }
                $hfsAsset->status = 'sold';
                $hfsAsset->save();
            }

            // Update remaining assets
            // Option 1: Keep them in HFS (if still meeting criteria)
            // Option 2: Reclassify back to original category
            $reclassifyRemaining = $saleData['reclassify_remaining'] ?? false;

            if ($reclassifyRemaining) {
                // Reclassify remaining assets back to original category
                foreach ($assetsToKeep as $hfsAsset) {
                    if ($hfsAsset->asset_id) {
                        $asset = $hfsAsset->asset;
                        $asset->hfs_status = 'none';
                        $asset->current_hfs_id = null;
                        $asset->depreciation_stopped = false;
                        $asset->save();
                    }
                    $hfsAsset->status = 'cancelled';
                    $hfsAsset->save();
                }
            } else {
                // Keep remaining assets in HFS
                // Update their carrying amounts proportionally if needed
                foreach ($assetsToKeep as $hfsAsset) {
                    // Assets remain in HFS status
                    $hfsAsset->status = 'classified';
                    $hfsAsset->save();
                }
            }

            // Update HFS request status
            if (empty($assetsToKeep) || $reclassifyRemaining) {
                $hfsRequest->status = 'sold';
            } else {
                // Partial sale - request remains active for remaining assets
                $hfsRequest->status = 'approved'; // Keep as approved for remaining assets
            }
            $hfsRequest->save();

            // Create disposal journal for sold portion
            $journalService = app(HfsJournalService::class);
            $journal = $journalService->createDisposalJournal($disposal);

            // Log partial sale
            Log::info("Partial sale processed", [
                'hfs_request_id' => $hfsRequest->id,
                'assets_sold_count' => count($assetsToSell),
                'assets_remaining_count' => count($assetsToKeep),
                'sold_carrying_amount' => $soldCarryingAmount,
                'remaining_carrying_amount' => $remainingCarryingAmount,
            ]);

            DB::commit();

            return [
                'success' => true,
                'disposal' => $disposal,
                'journal_id' => $journal->id,
                'assets_sold' => count($assetsToSell),
                'assets_remaining' => count($assetsToKeep),
                'reclassify_remaining' => $reclassifyRemaining,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partial sale processing error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if partial sale is valid
     */
    public function validatePartialSale(HfsRequest $hfsRequest, array $saleData): array
    {
        $errors = [];
        $warnings = [];

        if (!$hfsRequest->is_disposal_group) {
            $errors[] = "Partial sale only applies to disposal groups";
        }

        $assetsSoldIds = $saleData['assets_sold'] ?? [];
        $partialPercentage = $saleData['partial_sale_percentage'] ?? 0;

        if (empty($assetsSoldIds) && $partialPercentage <= 0) {
            $errors[] = "Either assets_sold array or partial_sale_percentage must be provided";
        }

        if (!empty($assetsSoldIds)) {
            // Validate that all selected assets belong to this HFS request
            $hfsAssetIds = $hfsRequest->hfsAssets->pluck('asset_id')->toArray();
            $invalidAssets = array_diff($assetsSoldIds, $hfsAssetIds);
            
            if (!empty($invalidAssets)) {
                $errors[] = "Some selected assets do not belong to this HFS request: " . implode(', ', $invalidAssets);
            }
        }

        if ($partialPercentage > 0 && ($partialPercentage < 1 || $partialPercentage > 100)) {
            $errors[] = "Partial sale percentage must be between 1 and 100";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}

