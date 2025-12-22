@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('referral.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail Referral Code: {{ $referral->code }}
        </h4>
    </div>
</div>

<div class="row">
    <div class="col-12 mt-4">
        <!-- Info Card -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Penggunaan</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalUsage }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-success">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Diskon Diberikan</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalDiscount, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-info">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Pembelian</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">Rp {{ number_format($totalPurchase, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-left-warning">
                    <div class="card-body">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Sisa Kuota</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $remainingQuota === 'Unlimited' ? 'Unlimited' : number_format($remainingQuota, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Info -->
        <div class="p-4 border-1 mb-4">
            <h5 class="mb-4">Informasi Referral Code</h5>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Kode</strong></td>
                            <td>: {{ $referral->code }}</td>
                        </tr>
                        <tr>
                            <td><strong>Nama</strong></td>
                            <td>: {{ $referral->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tipe Diskon</strong></td>
                            <td>: {{ ucfirst($referral->discount_type) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Diskon</strong></td>
                            <td>: 
                                @if($referral->discount_type === 'percentage')
                                    {{ $referral->discount_percentage }}%
                                    @if($referral->max_discount)
                                        (Max: Rp {{ number_format($referral->max_discount, 0, ',', '.') }})
                                    @endif
                                @else
                                    Rp {{ number_format($referral->discount_amount, 0, ',', '.') }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Minimal Pembelian</strong></td>
                            <td>: {{ $referral->min_purchase ? 'Rp ' . number_format($referral->min_purchase, 0, ',', '.') : 'Tidak ada' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Batas Per User</strong></td>
                            <td>: {{ $referral->per_user_limit }}x</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Status</strong></td>
                            <td>: 
                                <span class="badge badge-{{ $referral->is_active ? 'success' : 'danger' }}">
                                    {{ $referral->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Batas Penggunaan</strong></td>
                            <td>: {{ $referral->usage_limit ? number_format($referral->usage_limit, 0, ',', '.') : 'Unlimited' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Digunakan</strong></td>
                            <td>: {{ number_format($referral->used_count, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Persentase Penggunaan</strong></td>
                            <td>: 
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar {{ $usagePercentage > 80 ? 'bg-danger' : ($usagePercentage > 50 ? 'bg-warning' : 'bg-success') }}" 
                                         role="progressbar" 
                                         style="width: {{ $usagePercentage }}%"
                                         aria-valuenow="{{ $usagePercentage }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        {{ $usagePercentage }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Mulai</strong></td>
                            <td>: {{ $referral->start_date ? $referral->start_date->format('d M Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Berakhir</strong></td>
                            <td>: {{ $referral->end_date ? $referral->end_date->format('d M Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Deskripsi</strong></td>
                            <td>: {{ $referral->description ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Usage History -->
        <div class="p-4 border-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">Riwayat Penggunaan</h5>
                <a href="{{ route('referral.edit', $referral->id) }}" class="btn btn-warning btn-sm">
                    <i class="mdi mdi-pencil"></i> Edit
                </a>
            </div>

            @if($recentUsages->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Company ID</th>
                                <th>Contact ID</th>
                                <th>Jumlah Pembelian</th>
                                <th>Diskon</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentUsages as $usage)
                                <tr>
                                    <td>{{ $usage->created_at->format('d M Y H:i') }}</td>
                                    <td>{{ $usage->company_id ?? '-' }}</td>
                                    <td>{{ $usage->contact_id ?? '-' }}</td>
                                    <td>Rp {{ number_format($usage->purchase_amount ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-success">Rp {{ number_format($usage->discount_amount ?? 0, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($totalUsage > 10)
                    <div class="text-center mt-3">
                        <small class="text-muted">Menampilkan 10 penggunaan terakhir dari total {{ $totalUsage }} penggunaan</small>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="mdi mdi-information-outline mdi-48px text-muted"></i>
                    <p class="text-muted mt-3">Belum ada penggunaan untuk referral code ini</p>
                </div>
            @endif
        </div>

        <div class="mt-3 text-right">
            <a href="{{ route('referral.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .border-left-primary {
        border-left: 4px solid #2465FF !important;
    }
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .card {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
    }
</style>
@endpush

