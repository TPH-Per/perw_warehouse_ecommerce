@extends('layouts.admin')

@section('content')
<div class="container">
    <h1>Test Pagination</h1>

    <!-- Create some dummy data for pagination -->
    @php
        $items = collect(range(1, 50));
        $paginatedItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage(request('page', 1), 10),
            $items->count(),
            10,
            request('page', 1),
            ['path' => request()->url(), 'pageName' => 'page']
        );
    @endphp

    <div class="card">
        <div class="card-body">
            <ul>
                @foreach($paginatedItems as $item)
                    <li>Item {{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- Pagination links -->
    <div class="mt-4">
        {{ $paginatedItems->links() }}
    </div>
</div>
@endsection
