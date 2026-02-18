@extends('template.admin.main')

@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('setting-affiliator.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Reporting Usage Promo Code
        </h4>
        <p class="text-muted mb-2"><small>Detail penggunaan kode promo dan komisi per kategori (Registrasi Baru / Perpanjangan) untuk affiliator ini.</small></p>
    </div>
</div>

{{-- Card: Info Affiliator + Konfigurasi Komisi --}}
<div class="row mt-3">
    <div class="col-12">
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <h3 class="element-header mb-3">Informasi Affiliator & Konfigurasi Komisi</h3>
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group mb-2">
                            <label class="text-muted small text-uppercase font-weight-bold">Nama</label>
                            <div class="d-flex align-items-center">
                                <i class="mdi mdi-account-outline text-primary mr-2"></i>
                                <span class="font-weight-bold">{{ $user->name }}</span>
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="text-muted small text-uppercase font-weight-bold">Email</label>
                            <div class="d-flex align-items-center">
                                <i class="mdi mdi-email-outline text-primary mr-2"></i>
                                <span>{{ $user->email }}</span>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="text-muted small text-uppercase font-weight-bold">Kode Promo</label>
                            <div class="d-flex flex-wrap align-items-center">
                                @forelse($user->promoCodesAsAffiliator as $promo)
                                    <span class="badge badge-primary mr-1 mb-1 px-2 py-1 font-weight-bold">{{ $promo->code }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 d-none d-md-block border-left"></div>
                    <div class="col-md-5">
                        <label class="text-muted small text-uppercase font-weight-bold mb-2">Payback / Komisi</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="small text-muted mb-1">Registrasi Baru</div>
                                    <div class="h4 mb-0 font-weight-bold text-primary">
                                        {{ $config ? number_format((float) $config->commission_value_registrasi, 1) : '0' }}%
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-3 bg-light">
                                    <div class="small text-muted mb-1">Perpanjangan</div>
                                    <div class="h4 mb-0 font-weight-bold text-success">
                                        {{ $config ? number_format((float) $config->commission_value_perpanjangan, 1) : '0' }}%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tabs: Registrasi Baru | Perpanjangan --}}
<div class="row">
    <div class="col-12">
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <ul class="nav nav-tabs nav-tabs-card mb-3" id="usageTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="tab-registrasi" data-toggle="tab" href="#content-registrasi" role="tab">
                            <i class="mdi mdi-account-plus-outline mr-1"></i> Registrasi Baru
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tab-perpanjangan" data-toggle="tab" href="#content-perpanjangan" role="tab">
                            <i class="mdi mdi-renew mr-1"></i> Perpanjangan
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="usageTabContent">
                    {{-- Tab Registrasi Baru --}}
                    <div class="tab-pane fade show active" id="content-registrasi" role="tabpanel">
                        @php
                            $totalUseReg = count($usageRegistrasi);
                            $totalAmountReg = collect($usageRegistrasi)->sum('amount');
                            $totalCommissionReg = collect($usageRegistrasi)->sum('commission_amount');
                        @endphp
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card border-0 bg-primary text-white shadow-sm">
                                    <div class="card-body py-3">
                                        <div class="small text-white-50 text-uppercase font-weight-bold">Total Penggunaan</div>
                                        <div class="h4 mb-0 font-weight-bold">{{ number_format($totalUseReg) }}x</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm" style="background: #f8f9fc;">
                                    <div class="card-body py-3">
                                        <div class="small text-muted text-uppercase font-weight-bold">Total Nilai Transaksi</div>
                                        <div class="h4 mb-0 font-weight-bold text-dark">Rp {{ number_format($totalAmountReg, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-success text-white shadow-sm">
                                    <div class="card-body py-3">
                                        <div class="small text-white-50 text-uppercase font-weight-bold">Total Komisi</div>
                                        <div class="h4 mb-0 font-weight-bold">Rp {{ number_format($totalCommissionReg, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h6 class="element-header mb-2">Riwayat Penggunaan (Registrasi Baru)</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-center">Kode Promo</th>
                                        <th class="text-center">Customer / Company</th>
                                        <th class="text-right">Nilai Transaksi</th>
                                        <th class="text-center">Komisi (%)</th>
                                        <th class="text-right">Komisi (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($usageRegistrasi as $row)
                                        <tr>
                                            <td class="text-center">{{ $row['date'] }}</td>
                                            <td class="text-center"><span class="badge badge-primary">{{ $row['code'] }}</span></td>
                                            <td class="text-center">{{ $row['customer'] }}</td>
                                            <td class="text-right">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($row['commission_pct'], 1) }}%</td>
                                            <td class="text-right font-weight-bold">Rp {{ number_format($row['commission_amount'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data penggunaan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Tab Perpanjangan --}}
                    <div class="tab-pane fade" id="content-perpanjangan" role="tabpanel">
                        @php
                            $totalUsePer = count($usagePerpanjangan);
                            $totalAmountPer = collect($usagePerpanjangan)->sum('amount');
                            $totalCommissionPer = collect($usagePerpanjangan)->sum('commission_amount');
                        @endphp
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card border-0 bg-primary text-white shadow-sm">
                                    <div class="card-body py-3">
                                        <div class="small text-white-50 text-uppercase font-weight-bold">Total Penggunaan</div>
                                        <div class="h4 mb-0 font-weight-bold">{{ number_format($totalUsePer) }}x</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm" style="background: #f8f9fc;">
                                    <div class="card-body py-3">
                                        <div class="small text-muted text-uppercase font-weight-bold">Total Nilai Transaksi</div>
                                        <div class="h4 mb-0 font-weight-bold text-dark">Rp {{ number_format($totalAmountPer, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-success text-white shadow-sm">
                                    <div class="card-body py-3">
                                        <div class="small text-white-50 text-uppercase font-weight-bold">Total Komisi</div>
                                        <div class="h4 mb-0 font-weight-bold">Rp {{ number_format($totalCommissionPer, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h6 class="element-header mb-2">Riwayat Penggunaan (Perpanjangan)</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-center">Kode Promo</th>
                                        <th class="text-center">Customer / Company</th>
                                        <th class="text-right">Nilai Transaksi</th>
                                        <th class="text-center">Komisi (%)</th>
                                        <th class="text-right">Komisi (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($usagePerpanjangan as $row)
                                        <tr>
                                            <td class="text-center">{{ $row['date'] }}</td>
                                            <td class="text-center"><span class="badge badge-primary">{{ $row['code'] }}</span></td>
                                            <td class="text-center">{{ $row['customer'] }}</td>
                                            <td class="text-right">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                            <td class="text-center">{{ number_format($row['commission_pct'], 1) }}%</td>
                                            <td class="text-right font-weight-bold">Rp {{ number_format($row['commission_amount'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada data penggunaan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <a href="{{ route('setting-affiliator.index') }}" class="btn btn-outline-secondary">
            <i class="mdi mdi-arrow-left"></i> Kembali ke Daftar Affiliator
        </a>
    </div>
</div>
@endsection

@push('head')
<style>
.nav-tabs-card .nav-link { font-weight: 600; }
.element-header { font-size: 0.95rem; font-weight: 600; color: #5a5c69; }
</style>
@endpush
