<?php
namespace App\Http\Controllers\Production;

use App\Models\Production\ProductionOrder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductionOrderController extends Controller
{
    public function index()
    {
        $orders = ProductionOrder::all();
        return view('production.orders.index', compact('orders'));
    }

    public function create()
    {
        return view('production.orders.create');
    }

    public function store(Request $request)
    {
        $order = ProductionOrder::create($request->all());
        return redirect()->route('production.orders.index');
    }

    public function show($id)
    {
        $order = ProductionOrder::findOrFail($id);
        return view('production.orders.show', compact('order'));
    }

    public function edit($id)
    {
        $order = ProductionOrder::findOrFail($id);
        return view('production.orders.edit', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $order = ProductionOrder::findOrFail($id);
        $order->update($request->all());
        return redirect()->route('production.orders.index');
    }

    public function destroy($id)
    {
        $order = ProductionOrder::findOrFail($id);
        $order->delete();
        return redirect()->route('production.orders.index');
    }
}
