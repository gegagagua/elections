@extends('layouts.app')

@section('title', 'მენეჯერის რედაქტირება')

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('managers.index') }}">მენეჯერები</a></li>
            <li class="breadcrumb-item active">რედაქტ.</li>
        </ol>
    </nav>
    <div class="card" style="max-width: 720px;">
        <div class="card-header">მენეჯერის რედაქტირება</div>
        <div class="card-body">
            <form method="POST" action="{{ route('managers.update', $manager) }}">
                @csrf
                @method('PUT')
                @include('managers._form', ['manager' => $manager])
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> შენახვა</button>
                    <a href="{{ route('managers.index') }}" class="btn btn-outline-secondary">გაუქმება</a>
                </div>
            </form>
        </div>
    </div>
@endsection
