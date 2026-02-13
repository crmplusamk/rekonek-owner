<div class="text-center">
    @if($promoCode->usage_limit)
        {{ $promoCode->used_count }} / {{ $promoCode->usage_limit }}
    @else
        {{ $promoCode->used_count }} / <span class="text-muted">Unlimited</span>
    @endif
</div>
