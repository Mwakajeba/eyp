<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\ExternalLoanInstitution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class ExternalLoanInstitutionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Check if this is a request for JSON data (for dropdown refresh)
            if ($request->wantsJson() || $request->has('json')) {
                $institutions = ExternalLoanInstitution::where('company_id', current_company_id())
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code']);
                
                return response()->json([
                    'success' => true,
                    'data' => $institutions
                ]);
            }
            
            $institutions = ExternalLoanInstitution::where('company_id', current_company_id())
                ->orderBy('name');

            return DataTables::of($institutions)
                ->addIndexColumn()
                ->addColumn('status_badge', function ($institution) {
                    if ($institution->is_active) {
                        return '<span class="badge bg-success">Active</span>';
                    }
                    return '<span class="badge bg-secondary">Inactive</span>';
                })
                ->addColumn('action', function ($institution) {
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('hr.external-loan-institutions.show', $institution->hash_id) . '" class="btn btn-sm btn-info" title="View">
                                    <i class="bx bx-show"></i>
                                 </a>';
                    $actions .= '<a href="' . route('hr.external-loan-institutions.edit', $institution->hash_id) . '" class="btn btn-sm btn-primary" title="Edit">
                                    <i class="bx bx-edit"></i>
                                 </a>';
                    $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteInstitution(\'' . $institution->hash_id . '\')" title="Delete">
                                    <i class="bx bx-trash"></i>
                                 </button>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('hr-payroll.external-loan-institutions.index');
    }

    public function create()
    {
        return view('hr-payroll.external-loan-institutions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:hr_external_loan_institutions,name,NULL,id,company_id,' . current_company_id(),
            'code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $institution = ExternalLoanInstitution::create([
            'company_id' => current_company_id(),
            'name' => $request->name,
            'code' => $request->code,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $request->notes,
        ]);

        // If AJAX request, return JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'External loan institution created successfully.',
                'institution' => [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'code' => $institution->code,
                ]
            ]);
        }

        return redirect()->route('hr.external-loan-institutions.index')
            ->with('success', 'External loan institution created successfully.');
    }

    public function show(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $institution = ExternalLoanInstitution::where('company_id', current_company_id())
            ->with('externalLoans.employee')
            ->findOrFail($id);

        return view('hr-payroll.external-loan-institutions.show', compact('institution'));
    }

    public function edit(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $institution = ExternalLoanInstitution::where('company_id', current_company_id())
            ->findOrFail($id);

        return view('hr-payroll.external-loan-institutions.edit', compact('institution'));
    }

    public function update(Request $request, string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $institution = ExternalLoanInstitution::where('company_id', current_company_id())
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:hr_external_loan_institutions,name,' . $id . ',id,company_id,' . current_company_id(),
            'code' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $institution->update([
            'name' => $request->name,
            'code' => $request->code,
            'contact_person' => $request->contact_person,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $request->notes,
        ]);

        return redirect()->route('hr.external-loan-institutions.index')
            ->with('success', 'External loan institution updated successfully.');
    }

    public function destroy(string $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        abort_if(!$id, 404);

        $institution = ExternalLoanInstitution::where('company_id', current_company_id())
            ->findOrFail($id);

        // Check if institution has loans
        if ($institution->externalLoans()->count() > 0) {
            return redirect()->route('hr.external-loan-institutions.index')
                ->with('error', 'Cannot delete institution with existing loans. Please remove or reassign loans first.');
        }

        $institution->delete();

        return redirect()->route('hr.external-loan-institutions.index')
            ->with('success', 'External loan institution deleted successfully.');
    }
}

