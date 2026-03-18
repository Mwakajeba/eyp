<?php

namespace App\Http\Controllers;

use App\Models\ImprestApprovalSettings;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImprestApprovalSettingsController extends Controller
{
    public function index()
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = session('branch_id');

        info('userBranchId', [$userBranchId]);

        $settings = ImprestApprovalSettings::where('company_id', $userCompanyId)
            ->where(function ($query) use ($userBranchId) {
                if ($userBranchId) {
                    $query->where('branch_id', $userBranchId);
                } else {
                    $query->where('branch_id', $userBranchId);
                }
            })
            ->with(['company', 'branch', 'creator', 'updater'])
            ->first();

        $companies = Company::all();
        $branches = Branch::where('company_id', $userCompanyId)->get();
        $users = User::where('company_id', $userCompanyId)->get();

        return view('imprest.multi-approval-settings.index', compact('settings', 'companies', 'branches', 'users'));
    }

    public function store(Request $request)
    {
        $userCompanyId = Auth::user()->company_id;
        $userBranchId = session('branch_id');

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

        // Check if settings already exist for this company/branch combination
        $existingQuery = ImprestApprovalSettings::where('company_id', $userCompanyId);

        if ($userBranchId) {
            $existingQuery->where('branch_id', $userBranchId);
        } else {
            $existingQuery->whereNull('branch_id');
        }

        $settings = $existingQuery->first();

        // If user has no branch (branch_id is null), check if there's already a company-wide setting
        if (!$userBranchId && !$settings) {
            $companyWideExists = ImprestApprovalSettings::where('company_id', $userCompanyId)
                ->whereNull('branch_id')
                ->exists();

            if ($companyWideExists) {
                return redirect()->route('imprest.multi-approval-settings.index')
                    ->with('error', 'Company-wide approval settings already exist. Only one company-wide setting is allowed.');
            }
        }

        // Prepare data for saving
        $data = [
            'company_id' => $userCompanyId,
            'branch_id' => $userBranchId,
            'approval_required' => $request->has('approval_required'),
            'approval_levels' => $validated['approval_levels'],
            'notes' => $validated['notes'] ?? null,
        ];

        // Add amount thresholds and approvers for each level
        for ($i = 1; $i <= 5; $i++) {
            $thresholdKey = "level{$i}_amount_threshold";
            $approversKey = "level{$i}_approvers";

            // Always set the threshold (even if null)
            $data[$thresholdKey] = $validated[$thresholdKey] ?? null;

            // Handle approvers array - ensure it's properly formatted as JSON
            if (isset($validated[$approversKey]) && is_array($validated[$approversKey])) {
                // Filter out empty values and ensure all are integers
                $approvers = array_filter($validated[$approversKey], function ($value) {
                    return !empty($value) && is_numeric($value);
                });
                $approvers = array_map('intval', $approvers);
                $data[$approversKey] = !empty($approvers) ? array_values($approvers) : null;
            } else {
                $data[$approversKey] = null;
            }
        }

        if ($settings) {
            $data['updated_by'] = Auth::id();
            $settings->update($data);
            $message = 'Approval settings updated successfully';
        } else {
            try {
                $data['created_by'] = Auth::id();
                $settings = ImprestApprovalSettings::create($data);
                $message = 'Approval settings created successfully';
            } catch (\Illuminate\Database\QueryException $e) {
                // Handle unique constraint violation
                if ($e->getCode() == 23000) {
                    return redirect()->route('imprest.multi-approval-settings.index')
                        ->with('error', 'Approval settings for this company/branch combination already exist.');
                }
                throw $e;
            } catch (\Exception $e) {
                Log::error('Failed to save imprest approval settings', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $data
                ]);
                return redirect()->route('imprest.multi-approval-settings.index')
                    ->with('error', 'Failed to save approval settings: ' . $e->getMessage());
            }
        }

        return redirect()->route('imprest.multi-approval-settings.index')
            ->with('success', $message);
    }

    public function getUsersByBranch(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $branch_id = session('branch_id');

        $users = User::where('company_id', $companyId)
            ->select('id', 'name', 'email', 'branch_id')
            ->get();

        return response()->json($users);
    }
}
