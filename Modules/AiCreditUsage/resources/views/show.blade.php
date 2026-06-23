@extends('template.admin.main')

@push('head')
<style>
.stat-lbl {
    display: block;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #6c757d;
    margin-bottom: 8px;
}
</style>
@endpush

@section('content')
<div class="row">

    {{-- Page header --}}
    <div class="col-12 mt-2 mb-3">
        <div class="d-flex justify-content-between align-items-end flex-wrap">
            <div class="mb-2">
                <a href="{{ route('ai-credit-usage.index') }}" class="text-muted small d-inline-flex align-items-center mb-1" style="gap: 4px;">
                    <i class="mdi mdi-arrow-left"></i> Laporan AI Credit
                </a>
                <h4 class="mb-1 font-weight-bold">{{ $companyName }}</h4>
                <small class="text-muted">Detail pemakaian token &amp; credit AI organisasi</small>
            </div>
            <div class="mb-2" style="min-width: 260px;">
                <span class="stat-lbl">Periode</span>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="mdi mdi-calendar-range"></i></span>
                    </div>
                    <input type="text" id="dateRange" class="form-control" readonly>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="col-12 mb-3">
        <div class="row">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100" style="border-left: 3px solid #2465FF;">
                    <div class="card-body">
                        <span class="stat-lbl">Total Credit Terpakai</span>
                        <div class="number-stats font-weight-bold text-dark" id="card-total-credits">0</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="stat-lbl">Total Token</span>
                        <h3 class="mb-2" id="card-total-tokens">0</h3>
                        <div class="progress mb-1" style="height: 5px;" title="Biru = input, Abu = output">
                            <div class="progress-bar bg-primary" id="token-bar-in" style="width:50%"></div>
                            <div class="progress-bar bg-secondary" id="token-bar-out" style="width:50%"></div>
                        </div>
                        <small class="text-muted">
                            In: <span id="card-input-tokens">0</span> &middot; Out: <span id="card-output-tokens">0</span>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="stat-lbl">Total Response AI</span>
                        <h3 class="mb-0" id="card-event-count">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kelola saldo credit AI (admin owner): reset + penyesuaian manual --}}
    <div class="col-12 mb-3">
        <div class="border-1">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="stat-lbl mb-0">Kelola Saldo Credit AI</span>
                <button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#resetCreditModal">
                    <i class="mdi mdi-restore mr-1"></i> Reset Credit
                </button>
            </div>
            <div class="p-3">
                <form method="POST" action="{{ route('ai-credit-usage.company.adjust', ['company' => $companyId]) }}"
                      class="form-row align-items-end"
                      onsubmit="return confirm('Yakin menyesuaikan credit AI organisasi ini?');">
                    @csrf
                    <div class="form-group col-md-3 mb-2">
                        <label class="stat-lbl">Aksi</label>
                        <select name="direction" class="form-control">
                            <option value="grant">Tambah (grant)</option>
                            <option value="deduct">Kurangi (deduct)</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label class="stat-lbl">Jumlah credit</label>
                        <input type="number" name="amount" min="1" step="1" class="form-control" required placeholder="mis. 1000">
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <label class="stat-lbl">Alasan (opsional)</label>
                        <input type="text" name="reason" maxlength="500" class="form-control" placeholder="mis. kompensasi gangguan">
                    </div>
                    <div class="form-group col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block"><i class="mdi mdi-check mr-1"></i> Terapkan</button>
                    </div>
                </form>
                <small class="text-muted">Reset & penyesuaian tidak menghapus history — tiap aksi dicatat sebagai entri audit (siapa &amp; alasan).</small>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi reset --}}
    <div class="modal fade" id="resetCreditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset AI Credit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Reset akan mengembalikan pemakaian credit AI organisasi <b>{{ $companyName }}</b> pada cycle berjalan ke <b>0</b> (sisa kembali penuh). History pemakaian tetap tersimpan; reset dicatat sebagai entri audit.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <form method="POST" action="{{ route('ai-credit-usage.company.reset', ['company' => $companyId]) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger"><i class="mdi mdi-restore mr-1"></i> Ya, Reset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Trend chart + feature breakdown --}}
    <div class="col-md-8 mb-3">
        <div class="border-1 h-100">
            <div class="p-3 border-bottom">
                <span class="stat-lbl mb-0">Tren Harian — Credit</span>
            </div>
            <div class="p-3">
                <canvas id="aiUsageTrendChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="border-1 h-100">
            <div class="p-3 border-bottom">
                <span class="stat-lbl mb-0">Berdasarkan Fitur</span>
            </div>
            <div class="p-3">
                <div id="feature-breakdown">
                    <p class="text-muted small mb-0">Tidak ada data.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Per-response table --}}
    <div class="col-12">
        <div class="border-1">
            <div class="p-3 border-bottom">
                <span class="stat-lbl mb-0">Daftar Response</span>
            </div>
            <div class="table-responsive">
                <table class="table ai-usage-responses-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Fitur</th>
                            <th class="text-right">Input Token</th>
                            <th class="text-right">Output Token</th>
                            <th class="text-right">Total Token</th>
                            <th class="text-right">Credit</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('script')
