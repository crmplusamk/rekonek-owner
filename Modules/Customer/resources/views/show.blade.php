@extends('template.admin.main')

@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('customer.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail Customer: {{ $data['customer']->name }}
        </h4>
    </div>
</div>

<div class="row">
    <!-- Main Content -->
    <div class="col-12 col-md-9 mt-4">
        
        {{-- Access Log Progress Pipeline --}}
        <div class="access-log-pipeline mb-4">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <h6 class="mb-3 text-muted" style="font-size: 0.875rem; font-weight: 600;">
                        <i class="mdi mdi-progress-check mr-2"></i>Progress Registrasi & Aktivasi
                    </h6>
                    <div class="d-flex justify-content-start flex-wrap">
                        @foreach ($data['access_log_stages'] as $stageKey => $stageName)
                        <div class="stage {{ in_array($stageKey, $data['completed_stages']) ? 'stage-active' : '' }} d-flex flex-grow-1 justify-content-center align-items-center m-1" 
                             style="min-height: 40px; font-size: 0.75rem; padding: 8px 12px; border: 1px solid #e3e6f0; border-radius: 4px; background: {{ in_array($stageKey, $data['completed_stages']) ? '#1cc88a' : '#f8f9fc' }}; color: {{ in_array($stageKey, $data['completed_stages']) ? '#fff' : '#5a5c69' }};">
                            <i class="mdi mdi-circle mr-2" style="font-size: 8px"></i>
                            <span class="text-center">{{ $stageName }}</span>
                        </div>
                        @endforeach
                    </div>
                    
                    {{-- Progress Summary removed per request --}}
                </div>
            </div>
        </div>
        {{-- End Access Log Progress Pipeline --}}

        {{-- Tabs Navigation --}}
        <ul class="nav nav-pills border-bottom mb-3" id="pills-tab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="stats-tab" data-toggle="pill" href="#stats-content" role="tab" aria-controls="stats-content" aria-selected="true">
                    Statistik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="invoice-tab" data-toggle="pill" href="#invoice-content" role="tab" aria-controls="invoice-content" aria-selected="false">
                    Invoice & Langganan
                </a>
            </li>
        </ul>

        {{-- Tabs Content --}}
        <div class="tab-content" id="pills-tabContent">
            
            {{-- Statistik Tab --}}
            <div class="tab-pane fade show active" id="stats-content" role="tabpanel" aria-labelledby="stats-tab">
                
                <!-- Statistics Cards Row 1 -->
                <div class="row mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Total Contacts</div>
                        <div class="h4 mb-3 font-weight-bold">{{ number_format($data['stats']['contacts_count']) }}</div>
                        <div class="text-xs text-muted">
                            @if($data['stats']['contacts_last_date'])
                                Terakhir: {{ \Carbon\Carbon::parse($data['stats']['contacts_last_date'])->format('d M Y, H:i') }}
                            @else
                                Tidak ada data
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Total Channel</div>
                        <div class="h4 mb-3 font-weight-bold">{{ number_format($data['stats']['total_channel_count']) }}</div>
                        <div class="text-xs text-muted">
                            @if($data['stats']['sessions_last_date'])
                                Terakhir: {{ \Carbon\Carbon::parse($data['stats']['sessions_last_date'])->format('d M Y, H:i') }}
                            @else
                                Tidak ada data
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Total Tasks</div>
                        <div class="h4 mb-3 font-weight-bold">{{ number_format($data['stats']['tasks_count']) }}</div>
                        <div class="text-xs text-muted">
                            @if($data['stats']['tasks_last_date'])
                                Terakhir: {{ \Carbon\Carbon::parse($data['stats']['tasks_last_date'])->format('d M Y, H:i') }}
                            @else
                                Tidak ada data
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Total Users</div>
                        <div class="h4 mb-3 font-weight-bold">{{ number_format($data['stats']['users_count']) }}</div>
                        <div class="text-xs text-muted">Total pengguna</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards Row 2 -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Total Conversations</div>
                        <div class="h4 mb-3 font-weight-bold">{{ number_format($data['stats']['conversations_count']) }}</div>
                        <div class="text-xs text-muted">
                            @if($data['stats']['conversations_last_date'])
                                Terakhir: {{ \Carbon\Carbon::parse($data['stats']['conversations_last_date'])->format('d M Y, H:i') }}
                            @else
                                Tidak ada data
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-muted text-uppercase mb-3">Total Revenue (IDR)</div>
                        <div class="h4 mb-3 font-weight-bold">Rp {{ number_format($data['stats']['total_revenue'], 0, ',', '.') }}</div>
                        <div class="text-xs text-muted">Total pendapatan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 30-Day Statistics Charts -->
        <div class="mb-4">
            <!-- Contacts Chart -->
            <div class="p-4 border-1 mb-4 shadow-sm">
                <h5 class="mb-4">30 Hari Terakhir - Contacts</h5>
                <canvas id="contactsChart"></canvas>
            </div>
            <!-- Conversations Chart -->
            <div class="p-4 border-1 mb-4 shadow-sm">
                <h5 class="mb-4">30 Hari Terakhir - Conversations</h5>
                <canvas id="conversationsChart"></canvas>
            </div>
            <!-- Tasks Chart -->
            <div class="p-4 border-1 mb-4 shadow-sm">
                <h5 class="mb-4">30 Hari Terakhir - Tasks</h5>
                <canvas id="tasksChart"></canvas>
            </div>
        </div>

            </div>{{-- End Statistik Tab --}}
        
            {{-- Invoice & Langganan Tab --}}
            <div class="tab-pane fade" id="invoice-content" role="tabpanel" aria-labelledby="invoice-tab">
                <div class="card border rounded-md shadow-sm">
                    <div class="card-body">
                        <div class="p-3 border-bottom">
                            <div class="row flex-column-reverse flex-md-row">
                                <div class="col-md-3 mb-2">
                                    <input type="text" id="search-invoice" class="form-control" placeholder="Pencarian">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <select class="form-control" id="showCountInvoice">
                                        <option value="10" selected>Tampil 10 data</option>
                                        <option value="25">Tampil 25 data</option>
                                        <option value="50">Tampil 50 data</option>
                                        <option value="-1">Semua Data</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="table-invoices">
                                <thead>
                                    <tr>
                                        <th>KODE TRANSAKSI</th>
                                        <th>PEMBELIAN</th>
                                        <th>TANGGAL</th>
                                        <th>TOTAL</th>
                                        <th class="text-center">STATUS</th>
                                        <th class="text-center">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['invoices'] as $invoice)
                                    <tr>
                                        <td class="fw-bold text-primary">{{ $invoice->code }}</td>
                                        <td>
                                            @if(count($invoice->items) > 0)
                                                {{ $invoice->items[0]->name }}
                                                @if(count($invoice->items) > 1)
                                                    <span class="badge badge-secondary">+{{ count($invoice->items) - 1 }}</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($invoice->date)->locale('id')->format('d M Y') }}</td>
                                        <td>Rp {{ number_format($invoice->total, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            @if($invoice->is_paid)
                                                <span class="badge badge-pill badge-success">Lunas</span>
                                            @elseif(\Carbon\Carbon::parse($invoice->due_date)->isPast())
                                                <span class="badge badge-pill badge-danger">Expired</span>
                                            @else
                                                <span class="badge badge-pill badge-warning">Belum Dibayar</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-default btn-circle btn-sm" type="button" id="dropdownMenuButton{{ $invoice->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <i class="mdi mdi-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $invoice->id }}">
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick="showInvoiceDetail('{{ $invoice->id }}')">
                                                        <i class="mdi mdi-eye mr-2"></i>Detail Invoice
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>{{-- End Invoice & Langganan Tab --}}
        
        </div>{{-- End Tab Content --}}

        <!-- Back Button -->
        <div class="mt-3 text-right">
            <a href="{{ route('customer.index') }}" class="btn btn-primary">Kembali</a>
        </div>
    </div>

    <!-- Right Sidebar: Customer & Company Information -->
    <div class="col-12 col-md-3 mt-4">
        <!-- Company Information Card -->
        <div class="p-4 border-1 shadow-sm mb-4">
            <h5 class="mb-4">Informasi Customer</h5>
            
            <div class="mb-4">
                <small class="text-muted d-block mb-1">Nama Customer</small>
                <strong class="d-block">{{ $data['customer']->name }}</strong>
            </div>
            
            <div class="mb-4">
                <small class="text-muted d-block mb-1">Email Customer</small>
                <strong class="d-block">{{ $data['customer']->email ?? 'N/A' }}</strong>
            </div>
            
            <div class="mb-4">
                <small class="text-muted d-block mb-1">Nomor Telepon</small>
                <strong class="d-block">{{ $data['customer']->phone ?? 'N/A' }}</strong>
            </div>

            <hr class="my-4">

            <h5 class="mb-4">Informasi Company</h5>
            
            <div class="mb-4">
                <small class="text-muted d-block mb-1">Company Name</small>
                <strong class="d-block">{{ $data['company']->name ?? 'N/A' }}</strong>
            </div>
            
            <div class="mb-4">
                <small class="text-muted d-block mb-1">Company Phone</small>
                <strong class="d-block">{{ $data['company']->phone ?? 'N/A' }}</strong>
            </div>
            
            <div class="mb-4">
                <small class="text-muted d-block mb-1">Company Address</small>
                <strong class="d-block text-break">{{ $data['company']->address ?? 'N/A' }}</strong>
            </div>
            
            <div class="mb-0">
                <small class="text-muted d-block mb-1">Subscription</small>
                <span class="badge badge-{{ ($data['company']->is_subscriber ?? false) ? 'primary' : 'secondary' }}">
                    {{ ($data['company']->is_subscriber ?? false) ? 'Subscribed' : 'Not Subscribed' }}
                </span>
            </div>
        </div>
        
        <!-- Recent Logins Card -->
        <div class="p-4 border-1 shadow-sm">
            <h5 class="mb-4">User Login</h5>
            @if(count($data['recent_logins']) > 0)
                <div class="list-group">
                    @foreach($data['recent_logins'] as $index => $login)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                    <strong>{{ $login->name }}</strong>
                            </div>
                            <div class="text-muted text-xs">
                                {{ \Carbon\Carbon::parse($login->last_login_at)->format('d M Y, H:i') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-muted">
                    <small>Tidak ada data login</small>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Invoice Detail Modal --}}
<div class="modal fade" id="invoiceDetailModal" tabindex="-1" role="dialog" aria-labelledby="invoiceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceDetailModalLabel">
                    Detail Invoice - <span id="modal-invoice-code-header" class="text-primary"></span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="invoice-detail-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="invoice-detail-content" style="display: none;">
                    <div class="row">
                        <!-- Main Content -->
                        <div class="col-md-9">
                            {{-- Customer Info & Status --}}
                            <div class="user-profile p-3 mb-3 border rounded">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="border-left border-primary pl-3" style="border-left-width: 4px !important;">
                                            <p class="text-muted mb-1">Customer</p>
                                            <h6 class="mb-1" id="modal-customer-name"></h6>
                                            <small class="text-muted">
                                                <span id="modal-customer-email"></span>
                                                <span> | </span>
                                                <span id="modal-customer-phone"></span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-center justify-content-end">
                                        <div id="modal-invoice-status"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Invoice Summary Table --}}
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <td class="text-center">
                                                <p class="font-weight-bold mb-1">Tanggal Invoice</p>
                                                <p class="mb-0" id="modal-invoice-date"></p>
                                            </td>
                                            <td class="text-center">
                                                <p class="font-weight-bold mb-1">Jatuh Tempo</p>
                                                <p class="mb-0" id="modal-invoice-due-date"></p>
                                            </td>
                                            <td class="text-center">
                                                <p class="font-weight-bold mb-1">Total Invoice</p>
                                                <p class="mb-0" id="modal-invoice-total"></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-center">
                                                <p class="font-weight-bold mb-1">Metode Pembayaran</p>
                                                <p class="mb-0" id="modal-payment-method"></p>
                                            </td>
                                            <td class="text-center">
                                                <p class="font-weight-bold mb-1">Tanggal Pembayaran</p>
                                                <p class="mb-0" id="modal-payment-date"></p>
                                            </td>
                                            <td class="text-center">
                                                <p class="font-weight-bold mb-1">Total Pembayaran</p>
                                                <p class="mb-0" id="modal-payment-total"></p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Customer Address --}}
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>Alamat</th>
                                        </tr>
                                        <tr>
                                            <td id="modal-customer-address" style="word-wrap: break-word;word-break: break-all;white-space: normal !important;">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Invoice Items --}}
                            <div class="mb-3">
                                <h6 class="font-weight-bold mb-3">Daftar Pesanan</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="max-width: 100px;">Produk</th>
                                                <th class="text-center">Jenis</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-center">Durasi</th>
                                                <th class="text-right">Harga</th>
                                            </tr>
                                        </thead>
                                        <tbody id="modal-invoice-items">
                                        </tbody>
                                        <tbody>
                                            <tr>
                                                <td class="text-right" colspan="4">Subtotal</td>
                                                <td class="text-right" id="modal-subtotal"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right" colspan="4">Diskon <span id="modal-discount-percentage"></span>%</td>
                                                <td class="text-right" id="modal-discount-amount"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right" colspan="4">Biaya Layanan</td>
                                                <td class="text-right" id="modal-service-fee"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right" colspan="4">Biaya Admin</td>
                                                <td class="text-right" id="modal-admin-fee"></td>
                                            </tr>
                                            <tr>
                                                <td class="text-right" colspan="4">PPN <span id="modal-tax-percentage"></span>%</td>
                                                <td class="text-right" id="modal-tax-amount"></td>
                                            </tr>
                                            <tr style="background-color: #2465FF !important;">
                                                <td class="text-right" colspan="4" style="background-color: #2465FF !important;">
                                                    <span class="h6 font-weight-bold text-white mb-0">TOTAL</span>
                                                </td>
                                                <td class="text-right" style="background-color: #2465FF !important;">
                                                    <span class="h6 font-weight-bold text-white mb-0" id="modal-total"></span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Sidebar - Logs -->
                        <div class="col-md-3 border-left" style="background-color: #f8f9fc;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="font-weight-bold mb-0 text-dark">Riwayat</h6>
                            </div>
                            <div id="modal-invoice-logs" style="max-height: 500px; overflow-y: auto;">
                                <div class="text-center text-muted py-3">
                                    <small>Tidak ada log</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/id.min.js"></script>
