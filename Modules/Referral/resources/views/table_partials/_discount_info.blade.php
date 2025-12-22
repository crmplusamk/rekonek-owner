<div class="text-center">
    @if($referral->discount_type === 'percentage')
        {{ $referral->discount_percentage }}%
        @if($referral->max_discount)
            <br><small class="text-muted">(Max: Rp {{ number_format($referral->max_discount, 0, ',', '.') }})</small>
        @endif
    @else
        Rp {{ number_format($referral->discount_amount, 0, ',', '.') }}
    @endif
</div>

