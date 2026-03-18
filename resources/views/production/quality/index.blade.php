@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Quality Checks</h1>
    <a href="{{ route('production.quality.create') }}" class="btn btn-primary mb-3">Add New Quality Check</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>Checked By</th>
                <th>Passed</th>
                <th>Remarks</th>
                <th>Checked At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($checks as $check)
            <tr>
                <td>{{ $check->id }}</td>
                <td>{{ $check->production_order_id }}</td>
                <td>{{ $check->checked_by }}</td>
                <td>{{ $check->passed ? 'Yes' : 'No' }}</td>
                <td>{{ $check->remarks }}</td>
                <td>{{ $check->checked_at }}</td>
                <td>
                    <a href="{{ route('production.quality.show', $check->id) }}" class="btn btn-info btn-sm">View</a>
                    <a href="{{ route('production.quality.edit', $check->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('production.quality.destroy', $check->id) }}" method="POST" style="display:inline-block;">
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
