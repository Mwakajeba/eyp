@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Create Production Order</h1>
    <form action="{{ route('production.orders.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label>Customer Name</label>
            <input type="text" name="customer_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Product Type</label>
            <select name="product_type" class="form-control" required>
                <option value="sweater">Sweater</option>
                <option value="t-shirt">T-Shirt</option>
            </select>
        </div>
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantity" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Size</label>
            <input type="text" name="size" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Color</label>
            <input type="text" name="color" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Logo Design ID</label>
            <input type="number" name="logo_design_id" class="form-control">
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Create Order</button>
    </form>
</div>
@endsection
