@props(['icon' => 'mdi-inbox-outline', 'title' => 'Belum ada data', 'message' => null])

<div class="rk-empty-state">
    <i class="mdi {{ $icon }}"></i>
    <p class="rk-empty-title">{{ $title }}</p>
    @if ($message)
        <p class="rk-empty-message">{{ $message }}</p>
    @endif
    @isset($slot)
        <div class="rk-empty-actions">{{ $slot }}</div>
    @endisset
</div>
