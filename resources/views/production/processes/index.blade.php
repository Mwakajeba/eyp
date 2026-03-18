@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Production Processes</h1>
    <a href="{{ route('production.processes.create') }}" class="btn btn-primary mb-3">Add New Process</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Order ID</th>
                <th>Step</th>
                <th>Status</th>
                <th>Started At</th>
                <th>Finished At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($processes as $process)
            <tr>
                <td>{{ $process->id }}</td>
                <td>{{ $process->production_order_id }}</td>
                <td>{{ $process->step }}</td>
                <td>{{ $process->status }}</td>
                <td>{{ $process->started_at }}</td>
                <td>{{ $process->finished_at }}</td>
                <td>
                    <a href="{{ route('production.processes.show', $process->id) }}" class="btn btn-info btn-sm">View</a>
                    <a href="{{ route('production.processes.edit', $process->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('production.processes.destroy', $process->id) }}" method="POST" style="display:inline-block;">
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
