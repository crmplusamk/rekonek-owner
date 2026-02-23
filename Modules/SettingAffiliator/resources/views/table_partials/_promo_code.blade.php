@php
    $promoCodes = \Modules\PromoCode\App\Models\PromoCode::where('affiliator_user_id', $user->id)->get();
    $count = $promoCodes->count();
@endphp
<div class="text-center">
    @if ($count === 0)
        <span class="text-muted">-</span>
    @elseif ($count === 1)
        <span class="font-weight-bold" style="letter-spacing: 0.5px;">{{ $promoCodes->first()->code }}</span>
    @else
        <span>{{ $count }} code</span>
        <a href="javascript:void(0)" class="btn-view-promo-codes ml-1 text-primary pointer" data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}">
            Lihat detail
        </a>
    @endif
</div>
