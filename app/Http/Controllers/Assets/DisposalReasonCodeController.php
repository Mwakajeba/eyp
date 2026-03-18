<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\DisposalReasonCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\Facades\DataTables;

class DisposalReasonCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    /**
     * Display a listing of disposal reason codes
     */
    public function index()
    {
        return view('assets.disposals.reason-codes.index');
    }

    /**
     * Get data for DataTables
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        
        $reasonCodes = DisposalReasonCode::where('company_id', $user->company_id)
            ->orderBy('code');

        return DataTables::of($reasonCodes)
            ->addColumn('disposal_type_display', function ($code) {
                $types = [
                    'sale' => 'Sale',
                    'scrap' => 'Scrap',
                    'write_off' => 'Write-off',
                    'donation' => 'Donation',
                    'loss' => 'Loss/Theft'
                ];
                return $types[$code->disposal_type] ?? 'Any';
            })
            ->addColumn('status_badge', function ($code) {
                return $code->is_active 
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('actions', function ($code) {
                $encodedId = Hashids::encode($code->id);
                $base = route('assets.disposals.reason-codes.index');
                return '
                    <div class="btn-group" role="group">
                        <a href="' . route('assets.disposals.reason-codes.edit', $encodedId) . '" class="btn btn-sm btn-outline-primary">
                            <i class="bx bx-edit"></i>
                        </a>
                        <form method="POST" action="' . route('assets.disposals.reason-codes.destroy', $encodedId) . '" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this reason code?\');">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bx bx-trash"></i>
                            </button>
                        </form>
                    </div>';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new reason code
     */
    public function create()
    {
        return view('assets.disposals.reason-codes.create');
    }

    /**
     * Store a newly created reason code
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:disposal_reason_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'disposal_type' => 'nullable|in:sale,scrap,write_off,donation,loss',
            'is_active' => 'boolean',
        ]);

        $user = Auth::user();

        DisposalReasonCode::create([
            'company_id' => $user->company_id,
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'disposal_type' => $validated['disposal_type'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'is_system' => false,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return redirect()->route('assets.disposals.reason-codes.index')
            ->with('success', 'Disposal reason code created successfully.');
    }

    /**
     * Show the form for editing the specified reason code
     */
    public function edit($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $reasonCode = DisposalReasonCode::findOrFail($id);

        // Check authorization
        if ($reasonCode->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        return view('assets.disposals.reason-codes.edit', compact('reasonCode', 'encodedId'));
    }

    /**
     * Update the specified reason code
     */
    public function update(Request $request, $encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $reasonCode = DisposalReasonCode::findOrFail($id);

        // Check authorization
        if ($reasonCode->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:disposal_reason_codes,code,' . $reasonCode->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'disposal_type' => 'nullable|in:sale,scrap,write_off,donation,loss',
            'is_active' => 'boolean',
        ]);

        $reasonCode->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'disposal_type' => $validated['disposal_type'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('assets.disposals.reason-codes.index')
            ->with('success', 'Disposal reason code updated successfully.');
    }

    /**
     * Remove the specified reason code
     */
    public function destroy($encodedId)
    {
        $id = Hashids::decode($encodedId)[0] ?? null;
        if (!$id) {
            abort(404);
        }

        $reasonCode = DisposalReasonCode::findOrFail($id);

        // Check authorization
        if ($reasonCode->company_id !== Auth::user()->company_id) {
            abort(403);
        }

        // Check if it's being used
        if ($reasonCode->disposals()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete reason code that is being used in disposals.');
        }

        $reasonCode->delete();

        return redirect()->route('assets.disposals.reason-codes.index')
            ->with('success', 'Disposal reason code deleted successfully.');
    }
}
