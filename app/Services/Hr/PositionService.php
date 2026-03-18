<?php

namespace App\Services\Hr;

use App\Models\Hr\Position;
use App\Models\Hr\JobGrade;
use App\Models\Hr\PositionAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PositionService
{
    /**
     * Create or update position with headcount control
     */
    public function createOrUpdatePosition(array $positionData): Position
    {
        DB::beginTransaction();
        try {
            if (isset($positionData['id'])) {
                $position = Position::findOrFail($positionData['id']);
                $position->update($positionData);
            } else {
                $position = Position::create($positionData);
            }

            // Recalculate filled headcount
            $this->recalculateFilledHeadcount($position);

            DB::commit();
            return $position;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create/update position', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Recalculate filled headcount for a position
     */
    public function recalculateFilledHeadcount(Position $position): void
    {
        $filledCount = PositionAssignment::where('position_id', $position->id)
            ->where('is_acting', false)
            ->where('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            })
            ->count();

        $position->update(['filled_headcount' => $filledCount]);
    }

    /**
     * Check if position can accept new assignment
     */
    public function canAcceptAssignment(Position $position, bool $isActing = false): bool
    {
        if ($isActing) {
            return true; // Acting assignments don't count against headcount
        }

        return $position->hasAvailableHeadcount() && $position->isActive();
    }

    /**
     * Validate salary against grade band
     */
    public function validateSalaryAgainstGrade(?int $gradeId, float $salary): array
    {
        if (!$gradeId) {
            return ['valid' => true, 'message' => 'No grade assigned'];
        }

        $grade = JobGrade::find($gradeId);
        if (!$grade) {
            return ['valid' => false, 'message' => 'Grade not found'];
        }

        if (!$grade->isSalaryInRange($salary)) {
            $min = $grade->minimum_salary ? number_format($grade->minimum_salary, 2) : 'N/A';
            $max = $grade->maximum_salary ? number_format($grade->maximum_salary, 2) : 'N/A';
            return [
                'valid' => false,
                'message' => "Salary {$salary} is outside grade band ({$min} - {$max})"
            ];
        }

        return ['valid' => true, 'message' => 'Salary is within grade band'];
    }
}