<script>
    moment.locale('id');
    
    // Invoice DataTable
    $(document).ready(function() {
        var invoiceTable = $('#table-invoices').DataTable({
            lengthChange: false,
            destroy: true,
            dom: 'lrtip',
            pageLength: 10,
            order: [[2, 'desc']]
        });

        $('#search-invoice').on('keyup', function() {
            invoiceTable.search(this.value).draw();
        });

        $('#showCountInvoice').on('change', function() {
            invoiceTable.page.len(this.value).draw();
        });
    });

    // Show Invoice Detail Modal
    function showInvoiceDetail(invoiceId) {
        $('#invoiceDetailModal').modal('show');
        $('#invoice-detail-loading').show();
        $('#invoice-detail-content').hide();

        const customerId = '{{ $data['customer']->id }}';

        // Fetch invoice detail
        $.ajax({
            url: '{{ route("customer.index") }}/' + customerId + '/invoice-detail/' + invoiceId,
            method: 'GET',
            success: function(response) {
                const invoice = response.data;
                
                // Populate header
                $('#modal-invoice-code-header').text(invoice.code);
                
                // Customer information
                $('#modal-customer-name').text(invoice.customer_name || '-');
                $('#modal-customer-email').text(invoice.customer_email || '-');
                $('#modal-customer-phone').text(invoice.customer_phone || '-');
                $('#modal-customer-address').text(invoice.customer_address || '-');
                
                // Invoice status
                let statusBadge = '';
                if (invoice.is_paid) {
                    statusBadge = '<span class="badge badge-pill badge-success" style="font-size: 14px;">Lunas</span>';
                } else if (moment(invoice.due_date).isBefore(moment())) {
                    statusBadge = '<span class="badge badge-pill badge-danger" style="font-size: 14px;">Expired</span>';
                } else {
                    statusBadge = '<span class="badge badge-pill badge-warning" style="font-size: 14px;">Belum Dibayar</span>';
                }
                $('#modal-invoice-status').html(statusBadge);
                
                // Invoice summary
                $('#modal-invoice-date').text(moment(invoice.date).locale('id').format('DD-MM-YYYY'));
                $('#modal-invoice-due-date').text(moment(invoice.due_date).locale('id').format('DD-MM-YYYY'));
                $('#modal-invoice-total').text('Rp ' + Number(invoice.total).toLocaleString('id-ID'));
                
                // Payment information
                $('#modal-payment-method').text(invoice.payment_method ? invoice.payment_method.replace(/_/g, ' ') : '-');
                $('#modal-payment-date').text(invoice.payment_date ? moment(invoice.payment_date).locale('id').format('DD-MM-YYYY') : '-');
                $('#modal-payment-total').text('Rp ' + Number(invoice.payment_total || 0).toLocaleString('id-ID'));
                
                // Invoice items
                let itemsHtml = '';
                $.each(invoice.items, function(index, item) {
                    // Determine item type
                    let itemType = '-';
                    if (item.itemable_type) {
                        if (item.itemable_type.includes('Package')) {
                            itemType = 'Paket';
                        } else if (item.itemable_type.includes('Addon')) {
                            itemType = 'Add On';
                        }
                    }
                    
                    // Format duration
                    let duration = '-';
                    if (item.duration && item.duration_type) {
                        const durationType = item.duration_type === 'month' ? 'bulan' : 'tahun';
                        duration = item.duration + ' ' + durationType;
                        if (item.additional_duration) {
                            duration += ' +' + item.additional_duration;
                        }
                    }
                    
                    itemsHtml += `
                        <tr>
                            <td style="word-wrap: break-word;word-break: break-all;white-space: normal !important;">${item.name}</td>
                            <td class="text-center">${itemType}</td>
                            <td class="text-center">${item.quantity || 0}</td>
                            <td class="text-center">${duration}</td>
                            <td class="text-right">Rp ${Number(item.subtotal).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                });
                $('#modal-invoice-items').html(itemsHtml);
                
                // Cost breakdown
                $('#modal-subtotal').text('Rp ' + Number(invoice.subtotal || 0).toLocaleString('id-ID'));
                $('#modal-discount-percentage').text(invoice.discount_percentage || 0);
                $('#modal-discount-amount').text('Rp ' + Number(invoice.discount_percentage_amount || 0).toLocaleString('id-ID'));
                $('#modal-service-fee').text('Rp ' + Number(invoice.service_fee || 0).toLocaleString('id-ID'));
                $('#modal-admin-fee').text('Rp ' + Number(invoice.admin_fee || 0).toLocaleString('id-ID'));
                $('#modal-tax-percentage').text(invoice.tax || 0);
                $('#modal-tax-amount').text('Rp ' + Number(invoice.tax_amount || 0).toLocaleString('id-ID'));
                $('#modal-total').text('Rp ' + Number(invoice.total).toLocaleString('id-ID'));
                
                // Logs
                if (invoice.logs && invoice.logs.length > 0) {
                    let logsHtml = '';
                    
                    // Group logs by date
                    const logsByDate = {};
                    $.each(invoice.logs, function(index, log) {
                        const dateKey = moment(log.created_at).format('YYYY-MM-DD');
                        const dateLabel = moment(log.created_at).locale('id').format('DD MMMM YYYY');
                        
                        if (!logsByDate[dateKey]) {
                            logsByDate[dateKey] = {
                                label: dateLabel,
                                logs: []
                            };
                        }
                        logsByDate[dateKey].logs.push(log);
                    });
                    
                    // Render logs grouped by date
                    $.each(logsByDate, function(dateKey, dateGroup) {
                        logsHtml += `
                            <p class="text-muted font-weight-bold mb-2 pt-3" style="font-size: 0.875rem;">
                                ${dateGroup.label}
                            </p>
                        `;
                        
                        $.each(dateGroup.logs, function(index, log) {
                            logsHtml += `
                                <div class="mb-3">
                                    <div class="d-flex align-items-start">
                                        <span class="badge badge-secondary badge-sm mr-2" style="font-size: 0.7rem;">
                                            ${moment(log.created_at).format('HH:mm')}
                                        </span>
                                        <div class="flex-grow-1">
                                            ${log.category ? '<span class="badge badge-info badge-sm mb-1">' + log.category + '</span>' : ''}
                                            <div class="font-weight-bold" style="font-size: 0.875rem;">${log.title || 'Log'}</div>
                                            <p class="mb-0 text-muted" style="font-size: 0.8rem; word-wrap: break-word;">${log.note || '-'}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                    });
                    
                    $('#modal-invoice-logs').html(logsHtml);
                } else {
                    $('#modal-invoice-logs').html('<div class="text-center text-muted py-3"><small>Tidak ada log</small></div>');
                }
                
                $('#invoice-detail-loading').hide();
                $('#invoice-detail-content').show();
            },
            error: function(xhr) {
                $('#invoice-detail-loading').hide();
                alert('Gagal memuat data invoice');
            }
        });
    }

    // Prepare data for charts
    const dailyStatsData = @json($data['daily_stats']);
    
    // Extract labels (dates)
    const labels = dailyStatsData.map(stat => {
        const date = new Date(stat.date);
        return date.toLocaleDateString('id-ID', { month: 'short', day: 'numeric' });
    });
    
    // Contacts Chart (Line - Total, Leads, Customers)
    const contactsCtx = document.getElementById('contactsChart').getContext('2d');
    const contactsChart = new Chart(contactsCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Customers',
                    data: dailyStatsData.map(stat => stat.contacts.customers),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Leads',
                    data: dailyStatsData.map(stat => stat.contacts.leads),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Total: ' + dailyStatsData.reduce((sum, stat) => sum + stat.contacts.total, 0),
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
    
    // Conversations Chart (Line - Total, Open, Closed)
    const conversationsCtx = document.getElementById('conversationsChart').getContext('2d');
    const conversationsChart = new Chart(conversationsCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Closed',
                    data: dailyStatsData.map(stat => stat.conversations.closed),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Open',
                    data: dailyStatsData.map(stat => stat.conversations.open),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Total: ' + dailyStatsData.reduce((sum, stat) => sum + stat.conversations.total, 0),
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
    
    // Tasks Chart (Line - Total, Finished, Not Started)
    const tasksCtx = document.getElementById('tasksChart').getContext('2d');
    const tasksChart = new Chart(tasksCtx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Finished',
                    data: dailyStatsData.map(stat => stat.tasks.finished),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Not Started',
                    data: dailyStatsData.map(stat => stat.tasks.not_started),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: true,
                    text: 'Total: ' + dailyStatsData.reduce((sum, stat) => sum + stat.tasks.total, 0),
                    padding: {
                        top: 10,
                        bottom: 20
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    });
</script>
@endpush

@push('style')
<style>
    .border-left-primary {
        border-left: 4px solid #2465FF !important;
    }
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .border-left-secondary {
        border-left: 4px solid #6c757d !important;
    }
    .border-left-dark {
        border-left: 4px solid #343a40 !important;
    }
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
    }
    
    /* Tab styling */
    .nav-pills .nav-link {
        border-radius: 0.25rem;
        color: #5a5c69;
        padding: 0.75rem 1.5rem;
    }
    .nav-pills .nav-link.active {
        background-color: #2465FF;
        color: white;
    }
    .nav-pills .nav-link:hover {
        background-color: #f8f9fc;
    }
    .nav-pills .nav-link.active:hover {
        background-color: #1a4dc7;
    }
    
    /* Modal styling */
    .modal-xl {
        max-width: 1200px;
    }
    #invoice-detail-content .table td {
        padding: 0.5rem;
    }
    #invoice-detail-content .table-borderless td {
        border: none;
    }
    .timeline {
        position: relative;
    }
    .user-profile {
        background-color: #f8f9fc;
    }
    
    /* Dropdown menu */
    .dropdown-menu {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        border: 1px solid #e3e6f0;
    }
    .dropdown-item {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    .dropdown-item:hover {
        background-color: #f8f9fc;
    }
</style>
@endpush