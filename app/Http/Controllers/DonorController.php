<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DonorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        $branchId = session('branch_id') ?? $user->branch_id;

        if ($request->ajax()) {
            $donors = Customer::with(['branch'])
                ->where('company_id', $companyId)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->latest();

            return DataTables::of($donors)
                ->addColumn('donor_avatar', function ($donor) {
                    return '<div class="d-flex align-items-center">
                        <div class="avatar avatar-sm bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center shadow" style="width:36px; height:36px;">
                            <span class="avatar-title text-white fw-bold" style="font-size:1.25rem;">' . strtoupper(substr($donor->name, 0, 1)) . '</span>
                        </div>
                        <div class="fw-bold">' . e($donor->name) . '</div>
                    </div>';
                })
                ->addColumn('status_badge', function ($donor) {
                    $class = match ($donor->status) {
                        'active' => 'bg-success',
                        'inactive' => 'bg-secondary',
                        'suspended' => 'bg-warning',
                        default => 'bg-secondary',
                    };
                    return '<span class="badge ' . $class . '">' . ucfirst($donor->status) . '</span>';
                })
                ->addColumn('formatted_phone', function ($donor) {
                    return $donor->phone ?: 'N/A';
                })
                ->editColumn('email', function ($donor) {
                    return $donor->email ?: 'N/A';
                })
                ->addColumn('projects_count', function ($donor) {
                    return $donor->donorProjects()->count();
                })
                ->addColumn('actions', function ($donor) {
                    $encoded = Hashids::encode($donor->id);
                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<a href="' . route('projects.donors.show', $encoded) . '" class="btn btn-sm btn-outline-info" title="View"><i class="bx bx-show"></i></a>';
                    $actions .= '<a href="' . route('projects.donors.edit', $encoded) . '" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bx bx-edit"></i></a>';
                    $actions .= '<form action="' . route('projects.donors.destroy', $encoded) . '" method="POST" class="d-inline-block delete-form">'
                        . csrf_field() . method_field('DELETE')
                        . '<button class="btn btn-sm btn-outline-danger" title="Delete" data-name="' . e($donor->name) . '"><i class="bx bx-trash"></i></button></form>';
                    $actions .= '</div>';
                    return $actions;
                })
                ->rawColumns(['donor_avatar', 'status_badge', 'actions'])
                ->make(true);
        }

        $totalDonors = Customer::where('company_id', $companyId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        return view('projects.donors.index', compact('totalDonors'));
    }

    public function create()
    {
        return view('projects.donors.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        if ($request->filled('phone') && function_exists('normalize_phone_number')) {
            $request->merge(['phone' => normalize_phone_number($request->input('phone'))]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('customers', 'email')->where(fn ($q) => $q->where('company_id', $companyId)),
            ],
            'status' => 'required|in:active,inactive,suspended',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
        ]);

        $branchId = session('branch_id') ?? $user->branch_id;
        if (!$branchId) {
            return back()->withInput()->withErrors(['error' => 'No active branch. Please select a branch first.']);
        }

        DB::beginTransaction();
        try {
            $donor = Customer::create([
                'customerNo' => '',
                'name' => $request->name,
                'description' => $request->description,
                'phone' => $request->phone,
                'email' => $request->email,
                'status' => $request->status,
                'company_name' => $request->company_name,
                'company_registration_number' => $request->company_registration_number,
                'tin_number' => $request->tin_number,
                'vat_number' => $request->vat_number,
                'branch_id' => $branchId,
                'company_id' => $companyId,
            ]);

            DB::commit();
            return redirect()->route('projects.donors.index')->with('success', 'Donor created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Failed to create donor: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }

        $donor = Customer::with(['branch'])->findOrFail($decodedId);

        $user = Auth::user();
        if ($donor->company_id !== $user->company_id) {
            abort(403, 'Unauthorized');
        }

        $projects = $donor->donorProjects()->get();

        return view('projects.donors.show', compact('donor', 'projects'));
    }

    public function edit($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }

        $donor = Customer::findOrFail($decodedId);

        if ($donor->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized');
        }

        return view('projects.donors.edit', compact('donor'));
    }

    public function update(Request $request, $id)
    {
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }

        $donor = Customer::findOrFail($decodedId);
        $user = Auth::user();

        if ($donor->company_id !== $user->company_id) {
            abort(403, 'Unauthorized');
        }

        if ($request->filled('phone') && function_exists('normalize_phone_number')) {
            $request->merge(['phone' => normalize_phone_number($request->input('phone'))]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('customers', 'email')
                    ->where(fn ($q) => $q->where('company_id', $user->company_id))
                    ->ignore($donor->id),
            ],
            'status' => 'required|in:active,inactive,suspended',
            'company_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:50',
            'vat_number' => 'nullable|string|max:50',
        ]);

        $donor->update($request->only([
            'name', 'description', 'phone', 'email', 'status',
            'company_name', 'company_registration_number', 'tin_number', 'vat_number',
        ]));

        return redirect()->route('projects.donors.show', $id)->with('success', 'Donor updated successfully.');
    }

    public function destroy($id)
    {
        $decodedId = Hashids::decode($id)[0] ?? null;
        if (!$decodedId) {
            abort(404);
        }

        $donor = Customer::findOrFail($decodedId);

        if ($donor->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized');
        }

        if ($donor->donorProjects()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete donor with assigned projects. Remove project assignments first.']);
        }

        $donor->delete();

        return redirect()->route('projects.donors.index')->with('success', 'Donor deleted successfully.');
    }
}
