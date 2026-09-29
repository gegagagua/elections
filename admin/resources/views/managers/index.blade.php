@extends('layouts.app')

@section('title', 'მენეჯერები')

@section('content')
    <div class="page-head">
        <h1><i class="bi bi-people text-primary"></i> მენეჯერები</h1>
        <a href="{{ route('managers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> ახალი მენეჯერი
        </a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>სახელი</th>
                        <th>ელფოსტა</th>
                        <th>უბანი</th>
                        <th class="text-end">მოქმედება</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($managers as $manager)
                        <tr>
                            <td class="fw-semibold">
                                <i class="bi bi-person-circle text-muted"></i> {{ $manager->name }}
                            </td>
                            <td class="text-muted">{{ $manager->email }}</td>
                            <td>
                                @if ($manager->district)
                                    <span class="badge bg-primary-subtle text-primary-emphasis">
                                        <i class="bi bi-geo-alt"></i> {{ $manager->district->name }}
                                    </span>
                                @else
                                    <span class="text-muted small">— არ არის მიბმული —</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('managers.edit', $manager) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('managers.destroy', $manager) }}" onsubmit="return confirm('წავშალო?')" class="m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-person-slash fs-3 d-block mb-2"></i>
                                ჯერ არცერთი მენეჯერი არ გყავს
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
