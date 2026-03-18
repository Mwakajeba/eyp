<?php

namespace App\Services\Assets\Hfs;

use App\Models\Assets\HfsRequest;
use App\Models\Assets\HfsAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Service for managing HFS alerts and auto-flags
 * - 12-month rule monitoring
 * - Overdue HFS items
 * - Approaching deadline alerts
 */
class HfsAlertService
{
    /**
     * Check for HFS items that are approaching or have exceeded 12 months
     * 
     * @param int|null $companyId
     * @return array ['overdue' => [], 'approaching' => []]
     */
    public function check12MonthRule($companyId = null): array
    {
        $overdue = [];
        $approaching = [];

        $query = HfsRequest::where('status', '!=', 'CANCELLED')
            ->where('status', '!=', 'SOLD')
            ->where('status', '!=', 'REJECTED');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $hfsRequests = $query->with('hfsAssets')->get();

        foreach ($hfsRequests as $hfsRequest) {
            // Get the earliest reclassification date from assets
            $earliestReclassDate = $hfsRequest->hfsAssets
                ->whereNotNull('reclassified_date')
                ->min('reclassified_date');

            if (!$earliestReclassDate) {
                continue;
            }

            $reclassDate = Carbon::parse($earliestReclassDate);
            $monthsSinceReclass = now()->diffInMonths($reclassDate, false);

            // Check if exceeded 12 months
            if ($monthsSinceReclass > 12) {
                $overdue[] = [
                    'hfs_request' => $hfsRequest,
                    'months_overdue' => $monthsSinceReclass - 12,
                    'reclassified_date' => $reclassDate,
                    'requires_senior_approval' => true,
                ];
            } 
            // Check if approaching 12 months (11 months or more)
            elseif ($monthsSinceReclass >= 11) {
                $approaching[] = [
                    'hfs_request' => $hfsRequest,
                    'months_elapsed' => $monthsSinceReclass,
                    'months_remaining' => 12 - $monthsSinceReclass,
                    'reclassified_date' => $reclassDate,
                ];
            }
        }

        return [
            'overdue' => $overdue,
            'approaching' => $approaching,
        ];
    }

    /**
     * Flag HFS request as overdue and require senior approval
     */
    public function flagAsOverdue(HfsRequest $hfsRequest): void
    {
        // Update request with overdue flag
        $hfsRequest->is_overdue = true;
        $hfsRequest->overdue_notified_at = now();
        $hfsRequest->save();

        // Log the flagging
        Log::warning("HFS Request flagged as overdue", [
            'hfs_request_id' => $hfsRequest->id,
            'request_no' => $hfsRequest->request_no,
            'reclassified_date' => $hfsRequest->hfsAssets->min('reclassified_date'),
        ]);
    }

    /**
     * Send alerts for approaching 12-month deadline
     */
    public function sendApproachingDeadlineAlerts($companyId = null): void
    {
        $alerts = $this->check12MonthRule($companyId);

        foreach ($alerts['approaching'] as $alert) {
            $hfsRequest = $alert['hfs_request'];
            
            // Only send alert if not already sent recently (e.g., within last 7 days)
            $lastAlertSent = $hfsRequest->last_alert_sent_at;
            if ($lastAlertSent && Carbon::parse($lastAlertSent)->diffInDays(now()) < 7) {
                continue;
            }

            // Update last alert sent date
            $hfsRequest->last_alert_sent_at = now();
            $hfsRequest->save();

            // TODO: Send notification to relevant users (initiator, approvers, finance manager)
            // This would integrate with the notification system
            Log::info("HFS approaching deadline alert sent", [
                'hfs_request_id' => $hfsRequest->id,
                'request_no' => $hfsRequest->request_no,
                'months_remaining' => $alert['months_remaining'],
            ]);
        }
    }

    /**
     * Check and flag all overdue HFS items
     */
    public function checkAndFlagOverdue($companyId = null): void
    {
        $alerts = $this->check12MonthRule($companyId);

        foreach ($alerts['overdue'] as $alert) {
            $hfsRequest = $alert['hfs_request'];
            
            // Only flag if not already flagged
            if (!$hfsRequest->is_overdue) {
                $this->flagAsOverdue($hfsRequest);
            }
        }
    }

    /**
     * Get all overdue HFS requests requiring senior approval
     */
    public function getOverdueRequiringApproval($companyId = null): array
    {
        $query = HfsRequest::where('is_overdue', true)
            ->where('status', '!=', 'CANCELLED')
            ->where('status', '!=', 'SOLD');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->with(['hfsAssets.asset', 'initiator'])
            ->orderBy('overdue_notified_at', 'desc')
            ->get()
            ->map(function($hfsRequest) {
                $earliestReclassDate = $hfsRequest->hfsAssets
                    ->whereNotNull('reclassified_date')
                    ->min('reclassified_date');
                
                $monthsOverdue = $earliestReclassDate 
                    ? now()->diffInMonths(Carbon::parse($earliestReclassDate)) - 12
                    : 0;

                return [
                    'hfs_request' => $hfsRequest,
                    'request_no' => $hfsRequest->request_no,
                    'months_overdue' => max(0, $monthsOverdue),
                    'reclassified_date' => $earliestReclassDate,
                    'requires_senior_approval' => true,
                ];
            })
            ->toArray();
    }
}

