@php
    $typeLabels = [
        'affiliator' => 'Affiliator',
        'non_affiliator' => 'Non Affiliator',
    ];
    $typeLabel = $typeLabels[$promoCode->type] ?? '-';
@endphp
<div class="text-center">{{ $typeLabel }}</div>
