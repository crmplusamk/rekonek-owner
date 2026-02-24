@extends('template.admin.main')

@section('content')
<style>
    .affiliator-profile-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1.5rem;
        background: #f2f4f9;
        padding: 1.25rem;
    }
    .affiliator-profile-avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ccc6ff, #c4c4ff);
        color: #ffffff;
        font-weight: 700;
        font-size: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .affiliator-profile-name {
        font-weight: 700;
        font-size: 1.75rem;
        color: #0f172a;
    }
    .affiliator-profile-meta {
        font-size: 0.95rem;
        color: #94a3b8;
    }
    .affiliator-profile-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    .affiliator-profile-chip {
        display: inline-block;
        padding: 0.4rem 1rem;
        border-radius: 0.25rem;
        font-weight: 600;
        font-size: 80%;
        color: #1f2937;
        background: #f8f9fd;
    }
    .affiliator-section-title {
        font-size: 0.75rem;
        letter-spacing: 0.08em;
        color: #98a2b3;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 1rem;
    }
    .affiliator-info-card {
        background: #fff;
        border: 1px solid #edf2f7;
        border-radius: 6px;
        padding: 1.5rem;
        box-shadow: inset 0 0 0 1px rgba(226, 232, 240, 0.3);
    }
    .affiliator-info-card-title {
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 0.65rem;
        margin-bottom: 1.25rem;
    }
    .affiliator-info-card-title i {
        color: #a0aec0;
        font-size: 1.4rem;
    }
    .affiliator-info-row {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }
    .affiliator-info-row:last-child {
        border-bottom: none;
    }
    .affiliator-info-label {
        color: #94a3b8;
        font-weight: 600;
    }
    .affiliator-info-value {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
    }
    .affiliator-info-grid {
        padding: 1.25rem;
    }
    .affiliator-info-grid .affiliator-info-col {
        padding: 0 1.25rem;
    }
    .affiliator-info-grid .affiliator-info-col:first-child {
        padding-left: 0;
    }
    .affiliator-info-grid .affiliator-info-col + .affiliator-info-col {
        border-left: 2px solid #f2f4f9;
        padding-left: 1.5rem;
    }
    @media (max-width: 767px) {
        .affiliator-info-grid .affiliator-info-col {
            padding: 0 !important;
            border-left: none !important;
        }
        .affiliator-info-grid .affiliator-info-col + .affiliator-info-col {
            border-top: 2px solid #f2f4f9;
            padding-top: 1.25rem;
            margin-top: 1rem;
        }
    }
    .affiliator-summary-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .affiliator-summary-card {
        background: #f8f9fc;
        border-radius: 6px;
        padding: 1.25rem;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.1);
        flex: 1 0 calc(33.333% - 0.67rem);
        min-width: 0;
    }
    @media (max-width: 991px) {
        .affiliator-summary-card {
            flex: 1 0 calc(50% - 0.5rem);
        }
    }
    @media (max-width: 575px) {
        .affiliator-summary-card {
            flex: 1 0 100%;
        }
    }
    .affiliator-summary-label {
        margin-bottom: 0.25rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: #6b7280;
    }
    .affiliator-summary-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0;
    }
    .affiliator-summary-sub {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 0.25rem;
    }
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
</style>

<div class="border-bottom pt-2 pb-3 d-flex justify-content-between align-items-center">
    <h4 class="pt-2 mb-0">
        <a href="{{ route('setting-affiliator.index') }}" class="text-dark">
            <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
        </a>
        Detail Reporting Affiliator
    </h4>
</div>

