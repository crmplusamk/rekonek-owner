@php
    $class = match ($announcement->status) {
        'active' => 'success',
        'scheduled' => 'info',
        'draft' => 'secondary',
        'expired' => 'dark',
        default => 'warning',
    };
@endphp

<span class="badge badge-{{ $class }} text-uppercase">{{ $announcement->status }}</span>
