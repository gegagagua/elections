@php $customer = $customer ?? null; @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">სახელი</label>
        <input type="text" name="first_name" value="{{ old('first_name', $customer->first_name ?? '') }}" required
               class="form-control @error('first_name') is-invalid @enderror">
        @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">გვარი</label>
        <input type="text" name="last_name" value="{{ old('last_name', $customer->last_name ?? '') }}" required
               class="form-control @error('last_name') is-invalid @enderror">
        @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">პირადობა</label>
        <input type="text" name="personal_id" value="{{ old('personal_id', $customer->personal_id ?? '') }}" required
               class="form-control @error('personal_id') is-invalid @enderror">
        @error('personal_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">ტელეფონი</label>
        <input type="text" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" required
               class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">მისამართი</label>
        <input type="text" name="address" value="{{ old('address', $customer->address ?? '') }}"
               class="form-control @error('address') is-invalid @enderror">
        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">სურათი</label>
        <input type="file" name="image" accept="image/*"
               class="form-control @error('image') is-invalid @enderror">
        @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @isset($customer)
            @if ($customer->image_url)
                <div class="mt-2 d-flex align-items-center gap-3">
                    <img src="{{ $customer->image_url }}" alt="სურათი"
                         style="width:96px;height:96px;object-fit:cover;border-radius:8px;">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                        <label class="form-check-label" for="remove_image">სურათის წაშლა</label>
                    </div>
                </div>
            @endif
        @endisset
    </div>

    @isset($customer)
        <div class="col-md-6">
            <label class="form-label">სტატუსი</label>
            <select name="status" class="form-select">
                @foreach (['not_called' => 'არ დარეკილი', 'called' => 'დარეკილი', 'came' => 'მოვიდა'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('status', $customer->status) === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endisset
</div>
