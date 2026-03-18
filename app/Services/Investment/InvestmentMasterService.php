<?php

namespace App\Services\Investment;

use App\Models\Investment\InvestmentMaster;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentMasterService
{
    /**
     * Create a new investment master record
     */
    public function create(array $data, User $user): InvestmentMaster
    {
        DB::beginTransaction();
        try {
            $data['company_id'] = $user->company_id;
            $data['branch_id'] = $data['branch_id'] ?? $user->branch_id ?? session('branch_id');
            $data['created_by'] = $user->id;
            $data['status'] = $data['status'] ?? 'DRAFT';

            $investment = InvestmentMaster::create($data);

            DB::commit();
            return $investment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create investment master', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update an investment master record
     */
    public function update(InvestmentMaster $investment, array $data, User $user): InvestmentMaster
    {
        // Only allow updates if investment is in DRAFT status
        if ($investment->status !== 'DRAFT') {
            throw new \Exception('Cannot update investment that is not in DRAFT status');
        }

        DB::beginTransaction();
        try {
            $data['updated_by'] = $user->id;
            $investment->update($data);

            DB::commit();
            return $investment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update investment master', [
                'investment_id' => $investment->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete an investment master record (soft delete)
     */
    public function delete(InvestmentMaster $investment, User $user): bool
    {
        // Only allow deletion if investment is in DRAFT status
        if ($investment->status !== 'DRAFT') {
            throw new \Exception('Cannot delete investment that is not in DRAFT status');
        }

        DB::beginTransaction();
        try {
            $investment->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete investment master', [
                'investment_id' => $investment->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get investments with filters
     */
    public function getInvestments(array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $query = InvestmentMaster::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['instrument_type'])) {
            $query->where('instrument_type', $filters['instrument_type']);
        }

        if (isset($filters['accounting_class'])) {
            $query->where('accounting_class', $filters['accounting_class']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('instrument_code', 'like', "%{$search}%")
                  ->orWhere('issuer', 'like', "%{$search}%")
                  ->orWhere('isin', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}

