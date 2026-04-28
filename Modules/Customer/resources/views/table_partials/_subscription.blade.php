@if($subscription && $package)
    @if($subscription->is_trial === 'trial')
        <span class="badge badge-pill badge-warning">Trial</span>
    @else
        <span class="badge badge-pill badge-info">{{ $package->name ?? '-' }}</span>
    @endif
@else
    <span class="badge badge-pill badge-secondary">-</span>
@endif
