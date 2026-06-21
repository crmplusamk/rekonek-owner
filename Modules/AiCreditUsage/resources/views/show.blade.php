@extends('template.admin.main')

@section('content')
<div class="row">
    <div class="col-12 mt-2">
        <div class="d-flex justify-content-between align-items-end flex-wrap">
            <div class="mb-2">
                <a href="{{ route('ai-credit-usage.index') }}" class="text-muted small d-inline-block mb-1">
                    <i class="mdi mdi-arrow-left"></i> Kembali ke Laporan AI Credit
                </a>
                <h4 class="mb-0">{{ $companyName }}</h4>
                <small class="text-muted">Detail pemakaian token &amp; credit AI organisasi</small>
            </div>
            <div class="mb-2" style="min-width: 260px;">
                <label class="mb-1 small text-muted d-block">Periode</label>
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
    <div class="col-12 mt-3">
        <div class="row">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1">Total Credit Terpakai</p>
                    <h3 class="mb-0" id="card-total-credits">0</h3>
                </div></div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1">Total Token</p>
                    <h3 class="mb-0" id="card-total-tokens">0</h3>
                    <small class="text-muted">
                        Input: <span id="card-input-tokens">0</span> &middot; Output: <span id="card-output-tokens">0</span>
                    </small>
                </div></div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1">Total Response AI</p>
                    <h3 class="mb-0" id="card-event-count">0</h3>
                </div></div>
            </div>
        </div>
    </div>

    {{-- Daily trend + feature split --}}
    <div class="col-12">
        <div class="row">
            <div class="col-md-8 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-2">Tren Harian (Credit)</p>
                    <canvas id="aiUsageTrendChart" height="110"></canvas>
                </div></div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-2">Berdasarkan Fitur</p>
                    <div id="feature-breakdown">
                        <p class="text-muted small mb-0">Tidak ada data.</p>
                    </div>
                </div></div>
            </div>
        </div>
    </div>

    {{-- Per-response breakdown --}}
    <div class="col-12 mt-1">
        <div class="border-1">
            <div class="p-3 border-bottom">
                <h5 class="mb-0">Daftar Response</h5>
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
                    if (type !== 'display' || !d) { return d; }
                    return moment(d).format('DD MMM YYYY HH:mm');
                } },
                { data: 'feature', render: featureLabel },
                { data: 'input_tokens', className: 'text-right', render: fmt },
                { data: 'output_tokens', className: 'text-right', render: fmt },
                { data: 'total_tokens', className: 'text-right', render: fmt },
                { data: 'credits_used', className: 'text-right', render: fmt },
            ],
            order: [[0, 'desc']],
            dom: 'lrtip',
            length: 10,
            lengthChange: false,
        });
    }

    function refresh() {
        loadSummary();
        if (responsesTable) {
            responsesTable.ajax.reload();
        }
    }

    function loadSummary() {
        $.ajax({
            url: "{{ route('ai-credit-usage.company.summary', ['company' => $companyId]) }}",
            data: { start_date: currentStart, end_date: currentEnd },
            dataType: 'json',
            success: function (res) {
                const s = res.summary || {};
                $('#card-total-credits').text(fmt(s.total_credits));
                $('#card-total-tokens').text(fmt(s.total_tokens));
                $('#card-input-tokens').text(fmt(s.total_input_tokens));
                $('#card-output-tokens').text(fmt(s.total_output_tokens));
                $('#card-event-count').text(fmt(s.event_count));
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
                + '<td class="pb-0">' + featureLabel(f.feature) + '</td>'
                + '<td class="pb-0 text-right font-weight-bold">' + fmt(f.total_credits) + ' credit</td>'
                + '</tr>'
                + '<tr><td colspan="2" class="text-muted small pt-0">'
                + fmt(f.event_count) + ' response &middot; ' + fmt(f.total_tokens) + ' token</td></tr>';
        });
        html += '</tbody></table>';
        $('#feature-breakdown').html(html);
    }

    function renderTrend(trend) {
        const labels = trend.map(function (t) { return t.date; });
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
                    borderColor: '#131c5b',
                    backgroundColor: 'rgba(19, 28, 91, 0.08)',
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
