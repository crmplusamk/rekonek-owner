@php
    $class = match ($announcement->type) {
        'warning' => 'warning',
        'success' => 'success',
        'danger' => 'danger',
        default => 'info',
    };
@endphp

<span class="badge badge-{{ $class }} text-uppercase">{{ $announcement->type }}</span>
