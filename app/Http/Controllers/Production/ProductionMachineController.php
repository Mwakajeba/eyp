<?php
namespace App\Http\Controllers\Production;

use App\Models\Production\ProductionMachine;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductionMachineController extends Controller
{
    public function index()
    {
        $machines = ProductionMachine::all();
        return view('production.machines.index', compact('machines'));
    }

    public function create()
    {
        return view('production.machines.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'machine_name' => 'required|string|max:255',
            'purchased_date' => 'required|date',
            'status' => 'required|in:new,used',
            'location' => 'required|string|max:255',
            'production_stage' => 'nullable|in:KNITTING,CUTTING,JOINING,EMBROIDERY,IRONING_FINISHING,PACKAGING',
            'gauge' => 'nullable|string|max:50',
        ]);
        
        $machine = ProductionMachine::create($validated);
        
        return redirect()->route('production.machines.index')
            ->with('success', 'Production machine created successfully.');
    }

    public function show($hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.machines.index')->withErrors(['Machine not found.']);
        }
        $machine = ProductionMachine::findOrFail($decoded[0]);
        return view('production.machines.show', compact('machine'));
    }

    public function edit($hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.machines.index')->withErrors(['Machine not found.']);
        }
        $machine = ProductionMachine::findOrFail($decoded[0]);
        return view('production.machines.edit', compact('machine'));
    }

    public function update(Request $request, $hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.machines.index')->withErrors(['Machine not found.']);
        }
        $machine = ProductionMachine::findOrFail($decoded[0]);
        $validated = $request->validate([
            'machine_name' => 'required|string|max:255',
            'purchased_date' => 'required|date',
            'status' => 'required|in:new,used',
            'location' => 'required|string|max:255',
            'production_stage' => 'nullable|in:KNITTING,CUTTING,JOINING,EMBROIDERY,IRONING_FINISHING,PACKAGING',
            'gauge' => 'nullable|string|max:50',
        ]);
        
        $machine->update($validated);
        
        return redirect()->route('production.machines.index')
            ->with('success', 'Production machine updated successfully.');
    }

    public function destroy($hashid)
    {
        $decoded = Hashids::decode($hashid);
        if (empty($decoded)) {
            return redirect()->route('production.machines.index')->withErrors(['Machine not found.']);
        }
        $machine = ProductionMachine::findOrFail($decoded[0]);
        $machine->delete();
        
        return redirect()->route('production.machines.index')
            ->with('success', 'Production machine deleted successfully.');
    }
}
