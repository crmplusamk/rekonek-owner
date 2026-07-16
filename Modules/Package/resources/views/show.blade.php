@extends('template.admin.main')
@section('content')

@php $package = $data['package']; @endphp

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('package.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail Package: {{ $package->name }}
        </h4>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Harga</div>
                <div class="h4 mb-0 font-weight-bold">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Durasi</div>
                <div class="h4 mb-0 font-weight-bold">{{ $package->duration }} {{ $package->duration_type == 'month' ? 'Bulan' : 'Hari' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Status</div>
                <div class="h4 mb-0 font-weight-bold">
                    <span class="badge badge-{{ $package->is_active ? 'success' : 'secondary' }}">{{ $package->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Publish</div>
                <div class="h4 mb-0 font-weight-bold">
                    <span class="badge badge-{{ $package->is_publish ? 'success' : 'secondary' }}">{{ $package->is_publish ? 'Ya' : 'Tidak' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($package->description)
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="text-xs font-weight-bold text-muted text-uppercase mb-2">Deskripsi</div>
            <div>{{ $package->description }}</div>
        </div>
    </div>
@endif

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <h6 class="mb-3 text-muted font-weight-bold">Aturan Fitur</h6>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60%">Fitur</th>
                        <th class="text-center">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['features'] as $feature)
                        <tr class="bg-light">
                            <th colspan="2" class="font-weight-bold">{{ $feature->name }}</th>
                        </tr>
                        @foreach ($feature->childs as $sub)
                            @php $rule = $data['rules'][$sub->id]['pivot'] ?? null; @endphp
                            <tr>
                                <td class="font-weight-normal">{{ $sub->name }}</td>
                                <td class="text-center">
                                    @if ($rule && $rule['limit'])
                                        <span class="font-weight-bold">{{ $rule['limit'] == -1 ? 'Unlimited' : $rule['limit'] }}</span>
                                        <span class="text-muted">
                                            @switch($rule['limit_type'])
                                                @case('max') maks data @break
                                                @case('day') /hari @break
                                                @case('month') /bulan @break
                                                @case('time') kali @break
                                            @endswitch
                                        </span>
                                    @elseif ($rule && $rule['included'])
                                        <i class="mdi mdi-check-circle text-success" style="font-size:1.2rem"></i>
                                    @else
                                        <i class="mdi mdi-close-circle text-danger" style="font-size:1.2rem"></i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mb-5 text-right">
    <a href="{{ route('package.edit', $package->id) }}" class="btn btn-primary">
        <i class="mdi mdi-pencil"></i> Edit
    </a>
</div>
@endsection
