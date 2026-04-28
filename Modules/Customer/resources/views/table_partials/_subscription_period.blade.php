@if($subscription)
    <span class="small">
        {{ $subscription->started_at ? \Carbon\Carbon::parse($subscription->started_at)->format('d M Y') : '-' }}
        -
        {{ $subscription->expired_at ? \Carbon\Carbon::parse($subscription->expired_at)->format('d M Y') : '-' }}
    </span>
@else
    <span class="text-muted">-</span>
@endif
