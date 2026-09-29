@extends('layouts.app', ['autoRefresh' => 30])

@section('title', $district->name.' — ქასთამერები')

@php
    $labels = [
        'not_called' => 'არ დარეკილი',
        'called'     => 'დარეკილი',
        'came'       => 'მოვიდა',
    ];
    $badgeClass = [
        'not_called' => 'bg-danger-subtle text-danger-emphasis',
        'called'     => 'bg-warning-subtle text-warning-emphasis',
        'came'       => 'bg-success-subtle text-success-emphasis',
    ];
    $sortLink = function (string $column, string $label) use ($sort, $direction, $search, $district) {
        $nextDir = ($sort === $column && $direction === 'asc') ? 'desc' : 'asc';
        $arrow = $sort === $column ? ($direction === 'asc' ? ' <i class="bi bi-caret-up-fill"></i>' : ' <i class="bi bi-caret-down-fill"></i>') : '';
        $qs = http_build_query(array_filter([
            'search' => $search,
            'sort' => $column,
            'direction' => $nextDir,
        ]));
        $href = route('districts.customers.index', $district).'?'.$qs;
        return '<a href="'.$href.'" class="text-decoration-none text-reset">'.e($label).$arrow.'</a>';
    };

    $total = $customers->count();
    $came = $customers->where('status', 'came')->count();
    $called = $customers->where('status', 'called')->count();
    $notCame = $total - $came;
@endphp

@section('content')
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-0">
            <li class="breadcrumb-item"><a href="{{ route('districts.index') }}">უბნები</a></li>
            <li class="breadcrumb-item active">{{ $district->name }}</li>
        </ol>
    </nav>

    <div class="page-head">
        <h1><i class="bi bi-people text-primary"></i> {{ $district->name }}</h1>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('districts.customers.export', $district) }}" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Excel-ში გადმოწერა
            </a>
            <a href="{{ route('districts.customers.create', $district) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> ახალი ქასთამერი
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body">
                    <div class="text-muted small">სულ</div>
                    <div class="fs-3 fw-bold">{{ $total }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small">მოვიდა</div>
                    <div class="fs-3 fw-bold text-success">{{ $came }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-muted small">დარეკილი</div>
                    <div class="fs-3 fw-bold text-warning">{{ $called }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card border-start border-danger border-4">
                <div class="card-body">
                    <div class="text-muted small">არ მოსული</div>
                    <div class="fs-3 fw-bold text-danger">{{ $notCame }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('districts.customers.index', $district) }}" class="d-flex gap-2 flex-wrap">
                <div class="input-group flex-grow-1" style="min-width: 240px;">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" value="{{ $search }}" class="form-control"
                           placeholder="ძებნა (სახელი, გვარი, პირადობა, ტელეფონი...)">
                </div>
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <button type="submit" class="btn btn-primary">ძებნა</button>
                @if ($search)
                    <a href="{{ route('districts.customers.index', $district) }}" class="btn btn-outline-secondary">გასუფთავება</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="POST" action="{{ route('districts.customers.import', $district) }}" enctype="multipart/form-data"
                  class="d-flex gap-2 flex-wrap align-items-center">
                @csrf
                <strong class="me-2"><i class="bi bi-file-earmark-arrow-up"></i> Excel იმპორტი:</strong>
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="form-control" style="max-width: 300px;">
                <button type="submit" class="btn btn-success"><i class="bi bi-upload"></i> ატვირთვა</button>
                <span class="text-muted small">სვეტები: სახელი, გვარი, პირადი ნომერი, ტელეფონი, მისამართი (ან: first_name, last_name, personal_id, phone, address)</span>
            </form>
            @error('file') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:56px;">სურათი</th>
                        <th>{!! $sortLink('first_name', 'სახელი') !!}</th>
                        <th>{!! $sortLink('last_name', 'გვარი') !!}</th>
                        <th>პირადობა</th>
                        <th>მისამართი</th>
                        <th>ტელეფონი</th>
                        <th>{!! $sortLink('status', 'სტატუსი') !!}</th>
                        <th class="text-end">მოქმედება</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr class="{{ $customer->status === 'came' ? 'row-came' : '' }}">
                            <td>
                                @if ($customer->image_url)
                                    <a href="{{ $customer->image_url }}" target="_blank">
                                        <img src="{{ $customer->image_url }}" alt=""
                                             style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                                    </a>
                                @else
                                    <span class="text-muted"><i class="bi bi-person-circle fs-4"></i></span>
                                @endif
                            </td>
                            <td>{{ $customer->first_name }}</td>
                            <td class="fw-semibold">{{ $customer->last_name }}</td>
                            <td class="font-monospace small">{{ $customer->personal_id }}</td>
                            <td class="text-muted small">{{ $customer->address }}</td>
                            <td><a href="tel:{{ $customer->phone }}" class="text-decoration-none">{{ $customer->phone }}</a></td>
                            <td><span class="badge {{ $badgeClass[$customer->status] }}">{{ $labels[$customer->status] }}</span></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    @if ($customer->status !== 'came')
                                        <form method="POST" action="{{ route('districts.customers.status', [$district, $customer]) }}" class="m-0">
                                            @csrf
                                            <input type="hidden" name="status" value="came">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="bi bi-check-lg"></i> მოვიდა
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('districts.customers.status', [$district, $customer]) }}" class="m-0">
                                            @csrf
                                            <input type="hidden" name="status" value="not_called">
                                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('districts.customers.edit', [$district, $customer]) }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('districts.customers.destroy', [$district, $customer]) }}" onsubmit="return confirm('წავშალო?')" class="m-0">
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
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                ჩანაწერები არ მოიძებნა
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
