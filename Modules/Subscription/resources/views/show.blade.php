@extends('template.admin.main')
@section('content')

@php $inGrace = ($data->is_grace ?? 'active') !== 'active'; @endphp

<div class="row">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
        <h4 class="pt-2 mb-0">
            <a href="{{ route('subscription.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail Subscription: {{ $data->code }}
        </h4>
        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#changePackageModal">
            <i class="mdi mdi-swap-horizontal"></i> Ubah Paket
        </button>
    </div>
</div>

@if ($inGrace)
    <div class="alert alert-warning border-0 shadow-sm mt-3 mb-0" role="alert">
        <i class="mdi mdi-alert-outline"></i>
        <strong>Langganan dalam masa grace ({{ $data->is_grace }}).</strong>
        Mengubah paket akan <strong>mereset status grace ke aktif</strong> — data grace period di-clear dan langganan diperbarui.
    </div>
@endif

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

<div class="card shadow-sm mb-4">
    <div class="card-body">
        @php $ownedAddonIds = $addons->pluck('addon_id')->all(); @endphp
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0 text-muted font-weight-bold">Addon Aktif Company <small class="text-muted">(entitlement per-company, bukan terikat cycle langganan ini)</small></h6>
            <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#addAddonModal">
                <i class="mdi mdi-plus"></i> Tambah Addon
            </button>
        </div>
        @if ($addons->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Tidak ada addon aktif untuk company ini.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Addon</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Total</th>
                            <th class="text-center">Mulai</th>
                            <th class="text-center">Berakhir</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($addons as $addon)
                            @php
                                $block = (int) ($addon->block_size ?: 1);
                                $units = (int) $addon->units;
                                $qty = $block > 0 ? intdiv($units, $block) : $units;
                                $total = $block > 0 ? ($units / $block) * (float) $addon->price : 0;
                            @endphp
                            <tr>
                                <td>{{ $addon->addon_name }} <span class="text-muted">({{ $addon->feature_key ?? '-' }})</span></td>
                                <td class="text-center">{{ number_format($units, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    Rp {{ number_format($addon->price, 0, ',', '.') }}
                                    <span class="text-muted">/ {{ $block > 1 ? number_format($block, 0, ',', '.') . ' unit' : 'unit' }}</span>
                                </td>
                                <td class="text-right font-weight-bold">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                <td class="text-center">{{ $addon->started_at ? \Carbon\Carbon::parse($addon->started_at)->format('d M Y') : '-' }}</td>
                                <td class="text-center">{{ $addon->expired_at ? \Carbon\Carbon::parse($addon->expired_at)->format('d M Y') : '-' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $addon->is_active ? 'success' : 'secondary' }}">{{ $addon->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="text-right">
                                    <div class="dropdown">
                                        <a href="#" data-toggle="dropdown" class="text-muted"><i class="mdi mdi-dots-horizontal mdi-24px"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item pointer" data-toggle="modal" data-target="#editAddon-{{ $addon->id }}">
                                                <i class="mdi mdi-pencil"></i> Edit
                                            </a>
                                            <a class="dropdown-item text-danger pointer" data-toggle="modal" data-target="#deleteAddon-{{ $addon->id }}">
                                                <i class="mdi mdi-delete"></i> Hapus
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
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

{{-- ============ Modal: Ubah Paket (upgrade/downgrade) ============ --}}
<div class="modal fade" id="changePackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Paket Langganan</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a>
            </div>
            <form action="{{ route('subscription.package.update', $data->id) }}" method="post">
                @csrf
                @method('put')
                <div class="modal-body">
                    @if ($inGrace)
                        <div class="alert alert-warning py-2 small">
                            <i class="mdi mdi-alert-outline"></i> Langganan sedang <strong>{{ $data->is_grace }}</strong>. Menyimpan akan <strong>mereset status grace ke aktif</strong> & memperbarui langganan (data grace di-clear).
                        </div>
                    @endif
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="mdi mdi-information-outline"></i> Mengubah paket akan <strong>menyusun ulang snapshot aturan fitur</strong> sesuai paket baru — override manual pada langganan ini akan ter-reset ke default paket.
                    </div>
                    <div class="form-group">
                        <label>Paket</label>
                        <select name="package_id" class="form-control" required>
                            @foreach ($packages as $pkg)
                                <option value="{{ $pkg->id }}" {{ $data->package_id === $pkg->id ? 'selected' : '' }}>
                                    {{ $pkg->name }}@if ($pkg->price) — Rp {{ number_format($pkg->price, 0, ',', '.') }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="started_at" class="form-control" value="{{ \Carbon\Carbon::parse($data->started_at ?? now())->format('Y-m-d') }}" required>
                        </div>
                        <div class="form-group col-3">
                            <label>Durasi</label>
                            <input type="number" name="termin_duration" class="form-control" min="1" max="120" value="{{ $data->termin_duration ?: 1 }}" required>
                        </div>
                        <div class="form-group col-3">
                            <label>Termin</label>
                            <select name="termin" class="form-control" required>
                                <option value="month" {{ ($data->termin ?: 'month') === 'month' ? 'selected' : '' }}>Bulan</option>
                                <option value="year" {{ $data->termin === 'year' ? 'selected' : '' }}>Tahun</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group mb-1">
                        <label>Tipe</label>
                        <select name="is_trial" class="form-control" required>
                            <option value="subs" {{ $data->is_trial !== 'trial' ? 'selected' : '' }}>Berbayar</option>
                            <option value="trial" {{ $data->is_trial === 'trial' ? 'selected' : '' }}>Trial</option>
                        </select>
                    </div>
                    <small class="text-muted">Tanggal berakhir dihitung otomatis: mulai + (durasi × termin).</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan Paket</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ Modal: Tambah Addon ============ --}}
@php $availableAddons = collect($addonCatalog)->filter(fn ($a) => ! in_array($a->id, $ownedAddonIds)); @endphp
<div class="modal fade" id="addAddonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Addon</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a>
            </div>
            <form action="{{ route('subscription.addons.store', $data->id) }}" method="post">
                @csrf
                <div class="modal-body">
                    @if ($availableAddons->isEmpty())
                        <p class="text-muted mb-0">Semua addon pada katalog sudah dimiliki company ini. Gunakan menu <strong>Edit</strong> pada baris addon untuk mengubah jumlah.</p>
                    @else
                        <div class="form-group">
                            <label>Addon</label>
                            <select name="addon_id" id="addAddonSelect" class="form-control" required>
                                @foreach ($availableAddons as $a)
                                    <option value="{{ $a->id }}" data-block="{{ (int) ($a->block_size ?: 1) }}" data-price="{{ (float) $a->price }}">
                                        {{ $a->name }} ({{ $a->feature_key ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Jumlah (blok)</label>
                            <input type="number" name="quantity" id="addAddonQty" class="form-control" min="1" value="1" required>
                            <small class="text-muted" id="addAddonHint"></small>
                        </div>
                        <div class="form-row mb-0">
                            <div class="form-group col-6 mb-0">
                                <label>Mulai</label>
                                <input type="date" name="started_at" class="form-control" value="{{ \Carbon\Carbon::parse($data->started_at ?? now())->format('Y-m-d') }}" required>
                            </div>
                            <div class="form-group col-6 mb-0">
                                <label>Berakhir</label>
                                <input type="date" name="expired_at" class="form-control" value="{{ \Carbon\Carbon::parse($data->expired_at ?? now()->addMonth())->format('Y-m-d') }}" required>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    @unless ($availableAddons->isEmpty())
                        <button type="submit" class="btn btn-primary">Tambah Addon</button>
                    @endunless
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ============ Modal: Edit & Hapus Addon (per baris) ============ --}}
@foreach ($addons as $addon)
    @php
        $eBlock = (int) ($addon->block_size ?: 1);
        $eQty = $eBlock > 0 ? intdiv((int) $addon->units, $eBlock) : (int) $addon->units;
    @endphp
    <div class="modal fade" id="editAddon-{{ $addon->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Addon: {{ $addon->addon_name }}</h5>
                    <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a>
                </div>
                <form action="{{ route('subscription.addons.update', [$data->id, $addon->id]) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Jumlah (blok)</label>
                            <input type="number" name="quantity" class="form-control" min="1" value="{{ $eQty }}" required>
                            <small class="text-muted">1 blok = {{ number_format($eBlock, 0, ',', '.') }} unit • Rp {{ number_format($addon->price, 0, ',', '.') }}/blok</small>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-6">
                                <label>Mulai</label>
                                <input type="date" name="started_at" class="form-control" value="{{ $addon->started_at ? \Carbon\Carbon::parse($addon->started_at)->format('Y-m-d') : '' }}" required>
                            </div>
                            <div class="form-group col-6">
                                <label>Berakhir</label>
                                <input type="date" name="expired_at" class="form-control" value="{{ $addon->expired_at ? \Carbon\Carbon::parse($addon->expired_at)->format('Y-m-d') : '' }}" required>
                            </div>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="addonActive-{{ $addon->id }}" name="is_active" {{ $addon->is_active ? 'checked' : '' }}>
                            <label class="custom-control-label" for="addonActive-{{ $addon->id }}">Aktif</label>
                        </div>
                        @if (($addon->billing_type ?? 'recurring') !== 'onetime')
                            <small class="text-muted d-block mt-2"><i class="mdi mdi-information-outline"></i> Catatan: untuk addon recurring, status nonaktif tidak menghapus limit di aplikasi — gunakan <strong>Hapus</strong> bila ingin mencabut entitlement.</small>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteAddon-{{ $addon->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hapus Addon</h5>
                    <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a>
                </div>
                <form action="{{ route('subscription.addons.destroy', [$data->id, $addon->id]) }}" method="post">
                    @csrf
                    @method('delete')
                    <div class="modal-body">
                        <p class="mb-2">Hapus addon <strong>{{ $addon->addon_name }}</strong> dari company ini?</p>
                        <div class="alert alert-warning py-2 small mb-0">
                            <i class="mdi mdi-alert-outline"></i> Addon dihapus <strong>permanen</strong> (hard delete). Entitlement/limit dari addon ini akan hilang di aplikasi.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('script')
<script>
    $('body').on('change', '.rule-mode', function () {
        let target = $(this).data('target');
        $('.rule-limit-' + target).toggleClass('d-none', $(this).val() !== 'limited');
    });

    // Hint tambah addon: hitung total unit & harga dari jumlah blok terpilih.
    function updateAddAddonHint() {
        let opt = $('#addAddonSelect option:selected');
        if (!opt.length) return;
        let block = parseInt(opt.data('block')) || 1;
        let price = parseFloat(opt.data('price')) || 0;
        let qty = parseInt($('#addAddonQty').val()) || 0;
        let units = block * qty;
        let total = qty * price;
        let rp = new Intl.NumberFormat('id-ID').format(total);
        let unitStr = new Intl.NumberFormat('id-ID').format(units);
        $('#addAddonHint').text('1 blok = ' + new Intl.NumberFormat('id-ID').format(block) + ' unit • Total: ' + unitStr + ' unit • Rp ' + rp);
    }
    $('body').on('change', '#addAddonSelect', updateAddAddonHint);
    $('body').on('input', '#addAddonQty', updateAddAddonHint);
    $('#addAddonModal').on('shown.bs.modal', updateAddAddonHint);
</script>
@endpush
