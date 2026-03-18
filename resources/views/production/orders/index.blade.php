@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Production Orders</h1>
    <a href="{{ route('production.orders.create') }}" class="btn btn-primary mb-3">Create New Order</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Product Type</th>
                <th>Quantity</th>
                <th>Size</th>
                <th>Color</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>{{ $order->id }}</td>
                <td>{{ $order->customer_name }}</td>
                <td>{{ $order->product_type }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ $order->size }}</td>
                <td>{{ $order->color }}</td>
                <td>{{ $order->status }}</td>
                <td>{{ $order->due_date }}</td>
                <td>
                    <a href="{{ route('production.orders.show', $order->id) }}" class="btn btn-info btn-sm">View</a>
                    <a href="{{ route('production.orders.edit', $order->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('production.orders.destroy', $order->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
