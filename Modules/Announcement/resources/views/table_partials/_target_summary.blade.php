@php
    $companyTargets = $announcement->targets->where('target_type', 'company');
@endphp

@if ($companyTargets->isEmpty())
    <span class="badge badge-light border">Global</span>
@else
    <div>
        <span class="badge badge-primary">Company</span>
        <small class="d-block text-muted mt-1">{{ $companyTargets->count() }} target</small>
    </div>
@endif
