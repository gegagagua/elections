@extends('layouts.app')

@section('title', 'ახალი მენეჯერი')

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('managers.index') }}">მენეჯერები</a></li>
            <li class="breadcrumb-item active">ახალი</li>
        </ol>
    </nav>
    <div class="card" style="max-width: 720px;">
        <div class="card-header">ახალი მენეჯერი</div>
        <div class="card-body">
            <form method="POST" action="{{ route('managers.store') }}">
                @csrf
                @include('managers._form')
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> შენახვა</button>
                    <a href="{{ route('managers.index') }}" class="btn btn-outline-secondary">გაუქმება</a>
                </div>
            </form>
        </div>
    </div>
@endsection
