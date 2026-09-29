@extends('layouts.app')

@section('title', 'ქასთამერის რედაქტირება')

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('districts.index') }}">უბნები</a></li>
            <li class="breadcrumb-item"><a href="{{ route('districts.customers.index', $district) }}">{{ $district->name }}</a></li>
            <li class="breadcrumb-item active">რედაქტ.</li>
        </ol>
    </nav>
    <div class="card" style="max-width: 720px;">
        <div class="card-header">ქასთამერის რედაქტირება</div>
        <div class="card-body">
            <form method="POST" action="{{ route('districts.customers.update', [$district, $customer]) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('customers._form', ['customer' => $customer])
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> შენახვა</button>
                    <a href="{{ route('districts.customers.index', $district) }}" class="btn btn-outline-secondary">გაუქმება</a>
                </div>
            </form>
        </div>
    </div>
@endsection
