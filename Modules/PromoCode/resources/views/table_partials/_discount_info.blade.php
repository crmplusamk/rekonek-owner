<div class="text-center">
    @if($promoCode->discount_type === 'percentage')
        {{ $promoCode->discount_percentage }}%
        @if($promoCode->max_discount)
            <br><small class="text-muted">(Max: Rp {{ number_format($promoCode->max_discount, 0, ',', '.') }})</small>
        @endif
    @else
        Rp {{ number_format($promoCode->discount_amount, 0, ',', '.') }}
    @endif
</div>
