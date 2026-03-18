@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Designs</h1>
    <a href="{{ route('production.designs.create') }}" class="btn btn-primary mb-3">Add New Design</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Image</th>
                <th>Approved</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($designs as $design)
            <tr>
                <td>{{ $design->id }}</td>
                <td>{{ $design->name }}</td>
                <td><img src="{{ asset($design->image_path) }}" alt="Logo" width="50"></td>
                <td>{{ $design->approved ? 'Yes' : 'No' }}</td>
                <td>{{ $design->notes }}</td>
                <td>
                    <a href="{{ route('production.designs.show', $design->id) }}" class="btn btn-info btn-sm">View</a>
                    <a href="{{ route('production.designs.edit', $design->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('production.designs.destroy', $design->id) }}" method="POST" style="display:inline-block;">
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
