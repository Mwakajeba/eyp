<?php

namespace App\Helpers;

use Illuminate\Database\Query\Builder;

class FleetGlReportHelper
{
    /**
     * Exclude unapproved fleet GL transactions from financial reports.
     * Only fleet_trip_cost and fleet_fuel_log with approval_status = 'approved' are included.
     *
     * @param Builder $query Query builder on gl_transactions (or alias)
     * @param string $alias Table alias for gl_transactions (default 'gl_transactions')
     * @return Builder
     */
    public static function excludeUnapprovedFleet(Builder $query, string $alias = 'gl_transactions'): Builder
    {
        return $query->whereRaw(
            "({$alias}.transaction_type NOT IN ('fleet_trip_cost', 'fleet_fuel_log') " .
            "OR ({$alias}.transaction_type = 'fleet_trip_cost' AND EXISTS (SELECT 1 FROM fleet_trip_costs ftc WHERE ftc.id = {$alias}.transaction_id AND ftc.approval_status = 'approved')) " .
            "OR ({$alias}.transaction_type = 'fleet_fuel_log' AND EXISTS (SELECT 1 FROM fleet_fuel_logs ffl WHERE ffl.id = {$alias}.transaction_id AND ffl.approval_status = 'approved')))"
        );
    }
}
