<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\TimesheetApprovalSettings;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimesheetApprovalSettingsController extends Controller
{
    public function index()
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = Auth::user()->branch_id ?? null;

        $settings = TimesheetApprovalSettings::where('company_id', $userCompanyId)
            ->where(function ($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->with(['company', 'branch', 'creator', 'updater'])
            ->first();

        $companies = Company::all();
        $branches = Branch::where('company_id', $userCompanyId)->get();
        $users = User::where('company_id', $userCompanyId)
            ->with('branch')
            ->get();

        return view('hr-payroll.timesheet-approval-settings.index', compact('settings', 'companies', 'branches', 'users'));
    }

    public function store(Request $request)
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = Auth::user()->branch_id ?? null;

        $validated = $request->validate([
            'approval_required' => 'boolean',
            'approvers' => 'nullable|array',
            'approvers.*' => 'exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $settings = TimesheetApprovalSettings::where('company_id', $userCompanyId)
            ->where(function ($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->first();

        $data = array_merge($validated, [
            'company_id' => $userCompanyId,
            'branch_id' => $userBranchId,
            'approval_required' => $request->has('approval_required'),
        ]);

        if ($settings) {
            $data['updated_by'] = Auth::id();
            $settings->update($data);
            $message = 'Timesheet approval settings updated successfully';
        } else {
            $data['created_by'] = Auth::id();
            $settings = TimesheetApprovalSettings::create($data);
            $message = 'Timesheet approval settings created successfully';
        }

        return redirect()->route('hr-payroll.timesheet-approval-settings.index')
            ->with('success', $message);
    }

    public function getUsersByBranch(Request $request)
    {
        $companyId = Auth::user()->company_id;

        $users = User::where('company_id', $companyId)
            ->with('branch')
            ->select('id', 'name', 'email', 'branch_id')
            ->get();

        return response()->json($users);
    }
}
