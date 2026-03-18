<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hr\TradeUnion;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class TradeUnionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('hr-payroll.trade-unions.index');
    }

    /**
     * Get data for DataTables
     */
    public function data(Request $request)
    {
        $user = auth()->user();
        $query = TradeUnion::where('company_id', $user->company_id);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', function ($tradeUnion) {
                $badgeClass = $tradeUnion->is_active ? 'success' : 'secondary';
                $statusText = $tradeUnion->is_active ? 'Active' : 'Inactive';
                return '<span class="badge bg-' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->addColumn('description_short', function ($tradeUnion) {
                return $tradeUnion->description ? \Str::limit($tradeUnion->description, 50) : '-';
            })
            ->addColumn('actions', function ($tradeUnion) {
                $editUrl = route('hr.trade-unions.edit', $tradeUnion);
                $deleteUrl = route('hr.trade-unions.destroy', $tradeUnion);
                
                return '
                    <div class="d-flex gap-1 justify-content-end">
                        <a href="' . $editUrl . '" class="btn btn-sm btn-secondary" title="Edit">
                            <i class="bx bx-edit"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="deleteTradeUnion(' . $tradeUnion->id . ', \'' . addslashes($tradeUnion->name) . '\')" title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('hr-payroll.trade-unions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hr_trade_unions')->where(fn($q) => $q->where('company_id', $user->company_id))],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $tradeUnion = TradeUnion::create(array_merge($validated, [
            'company_id' => $user->company_id,
        ]));

        return redirect()->route('hr.trade-unions.index')->with('success', 'Trade Union created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = auth()->user();
        $tradeUnion = TradeUnion::where('company_id', $user->company_id)->findOrFail($id);
        return view('hr-payroll.trade-unions.edit', compact('tradeUnion'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $tradeUnion = TradeUnion::where('company_id', $user->company_id)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('hr_trade_unions')->ignore($tradeUnion->id)->where(fn($q) => $q->where('company_id', $user->company_id))],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $tradeUnion->update($validated);

        return redirect()->route('hr.trade-unions.index')->with('success', 'Trade Union updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = auth()->user();
        $tradeUnion = TradeUnion::where('company_id', $user->company_id)->findOrFail($id);

        // Check if trade union has employees
        if ($tradeUnion->employees()->count() > 0) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete trade union "' . $tradeUnion->name . '" because it has associated employees. Please reassign employees first.'
                ], 422);
            }
            return redirect()->route('hr.trade-unions.index')
                ->with('error', 'Cannot delete trade union "' . $tradeUnion->name . '" because it has associated employees. Please reassign employees first.');
        }

        $tradeUnion->delete();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Trade Union deleted successfully.'
            ]);
        }
        
        return redirect()->route('hr.trade-unions.index')->with('success', 'Trade Union deleted successfully.');
    }

    /**
     * Get active trade unions for AJAX requests
     */
    public function getActiveTradeUnions()
    {
        $user = auth()->user();
        $tradeUnions = TradeUnion::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($tradeUnions);
    }
}
