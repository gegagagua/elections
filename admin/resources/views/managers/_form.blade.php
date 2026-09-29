@php $manager = $manager ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">სახელი</label>
        <input type="text" name="name" value="{{ old('name', $manager->name ?? '') }}" required
               class="form-control @error('name') is-invalid @enderror">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">ელფოსტა</label>
        <input type="email" name="email" value="{{ old('email', $manager->email ?? '') }}" required
               class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">
            პაროლი
            @isset($manager) <span class="text-muted small">(ცარიელი — არ იცვლება)</span> @endisset
        </label>
        <input type="password" name="password" @if (! isset($manager)) required @endif
               class="form-control @error('password') is-invalid @enderror">
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">უბანი</label>
        <select name="district_id" class="form-select @error('district_id') is-invalid @enderror">
            <option value="">— აირჩიე უბანი —</option>
            @foreach ($districts as $district)
                <option value="{{ $district->id }}" @selected(old('district_id', $manager->district_id ?? null) == $district->id)>{{ $district->name }}</option>
            @endforeach
        </select>
        @error('district_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
