<div class="text-center">
    @if($referral->usage_limit)
        {{ $referral->used_count }} / {{ $referral->usage_limit }}
    @else
        {{ $referral->used_count }} / <span class="text-muted">Unlimited</span>
    @endif
</div>

