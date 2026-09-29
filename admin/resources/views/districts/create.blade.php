@extends('layouts.app')

@section('title', 'ახალი უბანი')

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('districts.index') }}">უბნები</a></li>
            <li class="breadcrumb-item active">ახალი</li>
        </ol>
    </nav>
    <div class="card" style="max-width: 500px;">
        <div class="card-header">ახალი უბანი</div>
        <div class="card-body">
            <form method="POST" action="{{ route('districts.store') }}">
                @csrf
                @include('districts._form')
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> შენახვა</button>
                    <a href="{{ route('districts.index') }}" class="btn btn-outline-secondary">გაუქმება</a>
                </div>
            </form>
        </div>
    </div>
@endsection
