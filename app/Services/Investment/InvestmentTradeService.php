<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\Investment\InvestmentTrade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InvestmentTradeService
{
    /**
     * Create a new investment trade
     */
    public function create(array $data, User $user): InvestmentTrade
    {
        DB::beginTransaction();
        try {
            // Get investment to process category-specific data
            $investment = null;
            if (isset($data['investment_id']) && $data['investment_id']) {
                $investment = InvestmentMaster::find($data['investment_id']);
            }

            // Set base fields
            $data['company_id'] = $user->company_id;
            $data['branch_id'] = $data['branch_id'] ?? session('branch_id') ?? $user->branch_id;
            $data['settlement_status'] = $data['settlement_status'] ?? 'PENDING';
            $data['created_by'] = $user->id;

            // Calculate gross amount if not provided
            if (!isset($data['gross_amount']) || $data['gross_amount'] == 0) {
                $data['gross_amount'] = $data['trade_units'] * $data['trade_price'];
            }

            // Process category-specific data
            if ($investment) {
                $data = $this->processCategorySpecificData($data, $investment);
            }

            // Clean up data - remove null values for optional fields to avoid issues
            $data = array_filter($data, function($value) {
                return $value !== null && $value !== '';
            });

            $trade = InvestmentTrade::create($data);

            // If this is a purchase trade and investment_id is provided, update investment status
            if ($trade->trade_type === 'PURCHASE' && $trade->investment_id) {
                $investment = InvestmentMaster::find($trade->investment_id);
                if ($investment && $investment->status === 'DRAFT') {
                    $investment->status = 'ACTIVE';
                    $investment->purchase_date = $trade->trade_date;
                    $investment->settlement_date = $trade->settlement_date;
                    $investment->purchase_price = $trade->trade_price;
                    $investment->units = $trade->trade_units;
                    $investment->nominal_amount = $trade->gross_amount;
                    $investment->updated_by = $user->id;
                    $investment->save();
                }
            }

            DB::commit();
            return $trade;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create investment trade', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update trade settlement status
     */
    public function updateSettlementStatus(InvestmentTrade $trade, string $status, ?string $bankRef = null): InvestmentTrade
    {
        $validStatuses = ['PENDING', 'INSTRUCTED', 'SETTLED', 'FAILED'];
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Invalid settlement status');
        }

        DB::beginTransaction();
        try {
            $trade->settlement_status = $status;
            if ($bankRef) {
                $trade->bank_ref = $bankRef;
            }
            $trade->save();

            DB::commit();
            return $trade->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update settlement status', [
                'trade_id' => $trade->trade_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark trade as settled
     */
    public function markAsSettled(InvestmentTrade $trade, string $bankRef): InvestmentTrade
    {
        return $this->updateSettlementStatus($trade, 'SETTLED', $bankRef);
    }

    /**
     * Get trades with filters
     */
    public function getTrades(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = InvestmentTrade::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['investment_id'])) {
            $query->where('investment_id', $filters['investment_id']);
        }

        if (isset($filters['trade_type'])) {
            $query->where('trade_type', $filters['trade_type']);
        }

        if (isset($filters['settlement_status'])) {
            $query->where('settlement_status', $filters['settlement_status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('trade_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('trade_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Process category-specific data based on instrument type
     */
    protected function processCategorySpecificData(array $data, InvestmentMaster $investment): array
    {
        $instrumentType = $investment->instrument_type;

        switch ($instrumentType) {
            case 'T_BOND':
            case 'CORP_BOND':
                // Ensure coupon_rate is set if available from investment master
                if (!isset($data['coupon_rate']) && $investment->coupon_rate) {
                    $data['coupon_rate'] = $investment->coupon_rate;
                }
                // Calculate premium/discount if not provided
                if (!isset($data['premium_discount']) && isset($data['trade_price']) && isset($data['trade_units'])) {
                    $faceValue = $data['trade_units']; // Assuming trade_units is face value for bonds
                    $purchaseValue = $data['gross_amount'] ?? ($data['trade_price'] * $data['trade_units']);
                    $data['premium_discount'] = $purchaseValue - $faceValue;
                }
                break;

            case 'T_BILL':
                // T-Bills are zero-coupon, so ensure coupon fields are null
                $data['coupon_rate'] = null;
                $data['coupon_frequency'] = null;
                // Calculate yield if discount rate is provided
                if (isset($data['discount_rate']) && isset($data['maturity_days']) && !isset($data['yield_rate'])) {
                    $days = (int)$data['maturity_days'];
                    if ($days > 0) {
                        // Simple yield calculation: (discount / (1 - discount * days/365)) * 365/days
                        $discount = $data['discount_rate'] / 100;
                        $data['yield_rate'] = ($discount / (1 - $discount * $days / 365)) * (365 / $days) * 100;
                    }
                }
                break;

            case 'FIXED_DEPOSIT':
                // Set default interest computation method if not provided
                if (!isset($data['interest_computation_method'])) {
                    $data['interest_computation_method'] = 'SIMPLE';
                }
                // Set default payout frequency if not provided
                if (!isset($data['payout_frequency'])) {
                    $data['payout_frequency'] = 'END_MATURITY';
                }
                // Convert checkbox values to boolean
                $data['collateral_flag'] = isset($data['collateral_flag']) && $data['collateral_flag'] == '1';
                $data['rollover_option'] = isset($data['rollover_option']) && $data['rollover_option'] == '1';
                break;

            case 'EQUITY':
                // For equity, number_of_shares should match trade_units
                if (!isset($data['number_of_shares']) && isset($data['trade_units'])) {
                    $data['number_of_shares'] = $data['trade_units'];
                }
                // Purchase price per share should match trade_price
                if (!isset($data['purchase_price_per_share']) && isset($data['trade_price'])) {
                    $data['purchase_price_per_share'] = $data['trade_price'];
                }
                // Calculate fair value if not provided (for FVPL/FVOCI)
                if (!isset($data['fair_value']) && isset($data['number_of_shares']) && isset($data['purchase_price_per_share'])) {
                    // Default to purchase value, will be updated with market price later
                    $data['fair_value'] = $data['number_of_shares'] * $data['purchase_price_per_share'];
                }
                // Equity is not ECL-scoped
                $data['ecl_not_applicable_flag'] = true;
                $data['impairment_indicator'] = isset($data['impairment_indicator']) && $data['impairment_indicator'] == '1';
                break;

            case 'MMF':
                // Units purchased should match trade_units
                if (!isset($data['units_purchased']) && isset($data['trade_units'])) {
                    $data['units_purchased'] = $data['trade_units'];
                }
                // Unit price should match trade_price
                if (!isset($data['unit_price']) && isset($data['trade_price'])) {
                    $data['unit_price'] = $data['trade_price'];
                }
                // Calculate fair value using NAV if provided
                if (isset($data['nav_price']) && isset($data['units_purchased'])) {
                    $data['fair_value'] = $data['nav_price'] * $data['units_purchased'];
                } elseif (!isset($data['fair_value']) && isset($data['units_purchased']) && isset($data['unit_price'])) {
                    $data['fair_value'] = $data['units_purchased'] * $data['unit_price'];
                }
                // MMF is typically FVPL, not ECL-scoped
                $data['ecl_not_applicable_flag'] = true;
                break;

            case 'COMMERCIAL_PAPER':
                // Similar to T-Bills, zero-coupon
                $data['coupon_rate'] = null;
                $data['coupon_frequency'] = null;
                break;
        }

        // Process IFRS 9 ECL fields (for applicable categories)
        if (!in_array($instrumentType, ['EQUITY', 'MMF'])) {
            // Set default stage if not provided
            if (!isset($data['stage'])) {
                $data['stage'] = 1; // Default to Stage 1 (performing)
            }
            
            // Calculate ECL if PD, LGD, and EAD are provided
            if (isset($data['pd']) && isset($data['lgd']) && isset($data['ead']) && !isset($data['ecl_amount'])) {
                $data['ecl_amount'] = ($data['pd'] / 100) * ($data['lgd'] / 100) * $data['ead'];
            }
        }

        return $data;
    }
}

