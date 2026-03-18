<?php

namespace App\Http\Controllers;

use App\Models\PayrollApprovalSettings;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollApprovalSettingsController extends Controller
{
    public function index()
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = Auth::user()->branch_id ?? null; // Handle null branch_id

        $settings = PayrollApprovalSettings::where('company_id', $userCompanyId)
            ->where(function($query) use ($userBranchId) {
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

        return view('hr-payroll.approval-settings.index', compact('settings', 'companies', 'branches', 'users'));
    }

    public function store(Request $request)
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = Auth::user()->branch_id ?? null; // Handle null branch_id

        $validated = $request->validate([
            'approval_required' => 'boolean',
            'approval_levels' => 'required|integer|min:1|max:5',
            'level1_amount_threshold' => 'nullable|numeric|min:0',
            'level1_approvers' => 'nullable|array',
            'level1_approvers.*' => 'exists:users,id',
            'level2_amount_threshold' => 'nullable|numeric|min:0',
            'level2_approvers' => 'nullable|array',
            'level2_approvers.*' => 'exists:users,id',
            'level3_amount_threshold' => 'nullable|numeric|min:0',
            'level3_approvers' => 'nullable|array',
            'level3_approvers.*' => 'exists:users,id',
            'level4_amount_threshold' => 'nullable|numeric|min:0',
            'level4_approvers' => 'nullable|array',
            'level4_approvers.*' => 'exists:users,id',
            'level5_amount_threshold' => 'nullable|numeric|min:0',
            'level5_approvers' => 'nullable|array',
            'level5_approvers.*' => 'exists:users,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Check if settings already exist
        $settings = PayrollApprovalSettings::where('company_id', $userCompanyId)
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->first();

        $data = array_merge($validated, [
            'company_id' => $userCompanyId,
            'branch_id' => $userBranchId, // This can now be null
            'approval_required' => $request->has('approval_required'),
        ]);

        if ($settings) {
            $data['updated_by'] = Auth::id();
            $settings->update($data);
            $message = 'Payroll approval settings updated successfully';
        } else {
            $data['created_by'] = Auth::id();
            $settings = PayrollApprovalSettings::create($data);
            $message = 'Payroll approval settings created successfully';
        }

        return redirect()->route('hr-payroll.approval-settings.index')
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
