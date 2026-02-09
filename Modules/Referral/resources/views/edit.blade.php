@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('referral.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Edit Referral Code
        </h4>
    </div>
</div>

<div class="row">
    <div class="col-12 mt-4">
        <div class="p-4 border-1">
            <form action="{{ route('referral.update', $referral->id) }}" method="post">
                @if ($errors->any())
                    <div class="alert alert-danger mb-3">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-12 col-md-6 form-group">
                        <label for="code">Kode Referral <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" name="code" id="code" value="{{ old('code', $referral->code) }}" placeholder="Contoh: WELCOME10" required maxlength="50">
                        @error('code')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="form-text text-muted">Kode akan otomatis diubah menjadi huruf besar</small>
                    </div>
                    <div class="col-12 col-md-6 form-group">
                        <label for="name">Nama Referral</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name', $referral->name) }}" placeholder="Contoh: Welcome Referral Code">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 col-md-6 form-group">
                        <label for="discount_type">Tipe Diskon <span class="text-danger">*</span></label>
                        <select class="form-control @error('discount_type') is-invalid @enderror" name="discount_type" id="discount_type" required>
                            <option value="">Pilih Tipe Diskon</option>
                            <option value="percentage" {{ old('discount_type', $referral->discount_type) == 'percentage' ? 'selected' : '' }}>Persentase (%)</option>
                            <option value="nominal" {{ old('discount_type', $referral->discount_type) == 'nominal' ? 'selected' : '' }}>Nominal (Rp)</option>
                        </select>
                        @error('discount_type')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 col-md-6 form-group" id="discount_percentage_field">
                        <label for="discount_percentage">Diskon Persentase (%)</label>
                        <input type="number" class="form-control @error('discount_percentage') is-invalid @enderror" name="discount_percentage" id="discount_percentage" value="{{ old('discount_percentage', $referral->discount_percentage) }}" min="0" max="100" placeholder="Contoh: 10">
                        @error('discount_percentage')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 col-md-6 form-group d-none" id="discount_amount_field">
                        <label for="discount_amount">Diskon Nominal (Rp)</label>
                        <input type="number" class="form-control @error('discount_amount') is-invalid @enderror" name="discount_amount" id="discount_amount" value="{{ old('discount_amount', $referral->discount_amount) }}" min="0" placeholder="Contoh: 50000">
                        @error('discount_amount')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 col-md-6 form-group">
                        <label for="min_purchase">Minimal Pembelian (Rp)</label>
                        <input type="number" class="form-control @error('min_purchase') is-invalid @enderror" name="min_purchase" id="min_purchase" value="{{ old('min_purchase', $referral->min_purchase) }}" min="0" placeholder="0">
                        @error('min_purchase')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 col-md-6 form-group" id="max_discount_field">
                        <label for="max_discount">Maksimal Diskon (Rp)</label>
                        <input type="number" class="form-control @error('max_discount') is-invalid @enderror" name="max_discount" id="max_discount" value="{{ old('max_discount', $referral->max_discount) }}" min="0" placeholder="Contoh: 100000">
                        @error('max_discount')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="form-text text-muted">Hanya untuk tipe persentase</small>
                    </div>
                    
                    <div class="col-12 col-md-6 form-group">
                        <label for="usage_limit">Batas Penggunaan</label>
                        <input type="number" class="form-control @error('usage_limit') is-invalid @enderror" name="usage_limit" id="usage_limit" value="{{ old('usage_limit', $referral->usage_limit) }}" min="0" placeholder="Kosongkan untuk unlimited">
                        @error('usage_limit')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="form-text text-muted">Total penggunaan maksimal (kosongkan untuk unlimited)</small>
                    </div>
                    
                    <div class="col-12 col-md-6 form-group">
                        <label for="per_user_limit">Batas Per User/Company</label>
                        <input type="number" class="form-control @error('per_user_limit') is-invalid @enderror" name="per_user_limit" id="per_user_limit" value="{{ old('per_user_limit', $referral->per_user_limit) }}" min="0" placeholder="1">
                        @error('per_user_limit')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        <small class="form-text text-muted">Berapa kali user/company bisa menggunakan kode ini</small>
                    </div>
                    
                    <div class="col-12 col-md-6 form-group">
                        <label for="start_date">Tanggal Mulai</label>
                        <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" name="start_date" id="start_date" value="{{ old('start_date', $referral->start_date ? $referral->start_date->format('Y-m-d\TH:i') : '') }}">
                        @error('start_date')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 col-md-6 form-group">
                        <label for="end_date">Tanggal Berakhir</label>
                        <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" name="end_date" id="end_date" value="{{ old('end_date', $referral->end_date ? $referral->end_date->format('Y-m-d\TH:i') : '') }}">
                        @error('end_date')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 form-group">
                        <label for="description">Deskripsi</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="3" placeholder="Deskripsi referral code">{{ old('description', $referral->description) }}</textarea>
                        @error('description')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    
                    <div class="col-12 form-group mt-2">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $referral->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Aktif</label>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3 text-right">
                    <a href="{{ route('referral.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $('#discount_type').on('change', function() {
        const type = $(this).val();
        if (type === 'percentage') {
            $('#discount_percentage_field').removeClass('d-none');
            $('#discount_amount_field').addClass('d-none');
            $('#max_discount_field').removeClass('d-none');
            $('#discount_percentage').prop('required', true);
            $('#discount_amount').prop('required', false);
        } else if (type === 'nominal') {
            $('#discount_percentage_field').addClass('d-none');
            $('#discount_amount_field').removeClass('d-none');
            $('#max_discount_field').addClass('d-none');
            $('#discount_percentage').prop('required', false);
            $('#discount_amount').prop('required', true);
        } else {
            $('#discount_percentage_field').addClass('d-none');
            $('#discount_amount_field').addClass('d-none');
            $('#max_discount_field').addClass('d-none');
        }
    });

    // Trigger on load
    $('#discount_type').trigger('change');
</script>
@endpush

