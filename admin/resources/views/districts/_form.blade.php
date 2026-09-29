@php $district = $district ?? null; @endphp
<div class="mb-3">
    <label class="form-label">დასახელება</label>
    <input type="text" name="name" value="{{ old('name', $district->name ?? '') }}" required
           class="form-control @error('name') is-invalid @enderror">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="mb-3">
    <label class="form-label">აღწერა <span class="text-muted small">(არასავალდებულო)</span></label>
    <input type="text" name="description" value="{{ old('description', $district->description ?? '') }}"
           class="form-control @error('description') is-invalid @enderror">
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