<script>

    let responsesTable;
    let trendChart = null;
    let currentStart = moment().subtract(29, 'days').format('YYYY-MM-DD');
    let currentEnd = moment().format('YYYY-MM-DD');

    const FEATURE_LABELS = {
        chat_auto_reply: 'Auto-Reply Chat',
        agent_testing_sandbox: 'Live Testing',
    };

    function fmt(n) {
        return Number(n || 0).toLocaleString('id-ID');
    }

    function featureLabel(value) {
        return FEATURE_LABELS[value] || value || '-';
    }

    function animateCount(el, target, duration) {
        if (!el) return;
        const end = Number(target) || 0;
        if (!end) { el.textContent = '0'; return; }
        let current = 0;
        const frames = Math.max(1, Math.round(duration / 16));
        const step = end / frames;
        let f = 0;
        const timer = setInterval(function () {
            f++;
            current = Math.min(current + step, end);
            el.textContent = Math.round(current).toLocaleString('id-ID');
            if (f >= frames) { el.textContent = Math.round(end).toLocaleString('id-ID'); clearInterval(timer); }
        }, 16);
    }

    function updateTokenBar(inp, out) {
        const total = inp + out;
        const inPct  = total ? Math.max(5, inp / total * 100) : 50;
        const outPct = total ? Math.max(5, out / total * 100) : 50;
        document.getElementById('token-bar-in').style.width  = inPct.toFixed(1) + '%';
        document.getElementById('token-bar-out').style.width = outPct.toFixed(1) + '%';
    }

    $(document).ready(function () {
        initDateRange();
        initTable();
        loadSummary();
    });

    function initDateRange() {
        $('#dateRange').daterangepicker({
            startDate: moment(currentStart),
            endDate: moment(currentEnd),
            maxDate: moment(),
            locale: {
                format: 'DD MMM YYYY',
                separator: ' - ',
                applyLabel: 'Terapkan',
                cancelLabel: 'Batal',
                customRangeLabel: 'Kustom',
                daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                firstDay: 1,
            },
            ranges: {
                'Hari Ini': [moment(), moment()],
                '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
                '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
                'Bulan Ini': [moment().startOf('month'), moment()],
                'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            },
        }, function (start, end) {
            currentStart = start.format('YYYY-MM-DD');
            currentEnd = end.format('YYYY-MM-DD');
            refresh();
        });
    }

    function initTable() {
        responsesTable = $('.ai-usage-responses-table').DataTable({
            destroy: true,
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('ai-credit-usage.company.table', ['company' => $companyId]) }}",
                dataType: 'json',
                data: function (d) {
                    d.start_date = currentStart;
                    d.end_date = currentEnd;
                },
            },
            columns: [
                { data: 'created_at', render: function (d, type) {
                    if (type !== 'display' || !d) return d;
                    return moment(d).format('DD MMM YYYY HH:mm');
                }},
                { data: 'feature', render: featureLabel },
                { data: 'input_tokens',  className: 'text-right', render: fmt },
                { data: 'output_tokens', className: 'text-right', render: fmt },
                { data: 'total_tokens',  className: 'text-right', render: fmt },
                { data: 'credits_used',  className: 'text-right', render: fmt },
            ],
            order: [[0, 'desc']],
            dom: 'lrtip',
            length: 10,
            lengthChange: false,
        });
    }

    function refresh() {
        loadSummary();
        if (responsesTable) responsesTable.ajax.reload();
    }

    function loadSummary() {
        $.ajax({
            url: "{{ route('ai-credit-usage.company.summary', ['company' => $companyId]) }}",
            data: { start_date: currentStart, end_date: currentEnd },
            dataType: 'json',
            success: function (res) {
                const s = res.summary || {};
                const inp = Number(s.total_input_tokens) || 0;
                const out = Number(s.total_output_tokens) || 0;
                animateCount(document.getElementById('card-total-credits'), s.total_credits, 700);
                animateCount(document.getElementById('card-total-tokens'), s.total_tokens, 500);
                animateCount(document.getElementById('card-input-tokens'), inp, 500);
                animateCount(document.getElementById('card-output-tokens'), out, 500);
                animateCount(document.getElementById('card-event-count'), s.event_count, 450);
                updateTokenBar(inp, out);
                renderFeatures(res.features || []);
                renderTrend(res.trend || []);
            },
        });
    }

    function renderFeatures(features) {
        if (!features.length) {
            $('#feature-breakdown').html('<p class="text-muted small mb-0">Tidak ada data.</p>');
            return;
        }
        let html = '<table class="table table-sm mb-0"><tbody>';
        features.forEach(function (f) {
            html += '<tr>'
                + '<td class="border-0 pb-0">' + featureLabel(f.feature) + '</td>'
                + '<td class="border-0 pb-0 text-right font-weight-bold text-primary">' + fmt(f.total_credits) + ' cr</td>'
                + '</tr>'
                + '<tr><td colspan="2" class="text-muted small pt-0">'
                + fmt(f.event_count) + ' response &middot; ' + fmt(f.total_tokens) + ' token</td></tr>';
        });
        html += '</tbody></table>';
        $('#feature-breakdown').html(html);
    }

    function renderTrend(trend) {
        const labels  = trend.map(function (t) { return t.date; });
        const credits = trend.map(function (t) { return t.credits; });

        if (trendChart) {
            trendChart.data.labels = labels;
            trendChart.data.datasets[0].data = credits;
            trendChart.update();
            return;
        }

        const ctx = document.getElementById('aiUsageTrendChart').getContext('2d');
        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Credit',
                    data: credits,
                    borderColor: '#2465FF',
                    backgroundColor: 'rgba(36,101,255,0.07)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });
    }

</script>
@endpush
