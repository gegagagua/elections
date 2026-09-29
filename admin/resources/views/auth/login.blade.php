@extends('layouts.app')

@section('title', 'შესვლა')

@section('content')
    <div class="card shadow-sm" style="width: 100%; max-width: 400px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-4 text-center">
                <i class="bi bi-check2-square text-primary"></i> ადმინის შესვლა
            </h1>
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">ელფოსტა</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="form-control @error('email') is-invalid @enderror">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">პაროლი</label>
                    <input id="password" type="password" name="password" required class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right"></i> შესვლა
                </button>
            </form>
        </div>
    </div>
@endsection
