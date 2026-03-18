<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Hr\AllowanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Vinkla\Hashids\Facades\Hashids;

class AllowanceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allowanceTypes = AllowanceType::where('company_id', Auth::user()->company_id)
            ->orderBy('name')
            ->paginate(15);

        return view('hr-payroll.allowance-types.index', compact('allowanceTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.allowance-types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ]);

        AllowanceType::create([
            'company_id' => Auth::user()->company_id,
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'type' => $request->type,
            'is_taxable' => $request->boolean('is_taxable'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('hr.allowance-types.index')
            ->with('success', 'Allowance type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowanceType = AllowanceType::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        return view('hr-payroll.allowance-types.show', compact('allowanceType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowanceType = AllowanceType::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        return view('hr-payroll.allowance-types.edit', compact('allowanceType'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowanceType = AllowanceType::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:fixed,percentage',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $allowanceType->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'type' => $request->type,
            'is_taxable' => $request->boolean('is_taxable'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('hr.allowance-types.index')
            ->with('success', 'Allowance type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $allowanceType = AllowanceType::where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        // Check if allowance type is being used
        // You can add this check later when you have employee allowances

        $allowanceType->delete();

        return redirect()->route('hr.allowance-types.index')
            ->with('success', 'Allowance type deleted successfully.');
    }
}
