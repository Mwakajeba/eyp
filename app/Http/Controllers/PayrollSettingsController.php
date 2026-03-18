<?php

namespace App\Http\Controllers;

use App\Models\PayrollApprovalSettings;
use App\Models\TimesheetApprovalSettings;
use App\Models\VacancyRequisitionApprovalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollSettingsController extends Controller
{
    public function index()
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = Auth::user()->branch_id ?? null;

        // Get current approval settings for display
        $approvalSettings = PayrollApprovalSettings::where('company_id', $userCompanyId)
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->first();

        // Get current payment approval settings for display
        $paymentApprovalSettings = \App\Models\PayrollPaymentApprovalSettings::getSettingsForCompany(
            $userCompanyId,
            $userBranchId
        );

        // Get current overtime approval settings for display
        $overtimeApprovalSettings = \App\Models\OvertimeApprovalSettings::where('company_id', $userCompanyId)
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->first();

        // Get current timesheet approval settings for display
        try {
            $timesheetApprovalSettings = TimesheetApprovalSettings::getSettingsForCompany($userCompanyId, $userBranchId);
        } catch (\Exception $e) {
            // Handle case where table structure might be different (e.g., missing company_id column)
            // This can happen if migration hasn't been run on the server
            \Log::warning('Error fetching timesheet approval settings: ' . $e->getMessage());
            $timesheetApprovalSettings = null;
        }

        // Get current vacancy requisition approval settings for display
        $vacancyRequisitionApprovalSettings = VacancyRequisitionApprovalSettings::where('company_id', $userCompanyId)
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->first();

        return view('hr-payroll.payroll-settings.index', compact(
            'approvalSettings',
            'paymentApprovalSettings',
            'overtimeApprovalSettings',
            'timesheetApprovalSettings',
            'vacancyRequisitionApprovalSettings'
        ));
    }
}
