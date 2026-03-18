@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Edit Production Order</h1>
    <form action="{{ route('production.orders.update', $order->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label>Customer Name</label>
            <input type="text" name="customer_name" class="form-control" value="{{ $order->customer_name }}" required>
        </div>
        <div class="form-group">
            <label>Product Type</label>
            <select name="product_type" class="form-control" required>
                <option value="sweater" {{ $order->product_type == 'sweater' ? 'selected' : '' }}>Sweater</option>
                <option value="t-shirt" {{ $order->product_type == 't-shirt' ? 'selected' : '' }}>T-Shirt</option>
            </select>
        </div>
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantity" class="form-control" value="{{ $order->quantity }}" required>
        </div>
        <div class="form-group">
            <label>Size</label>
            <input type="text" name="size" class="form-control" value="{{ $order->size }}" required>
        </div>
        <div class="form-group">
            <label>Color</label>
            <input type="text" name="color" class="form-control" value="{{ $order->color }}" required>
        </div>
        <div class="form-group">
            <label>Logo Design ID</label>
            <input type="number" name="logo_design_id" class="form-control" value="{{ $order->logo_design_id }}">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="in_progress" {{ $order->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
        <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" value="{{ $order->due_date }}" required>
        </div>
        <button type="submit" class="btn btn-success">Update Order</button>
    </form>
</div>
@endsection
