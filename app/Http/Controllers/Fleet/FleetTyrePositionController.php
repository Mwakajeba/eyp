<?php

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FleetTyrePosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FleetTyrePositionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        $query = FleetTyrePosition::where('company_id', $companyId)
            ->orderBy('sort_order')
            ->orderBy('position_name');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }
        $positions = $query->paginate(20)->withQueryString();
        return view('fleet.tyre-positions.index', compact('positions'));
    }

    public function create()
    {
        return view('fleet.tyre-positions.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'position_code' => 'nullable|string|max:50',
            'position_name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['company_id'] = $user->company_id;
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);

        $position = FleetTyrePosition::create($validated);
        return redirect()->route('fleet.tyre-positions.show', $position)->with('success', 'Tyre position created.');
    }

    public function show(FleetTyrePosition $position)
    {
        $this->authorizeCompany($position);
        return view('fleet.tyre-positions.show', compact('position'));
    }

    public function edit(FleetTyrePosition $position)
    {
        $this->authorizeCompany($position);
        return view('fleet.tyre-positions.edit', compact('position'));
    }

    public function update(Request $request, FleetTyrePosition $position)
    {
        $this->authorizeCompany($position);
        $validated = $request->validate([
            'position_code' => 'nullable|string|max:50',
            'position_name' => 'required|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = $request->boolean('is_active', true);
        $position->update($validated);
        return redirect()->route('fleet.tyre-positions.show', $position)->with('success', 'Tyre position updated.');
    }

    public function destroy(FleetTyrePosition $position)
    {
        $this->authorizeCompany($position);
        if ($position->installations()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete: position has installation records.');
        }
        $position->delete();
        return redirect()->route('fleet.tyre-positions.index')->with('success', 'Tyre position deleted.');
    }

    private function authorizeCompany(FleetTyrePosition $position): void
    {
        if ($position->company_id !== Auth::user()->company_id) {
            abort(403);
        }
    }
}
