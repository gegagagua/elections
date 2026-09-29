@extends('layouts.app')

@section('title', 'უბნები')

@section('content')
    <div class="page-head">
        <h1><i class="bi bi-geo-alt text-primary"></i> უბნები</h1>
        <a href="{{ route('districts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> ახალი უბანი
        </a>
    </div>

    @if ($districts->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <p class="mb-0">ჯერ არცერთი უბანი არ არის. დაამატე პირველი.</p>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach ($districts as $district)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title mb-1">
                                <i class="bi bi-geo-alt-fill text-primary"></i> {{ $district->name }}
                            </h5>
                            @if ($district->description)
                                <p class="text-muted small mb-3">{{ $district->description }}</p>
                            @else
                                <div class="mb-3"></div>
                            @endif
                            <div class="d-flex gap-3 mb-3">
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-person"></i> {{ $district->customers_count }} ქასთამერი
                                </span>
                                <span class="badge bg-light text-dark border">
                                    <i class="bi bi-person-badge"></i> {{ $district->managers_count }} მენეჯერი
                                </span>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="{{ route('districts.customers.index', $district) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-list-ul"></i> ქასთამერები
                                </a>
                                <a href="{{ route('districts.edit', $district) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('districts.destroy', $district) }}" onsubmit="return confirm('დარწმუნებული ხარ?')" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
