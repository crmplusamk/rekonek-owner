@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('subscription.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail Subscription: {{ $data->code }}
        </h4>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Customer</div>
                <div class="h5 mb-0 font-weight-bold">{{ $data->customer->name ?? '-' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Package</div>
                <div class="h5 mb-0 font-weight-bold">{{ $data->package->name ?? '-' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Status</div>
                <div class="h5 mb-0 font-weight-bold">
                    <span class="badge badge-{{ $data->is_active ? 'success' : 'secondary' }}">{{ $data->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Tipe</div>
                <div class="h5 mb-0 font-weight-bold">{{ $data->is_trial === 'trial' ? 'Trial' : 'Berbayar' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Mulai</div>
                <div class="font-weight-bold">{{ $data->started_at ? \Carbon\Carbon::parse($data->started_at)->format('d M Y') : '-' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Berakhir</div>
                <div class="font-weight-bold">{{ $data->expired_at ? \Carbon\Carbon::parse($data->expired_at)->format('d M Y') : '-' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Grace</div>
                <div class="font-weight-bold">{{ $data->is_grace ?? '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-body">
        <h6 class="mb-3 text-muted font-weight-bold">Snapshot Aturan Fitur <small class="text-muted">(dibekukan saat langganan dibuat — dapat di-override manual)</small></h6>
        @if ($rules->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Belum ada snapshot aturan fitur untuk subscription ini.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Fitur</th>
                            <th class="text-center">Included</th>
                            <th>Limit</th>
                            <th>Sumber</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rules as $rule)
                            <tr>
                                <td>{{ $rule->name }} <span class="text-muted">({{ $rule->key }})</span></td>
                                <td class="text-center">
                                    @if ($rule->included)
                                        <i class="mdi mdi-check-circle text-success"></i>
                                    @else
                                        <i class="mdi mdi-close-circle text-danger"></i>
                                    @endif
                                </td>
                                <td>
                                    @if ($rule->limit === null)
                                        <span class="text-muted">—</span>
                                    @elseif ((string) $rule->limit === '-1')
                                        Unlimited
                                    @else
                                        {{ $rule->limit }}
                                        <span class="text-muted">
                                            @switch($rule->limit_type)
                                                @case('max') maks @break
                                                @case('day') /hari @break
                                                @case('month') /bulan @break
                                                @case('time') kali @break
                                            @endswitch
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $rule->source === 'manual' ? 'warning' : ($rule->source === 'admin_push' ? 'info' : 'light') }}">{{ $rule->source }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="dropdown">
                                        <a href="#" data-toggle="dropdown" class="text-muted"><i class="mdi mdi-dots-horizontal mdi-24px"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item pointer" data-toggle="modal" data-target="#editRule-{{ $rule->feature_id }}">
                                                <i class="mdi mdi-pencil"></i> Edit (manual)
                                            </a>
                                            @if ($rule->source !== 'package')
                                                <form action="{{ route('subscription.rules.reset', [$data->id, $rule->feature_id]) }}" method="post">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="mdi mdi-backup-restore"></i> Reset ke paket</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @foreach ($rules as $rule)
                @php $mode = is_null($rule->limit) ? 'none' : ((string) $rule->limit === '-1' ? 'unlimited' : 'limited'); @endphp
                <div class="modal fade" id="editRule-{{ $rule->feature_id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Override Manual: {{ $rule->name }}</h5>
                                <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></a>
                            </div>
                            <form action="{{ route('subscription.rules.update', [$data->id, $rule->feature_id]) }}" method="post">
                                @csrf
                                @method('put')
                                <div class="modal-body">
                                    <input type="hidden" name="visiblity" value="{{ $rule->visiblity ? 'on' : 'off' }}">
                                    <div class="custom-control custom-switch mb-3">
                                        <input type="checkbox" class="custom-control-input" id="inc-{{ $rule->feature_id }}" name="included" {{ $rule->included ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="inc-{{ $rule->feature_id }}">Included (fitur aktif)</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Limit</label>
                                        <select name="limit_mode" class="form-control rule-mode" data-target="{{ $rule->feature_id }}">
                                            <option value="none" {{ $mode === 'none' ? 'selected' : '' }}>Tidak diatur</option>
                                            <option value="unlimited" {{ $mode === 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                                            <option value="limited" {{ $mode === 'limited' ? 'selected' : '' }}>Limit</option>
                                        </select>
                                    </div>
                                    <div class="rule-limit-{{ $rule->feature_id }} {{ $mode === 'limited' ? '' : 'd-none' }}">
                                        <div class="form-group">
                                            <label>Nilai Limit</label>
                                            <input type="number" name="limit" min="1" class="form-control" value="{{ $mode === 'limited' ? $rule->limit : '' }}">
                                        </div>
                                        <div class="form-group">
                                            <label>Tipe Limit</label>
                                            <select name="limit_type" class="form-control">
                                                <option value="max" {{ $rule->limit_type === 'max' ? 'selected' : '' }}>Maksimal</option>
                                                <option value="day" {{ $rule->limit_type === 'day' ? 'selected' : '' }}>Perhari</option>
                                                <option value="month" {{ $rule->limit_type === 'month' ? 'selected' : '' }}>Perbulan</option>
                                                <option value="time" {{ $rule->limit_type === 'time' ? 'selected' : '' }}>Kali</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">Simpan sebagai manual</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
@endsection

@push('script')
<script>
    $('body').on('change', '.rule-mode', function () {
        let target = $(this).data('target');
        $('.rule-limit-' + target).toggleClass('d-none', $(this).val() !== 'limited');
    });
</script>
@endpush
