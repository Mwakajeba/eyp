<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollPaymentApprovalSettings;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayrollPaymentApprovalSettingsController extends Controller
{
    public function index()
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = Auth::user()->branch_id ?? null;

        $settings = PayrollPaymentApprovalSettings::where('company_id', $userCompanyId)
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId)
                          ->orWhereNull('branch_id');
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->orderBy('branch_id', 'desc')
            ->with(['company', 'branch', 'creator', 'updater'])
            ->first();

        $companies = Company::all();
        $branches = Branch::where('company_id', $userCompanyId)->get();
        $users = User::where('company_id', $userCompanyId)
            ->with('branch')
            ->get();

        return view('hr-payroll.payment-approval-settings.index', compact('settings', 'companies', 'branches', 'users'));
    }

    public function store(Request $request)
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = Auth::user()->branch_id ?? null;

        $validated = $request->validate([
            'payment_approval_required' => 'boolean',
            'payment_approval_levels' => 'required|integer|min:1|max:5',
            'payment_level1_amount_threshold' => 'nullable|numeric|min:0',
            'payment_level1_approvers' => 'nullable|array',
            'payment_level1_approvers.*' => 'exists:users,id',
            'payment_level2_amount_threshold' => 'nullable|numeric|min:0',
            'payment_level2_approvers' => 'nullable|array',
            'payment_level2_approvers.*' => 'exists:users,id',
            'payment_level3_amount_threshold' => 'nullable|numeric|min:0',
            'payment_level3_approvers' => 'nullable|array',
            'payment_level3_approvers.*' => 'exists:users,id',
            'payment_level4_amount_threshold' => 'nullable|numeric|min:0',
            'payment_level4_approvers' => 'nullable|array',
            'payment_level4_approvers.*' => 'exists:users,id',
            'payment_level5_amount_threshold' => 'nullable|numeric|min:0',
            'payment_level5_approvers' => 'nullable|array',
            'payment_level5_approvers.*' => 'exists:users,id',
            'notes' => 'nullable|string|max:1000'
        ]);

        // Check if settings already exist
        $settings = PayrollPaymentApprovalSettings::where('company_id', $userCompanyId)
            ->where(function($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId)
                          ->orWhereNull('branch_id');
                } else {
                    $query->whereNull('branch_id');
                }
            })
            ->orderBy('branch_id', 'desc')
            ->first();

        $data = array_merge($validated, [
            'company_id' => $userCompanyId,
            'branch_id' => $userBranchId,
            'payment_approval_required' => $request->has('payment_approval_required'),
        ]);

        if ($settings) {
            $data['updated_by'] = Auth::id();
            $settings->update($data);
            $message = 'Payment approval settings updated successfully';
        } else {
            $data['created_by'] = Auth::id();
            $settings = PayrollPaymentApprovalSettings::create($data);
            $message = 'Payment approval settings created successfully';
        }

        return redirect()->route('hr-payroll.payment-approval-settings.index')
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
