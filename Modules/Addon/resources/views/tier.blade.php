@extends('template.admin.main')

@section('content')

<div class="row">
    <div class="col-12 mt-2 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h4 class="mb-0">Aturan Diskon (Price Tier)</h4>
            <p class="text-muted mb-0">
                {{ $addonModel->name }} &middot; {{ $addonModel->feature->name ?? '-' }} &middot;
                Harga master {{ "Rp. ".number_format($addonModel->price, 0, ',', '.') }}/blok
            </p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('addon.index') }}" class="btn btn-outline-secondary">
                <i class="mdi mdi-arrow-left"></i> Kembali
            </a>
            <a data-toggle="modal" data-target="#addTier" class="btn btn-primary pointer">
                <i class="mdi mdi-plus"></i> Tambah Tier
            </a>
        </div>
    </div>

    <div class="col-12 mt-3">
        <div class="alert alert-info mb-0">
            <b>unit_price</b> = harga per blok pada tier ini. <b>percent</b> = diskon % dari harga master (0-100).
            Resolusi harga: tier dengan <code>min_quantity</code> terbesar yang &le; jumlah blok yang dipakai yang
            dipakai. Bila tidak ada tier aktif yang cocok, harga kembali ke harga master addon.
        </div>
    </div>

    <div class="col-12 mt-4 pt-1">
        <div class="border-1">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-center">Min. Qty (blok)</th>
                            <th class="text-center">Tipe</th>
                            <th class="text-center">Nilai</th>
                            <th class="text-center">Contoh Harga/Blok</th>
                            <th class="text-center">Label</th>
                            <th class="text-center">Status</th>
                            <th data-orderable="false"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tiers as $tier)
                            <tr>
                                <td class="text-center">{{ $tier->min_quantity }}</td>
                                <td class="text-center">{{ $tier->type === 'percent' ? 'Persen (%)' : 'Harga / Blok' }}</td>
                                <td class="text-center">
                                    @if ($tier->type === 'percent')
                                        {{ rtrim(rtrim(number_format($tier->value, 2, ',', '.'), '0'), ',') }}%
                                    @else
                                        {{ "Rp. ".number_format($tier->value, 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="text-center">{{ "Rp. ".number_format($pricing->unitPrice($addonModel, $tier->min_quantity), 0, ',', '.') }}</td>
                                <td class="text-center">{{ $tier->label ?: '-' }}</td>
                                <td class="text-center">
                                    <span class="badge badge-pill badge-{{ $tier->is_active ? 'success' : 'danger' }}">{{ $tier->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </td>
                                <td class="text-nowrap text-center">
                                    <a href="#" data-toggle="dropdown" class="text-muted" aria-expanded="false">
                                        <i class="mdi mdi-dots-horizontal mdi-24px"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right" role="menu">
                                        <a class="dropdown-item pointer" data-toggle="modal" data-target="#editTier-{{ $tier->id }}">
                                            <i class="mdi mdi-pencil"></i> Edit
                                        </a>
                                        <a class="dropdown-item pointer" data-toggle="modal" data-target="#statusTier-{{ $tier->id }}">
                                            <i class="mdi mdi-{{ $tier->is_active ? 'close' : 'check' }}"></i> {{ $tier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </a>
                                        <a class="dropdown-item pointer text-danger" data-toggle="modal" data-target="#deleteTier-{{ $tier->id }}">
                                            <i class="mdi mdi-delete"></i> Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada aturan diskon untuk addon ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tambah Tier --}}
<div class="modal fade" id="addTier" aria-labelledby="addTier" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Aturan Diskon</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></a>
            </div>
            <div class="modal-body">
                <form action="{{ route('addon.tier.store', $addonModel->id) }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label>Minimal Kuantitas (blok) <span class="text-danger">*</span></label>
                        <input name="min_quantity" type="number" min="1" class="form-control" value="{{ old('min_quantity') }}" required>
                        <small class="form-text text-muted">Tier berlaku bila jumlah blok yang dipakai &ge; nilai ini.</small>
                    </div>
                    <div class="form-group">
                        <label>Tipe <span class="text-danger">*</span></label>
                        <select name="type" class="form-control" required>
                            <option value="unit_price" {{ old('type', 'unit_price') === 'unit_price' ? 'selected' : '' }}>Harga per Blok (unit_price)</option>
                            <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Diskon Persen (percent)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nilai <span class="text-danger">*</span></label>
                        <input name="value" type="number" step="0.01" min="0" class="form-control" value="{{ old('value') }}" required>
                        <small class="form-text text-muted">unit_price: nominal Rp per blok. percent: 0-100 (%).</small>
                    </div>
                    <div class="form-group">
                        <label>Label</label>
                        <input name="label" type="text" class="form-control" value="{{ old('label') }}" placeholder="mis. Diskon Grosir">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach ($tiers as $tier)
    {{-- Modal Edit --}}
    <div class="modal fade text-left" id="editTier-{{ $tier->id }}" aria-labelledby="editTier" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Aturan Diskon</h5>
                    <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></a>
                </div>
                <div class="modal-body">
                    <form action="{{ route('addon.tier.update', $tier->id) }}" method="post">
                        @csrf
                        @method('put')
                        <div class="form-group">
                            <label>Minimal Kuantitas (blok) <span class="text-danger">*</span></label>
                            <input name="min_quantity" type="number" min="1" class="form-control" value="{{ $tier->min_quantity }}" required>
                        </div>
                        <div class="form-group">
                            <label>Tipe <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="unit_price" {{ $tier->type === 'unit_price' ? 'selected' : '' }}>Harga per Blok (unit_price)</option>
                                <option value="percent" {{ $tier->type === 'percent' ? 'selected' : '' }}>Diskon Persen (percent)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Nilai <span class="text-danger">*</span></label>
                            <input name="value" type="number" step="0.01" min="0" class="form-control" value="{{ $tier->value }}" required>
                            <small class="form-text text-muted">unit_price: nominal Rp per blok. percent: 0-100 (%).</small>
                        </div>
                        <div class="form-group">
                            <label>Label</label>
                            <input name="label" type="text" class="form-control" value="{{ $tier->label }}" placeholder="mis. Diskon Grosir">
                        </div>
                        <div class="form-group form-check">
                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editTierActive-{{ $tier->id }}" {{ $tier->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="editTierActive-{{ $tier->id }}">Aktif</label>
                        </div>
                        <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                            <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Toggle Status --}}
    <div class="modal fade" id="statusTier-{{ $tier->id }}" aria-labelledby="statusTier" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body modal-body-lg text-center text-wrap text-break">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                    <div class="text-center mt-4">
                        <h5>Konfirmasi {{ $tier->is_active ? 'Nonaktifkan' : 'Aktifkan' }} Tier</h5>
                        <p class="mt-2 text-lg">
                            Apakah anda yakin ingin {{ $tier->is_active ? 'menonaktifkan' : 'mengaktifkan' }}
                            tier min. {{ $tier->min_quantity }} blok ini?
                        </p>
                    </div>
                    <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                        <a data-dismiss="modal" class="btn btn-danger mr-3">Batal</a>
                        <a href="{{ route('addon.tier.status', $tier->id) }}" class="btn btn-primary mr-3">
                            {{ $tier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Hapus --}}
    <div class="modal fade" id="deleteTier-{{ $tier->id }}" aria-labelledby="deleteTier" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body modal-body-lg text-center text-wrap text-break">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                    <div class="text-center mt-4">
                        <h5>Konfirmasi Hapus Data</h5>
                        <p class="mt-2 text-lg">Apakah anda yakin ingin menghapus tier min. {{ $tier->min_quantity }} blok ini?</p>
                        <p class="text-danger">Proses ini tidak dapat dibatalkan</p>
                    </div>
                    <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                        <a data-dismiss="modal" class="btn btn-danger mr-3">Batal</a>
                        <form action="{{ route('addon.tier.destroy', $tier->id) }}" method="post">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-primary">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
