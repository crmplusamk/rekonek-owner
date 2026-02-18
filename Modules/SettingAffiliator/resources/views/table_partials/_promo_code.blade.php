<div class="text-center">{{ $user->promoCodesAsAffiliator->pluck('code')->implode(', ') ?: '-' }}</div>
