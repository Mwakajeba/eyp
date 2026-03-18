@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Dispatches</h1>
    <a href="{{ route('production.dispatch.create') }}" class="btn btn-primary mb-3">Add New Dispatch</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>Dispatched At</th>
                <th>Dispatched By</th>
                <th>Destination</th>
                <th>Tracking Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dispatches as $dispatch)
            <tr>
                <td>{{ $dispatch->id }}</td>
                <td>{{ $dispatch->production_order_id }}</td>
                <td>{{ $dispatch->dispatched_at }}</td>
                <td>{{ $dispatch->dispatched_by }}</td>
                <td>{{ $dispatch->destination }}</td>
                <td>{{ $dispatch->tracking_number }}</td>
                <td>
                    <a href="{{ route('production.dispatch.show', $dispatch->id) }}" class="btn btn-info btn-sm">View</a>
                    <a href="{{ route('production.dispatch.edit', $dispatch->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('production.dispatch.destroy', $dispatch->id) }}" method="POST" style="display:inline-block;">
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
