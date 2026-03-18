@extends('layouts.main')
@section('content')
<div class="container">
    <h1>Materials</h1>
    <a href="{{ route('production.materials.create') }}" class="btn btn-primary mb-3">Add New Material</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Quantity In Stock</th>
                <th>Unit</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materials as $material)
            <tr>
                <td>{{ $material->id }}</td>
                <td>{{ $material->name }}</td>
                <td>{{ $material->type }}</td>
                <td>{{ $material->quantity_in_stock }}</td>
                <td>{{ $material->unit }}</td>
                <td>
                    <a href="{{ route('production.materials.show', $material->id) }}" class="btn btn-info btn-sm">View</a>
                    <a href="{{ route('production.materials.edit', $material->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('production.materials.destroy', $material->id) }}" method="POST" style="display:inline-block;">
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
