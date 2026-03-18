@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Production Order Details</h1>
    <div class="card">
        <div class="card-body">
            <p><strong>ID:</strong> {{ $order->id }}</p>
            <p><strong>Customer Name:</strong> {{ $order->customer_name }}</p>
            <p><strong>Product Type:</strong> {{ $order->product_type }}</p>
            <p><strong>Quantity:</strong> {{ $order->quantity }}</p>
            <p><strong>Size:</strong> {{ $order->size }}</p>
            <p><strong>Color:</strong> {{ $order->color }}</p>
            <p><strong>Logo Design ID:</strong> {{ $order->logo_design_id }}</p>
            <p><strong>Status:</strong> {{ $order->status }}</p>
            <p><strong>Due Date:</strong> {{ $order->due_date }}</p>
        </div>
    </div>
    <a href="{{ route('production.orders.edit', $order->id) }}" class="btn btn-warning mt-3">Edit</a>
    <a href="{{ route('production.orders.index') }}" class="btn btn-secondary mt-3">Back to Orders</a>
</div>
@endsection
