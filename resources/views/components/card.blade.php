@props(['title' => null, 'subtitle' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'rk-card']) }}>
    @if ($title || isset($header))
        <div class="rk-card-header">
            <div>
                @if ($title)
                    <h6 class="rk-card-title">{{ $title }}</h6>
                @endif
                @if ($subtitle)
                    <span class="rk-card-subtitle">{{ $subtitle }}</span>
                @endif
            </div>
            @isset($header)
                <div class="rk-card-header-actions">{{ $header }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding ? 'rk-card-body' : '' }}">
        {{ $slot }}
    </div>
</div>