<div class="row">
    <div class="col-12 pt-3">
        {{-- Filter Kode Promo (paling atas — semua data bergantung pada filter ini) --}}
        <div class="mb-4" style="width: 320px;">
            <label for="filterPromoCode" class="affiliator-section-title mb-2 d-block">Filter Kode Promo</label>
            <select id="filterPromoCode" class="form-control select-2" style="width: 100%;">
                <option value="all">Semua Kode Promo</option>
                @foreach($promoCodes as $pc)
                    <option value="{{ $pc->id }}">{{ $pc->code }} - {{ $pc->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Card 1: Informasi Affiliator --}}
        <div class="card border-1 mb-4">
            <div class="card-body p-0">
                <div class="affiliator-profile-header">
                    <div class="d-flex align-items-center flex-wrap flex-grow-1">
                        <div class="affiliator-profile-avatar">
                            {{ strtoupper(substr($user->name ?? 'A', 0, 2)) }}
                        </div>
                        <div class="ml-0 ml-sm-3 mt-3 mt-sm-0">
                            <h4 class="affiliator-profile-name mb-1">{{ $user->name }}</h4>
                            <div class="affiliator-profile-meta">{{ $user->email }}</div>
                            <div class="affiliator-profile-tags">
                                @foreach($promoCodes as $pc)
                                    <span class="affiliator-profile-chip">{{ $pc->code }}</span>
                                @endforeach
                                @if($promoCodes->isEmpty())
                                    <span class="affiliator-profile-chip text-muted">Belum ada kode promo</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row affiliator-info-grid">
                    <div class="col-12 col-md-4 affiliator-info-col mb-4 mb-md-0">
                        <div class="affiliator-section-title">Informasi</div>
                        <div class="affiliator-info-row">
                            <span class="affiliator-info-label">Nama</span>
                            <span class="affiliator-info-value">{{ $user->name }}</span>
                        </div>
                        <div class="affiliator-info-row">
                            <span class="affiliator-info-label">Email</span>
                            <span class="affiliator-info-value text-truncate" style="max-width: 200px;">{{ $user->email }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 affiliator-info-col mb-3 mb-md-0">
                        <div class="affiliator-section-title">Persentase</div>
                        <div class="affiliator-info-row">
                            <span class="affiliator-info-label">Pembelian Baru</span>
                            <span class="affiliator-info-value">{{ $affiliatorConfig && $affiliatorConfig->commission_value_registrasi !== null ? number_format((float) $affiliatorConfig->commission_value_registrasi, 2, ',', '.') . '%' : '-' }}</span>
                        </div>
                        <div class="affiliator-info-row">
                            <span class="affiliator-info-label">Perpanjangan</span>
                            <span class="affiliator-info-value">{{ $affiliatorConfig && $affiliatorConfig->commission_value_perpanjangan !== null ? number_format((float) $affiliatorConfig->commission_value_perpanjangan, 2, ',', '.') . '%' : '-' }}</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 affiliator-info-col mb-3 mb-md-0">
                        <div class="affiliator-section-title">Total Komisi</div>
                        <div class="affiliator-info-row">
                            <span class="affiliator-info-label">Komisi didapatkan</span>
                            <span class="affiliator-info-value">Rp {{ number_format($totalCommission ?? 0, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 2: Reporting Komisi & Nilai --}}
        <div class="card border-1 mb-4">
            <div class="card-body">
                <div class="affiliator-section-title">Reporting Komisi & Nilai</div>
                <div class="affiliator-summary-grid">
                    <div class="affiliator-summary-card">
                        <div class="affiliator-summary-label">Total Register Usage</div>
                        <div class="affiliator-summary-value" id="summaryRegister">0</div>
                        <div class="affiliator-summary-sub">Hanya pencatatan</div>
                    </div>
                    <div class="affiliator-summary-card">
                        <div class="affiliator-summary-label">Total Pembelian Baru</div>
                        <div class="affiliator-summary-value" id="summaryNewPurchase">Rp 0</div>
                        <div class="affiliator-summary-sub" id="countNewPurchase">0 Transaksi</div>
                    </div>
                    <div class="affiliator-summary-card">
                        <div class="affiliator-summary-label">Total Perpanjangan</div>
                        <div class="affiliator-summary-value" id="summaryRenewal">Rp 0</div>
                        <div class="affiliator-summary-sub" id="countRenewal">0 Transaksi</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card 3: Tabel Reporting per Penggunaan Promo --}}
        <div class="card border-1">
            <div class="card-body">
                <ul class="nav nav-tabs" id="promoUsageTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="register-tab" data-toggle="tab" href="#register" role="tab" aria-controls="register" aria-selected="true">Register Usage</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="pembelian-baru-tab" data-toggle="tab" href="#pembelian-baru" role="tab" aria-controls="pembelian-baru" aria-selected="false">Pembelian Baru</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="perpanjangan-tab" data-toggle="tab" href="#perpanjangan" role="tab" aria-controls="perpanjangan" aria-selected="false">Perpanjangan</a>
                    </li>
                </ul>
                <div class="tab-content border border-top-0 p-3" id="promoUsageTabsContent">
                    <div class="tab-pane fade show active" id="register" role="tabpanel" aria-labelledby="register-tab">
                        <div class="table-responsive">
                            <table class="table table-hover w-100" id="table-register">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Kode Promo</th>
                                        <th>Nama Client</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pembelian-baru" role="tabpanel" aria-labelledby="pembelian-baru-tab">
                        <div class="table-responsive">
                            <table class="table table-hover w-100" id="table-new-purchase">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Kode Promo</th>
                                        <th>Invoice</th>
                                        <th>Client</th>
                                        <th>Total Pembelian</th>
                                        <th>Diskon</th>
                                        <th>Komisi Affiliator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="perpanjangan" role="tabpanel" aria-labelledby="perpanjangan-tab">
                        <div class="table-responsive">
                            <table class="table table-hover w-100" id="table-renewal">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Kode Promo</th>
                                        <th>Invoice</th>
                                        <th>Client</th>
                                        <th>Total Pembelian</th>
                                        <th>Diskon</th>
                                        <th>Komisi Affiliator</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('head')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    var detailDataUrl = "{{ route('setting-affiliator.detail-data', $user->id) }}";

    $('#filterPromoCode').select2();

    function loadUsageData(promoId) {
        var url = detailDataUrl + (promoId && promoId !== 'all' ? '?promo_id=' + encodeURIComponent(promoId) : '');
        $.get(url)
            .done(function(res) {
                var s = res.summary || {};
                $('#summaryRegister').text(s.register_count ?? 0);
                $('#summaryNewPurchase').text('Rp ' + formatNumber(s.new_purchase_total ?? 0));
                $('#countNewPurchase').text((s.new_purchase_count ?? 0) + ' Transaksi');
                $('#summaryRenewal').text('Rp ' + formatNumber(s.renewal_total ?? 0));
                $('#countRenewal').text((s.renewal_count ?? 0) + ' Transaksi');

                renderRegisterTable(res.list_register || []);
                renderNewPurchaseTable(res.list_new_purchase || []);
                renderRenewalTable(res.list_renewal || []);
            })
            .fail(function() {
                $('#summaryRegister').text('0');
                $('#summaryNewPurchase').text('Rp 0');
                $('#countNewPurchase').text('0 Transaksi');
                $('#summaryRenewal').text('Rp 0');
                $('#countRenewal').text('0 Transaksi');
                renderRegisterTable([]);
                renderNewPurchaseTable([]);
                renderRenewalTable([]);
            });
    }

    function formatNumber(n) {
        return Number(n).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function renderRegisterTable(rows) {
        var tbody = $('#table-register tbody');
        tbody.empty();
        if (rows.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted">—</td></tr>');
            return;
        }
        rows.forEach(function(r) {
            tbody.append(
                '<tr><td>' + r.no + '</td><td>' + escapeHtml(r.date) + '</td><td>' + escapeHtml(r.promo_code) + '</td><td>' + escapeHtml(r.client_name) + '</td><td>' + escapeHtml(r.email) + '</td></tr>'
            );
        });
    }

    function renderNewPurchaseTable(rows) {
        var tbody = $('#table-new-purchase tbody');
        tbody.empty();
        if (rows.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">—</td></tr>');
            return;
        }
        rows.forEach(function(r) {
            tbody.append(
                '<tr><td>' + r.no + '</td><td>' + escapeHtml(r.date) + '</td><td>' + escapeHtml(r.promo_code) + '</td><td>' + escapeHtml(r.invoice_code) + '</td><td>' + escapeHtml(r.client_name) + '</td><td>Rp ' + formatNumber(r.total_purchase) + '</td><td>Rp ' + formatNumber(r.discount_amount) + '</td><td>Rp ' + formatNumber(r.commission) + '</td></tr>'
            );
        });
    }

    function renderRenewalTable(rows) {
        var tbody = $('#table-renewal tbody');
        tbody.empty();
        if (rows.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center text-muted">—</td></tr>');
            return;
        }
        rows.forEach(function(r) {
            tbody.append(
                '<tr><td>' + r.no + '</td><td>' + escapeHtml(r.date) + '</td><td>' + escapeHtml(r.promo_code) + '</td><td>' + escapeHtml(r.invoice_code) + '</td><td>' + escapeHtml(r.client_name) + '</td><td>Rp ' + formatNumber(r.total_purchase) + '</td><td>Rp ' + formatNumber(r.discount_amount) + '</td><td>Rp ' + formatNumber(r.commission) + '</td></tr>'
            );
        });
    }

    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $('#filterPromoCode').on('change', function() {
        loadUsageData($(this).val());
    });

    loadUsageData($('#filterPromoCode').val());
});
</script>
@endpush
