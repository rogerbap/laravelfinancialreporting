@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">Categories</h1>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Color</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\Category::all() as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->code }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $category->color }}">
                                    {{ $category->color }}
                                </span>
                            </td>
                            <td>{{ $category->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection