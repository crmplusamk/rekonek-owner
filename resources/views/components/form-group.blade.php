@props(['label' => null, 'name' => null, 'required' => false, 'hint' => null])

<div {{ $attributes->merge(['class' => 'form-group rk-form-group']) }}>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="rk-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <small class="form-text text-muted">{{ $hint }}</small>
    @endif

    @if ($name)
        @error($name)
            <small class="rk-field-error">{{ $message }}</small>
        @enderror
    @endif
</div>
