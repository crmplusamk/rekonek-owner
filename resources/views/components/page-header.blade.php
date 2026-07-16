@props(['title', 'subtitle' => null, 'back' => null])

<div class="rk-page-header">
    <div class="rk-page-header-main">
        @if ($back)
            <a href="{{ $back }}" class="rk-back" title="Kembali">
                <i class="mdi mdi-chevron-left"></i>
            </a>
        @endif
        <div>
            <h4 class="rk-page-title">{{ $title }}</h4>
            @if ($subtitle)
                <p class="rk-page-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="rk-page-actions">{{ $actions }}</div>
    @endisset
</div>
