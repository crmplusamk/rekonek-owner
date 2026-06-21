@extends('template.admin.main')

@section('content')
<div class="row">
    <div class="col-12 mt-2">
        <div class="d-flex justify-content-between align-items-end flex-wrap">
            <div class="mb-2">
                <h4 class="mb-0">Laporan AI Credit</h4>
                <small class="text-muted">Pemakaian token &amp; credit AI di seluruh organisasi</small>
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
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1">Total Credit Terpakai</p>
                    <h3 class="mb-0" id="card-total-credits">0</h3>
                </div></div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1">Total Token</p>
                    <h3 class="mb-0" id="card-total-tokens">0</h3>
                    <small class="text-muted">
                        Input: <span id="card-input-tokens">0</span> &middot; Output: <span id="card-output-tokens">0</span>
                    </small>
                </div></div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1">Total Response AI</p>
                    <h3 class="mb-0" id="card-event-count">0</h3>
                </div></div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100"><div class="card-body">
                    <p class="text-muted mb-1">Organisasi Aktif</p>
                    <h3 class="mb-0" id="card-active-companies">0</h3>
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

    {{-- Per-company breakdown --}}
    <div class="col-12 mt-1">
        <div class="border-1">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-2 mb-md-0">Per Organisasi</h5>
                <div style="min-width: 240px;">
                    <input type="text" id="search" class="form-control" placeholder="Cari organisasi">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table ai-usage-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Organisasi</th>
                            <th class="text-right">Response</th>
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

    let aiUsageTable;
    let trendChart = null;
    let search = '';
    let currentStart = moment().subtract(29, 'days').format('YYYY-MM-DD');
    let currentEnd = moment().format('YYYY-MM-DD');

    const FEATURE_LABELS = {
        chat_auto_reply: 'Auto-Reply Chat',
        agent_testing_sandbox: 'Live Testing',
    };

    const SHOW_URL_TEMPLATE = "{{ route('ai-credit-usage.show', ['company' => '__CID__']) }}";

    function companyLink(data, type, row) {
        if (type !== 'display') {
            return data;
        }
        const url = SHOW_URL_TEMPLATE.replace('__CID__', encodeURIComponent(row.company_id));
        const a = document.createElement('a');
        a.href = url;
        a.textContent = data;
        return a.outerHTML;
    }

    function fmt(n) {
        return Number(n || 0).toLocaleString('id-ID');
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
        aiUsageTable = $('.ai-usage-table').DataTable({
            destroy: true,
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('ai-credit-usage.table') }}",
                dataType: 'json',
                data: function (d) {
                    d.search = search;
                    d.start_date = currentStart;
                    d.end_date = currentEnd;
                },
            },
            columns: [
                { data: 'company_name', render: companyLink },
                { data: 'event_count', className: 'text-right', render: fmt },
                { data: 'input_tokens', className: 'text-right', render: fmt },
                { data: 'output_tokens', className: 'text-right', render: fmt },
                { data: 'total_tokens', className: 'text-right', render: fmt },
                { data: 'credits_used', className: 'text-right', render: fmt },
            ],
            order: [[5, 'desc']],
            dom: 'lrtip',
            length: 10,
            lengthChange: false,
        });
    }

    $('#search').on('keyup', debounce(function () {
        search = this.value;
        aiUsageTable.ajax.reload();
    }, 500));

    function refresh() {
        loadSummary();
        if (aiUsageTable) {
            aiUsageTable.ajax.reload();
        }
    }

    function loadSummary() {
        $.ajax({
            url: "{{ route('ai-credit-usage.summary') }}",
            data: { start_date: currentStart, end_date: currentEnd },
            dataType: 'json',
            success: function (res) {
                const s = res.summary || {};
                $('#card-total-credits').text(fmt(s.total_credits));
                $('#card-total-tokens').text(fmt(s.total_tokens));
                $('#card-input-tokens').text(fmt(s.total_input_tokens));
                $('#card-output-tokens').text(fmt(s.total_output_tokens));
                $('#card-event-count').text(fmt(s.event_count));
                $('#card-active-companies').text(fmt(s.active_companies));
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
            const label = FEATURE_LABELS[f.feature] || f.feature;
            html += '<tr>'
                + '<td class="pb-0">' + label + '</td>'
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
