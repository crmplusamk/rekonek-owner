@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('promo-code.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Tambah Promo Code
        </h4>
        <p class="text-muted mb-4"><small>Buat promo code baru untuk meningkatkan transaksi dan loyalitas pengguna.</small></p>
    </div>
</div>

<form action="{{ route('promo-code.store') }}" method="post" autocomplete="off">
    @csrf
    @method('post')

    @if ($errors->any())
        <div class="alert alert-danger mb-4 alert-dismissible fade show" role="alert">
            <strong>Terjadi Kesalahan!</strong> Harap periksa kembali inputan Anda.
            <ul class="mb-0 mt-2 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <h3 class="element-header mt-3">Informasi Dasar</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 form-group">
                            <label for="code" class="font-weight-bold">Kode Promo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light text-muted"><i class="mdi mdi-ticket-percent"></i></span>
                                </div>
                                <input type="text" class="form-control text-uppercase font-weight-bold @error('code') is-invalid @enderror" name="code" id="code" value="{{ old('code') }}" placeholder="CONTOH: PROMO2024" required maxlength="50" style="letter-spacing: 1px;">
                            </div>
                            @error('code')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                            <small class="form-text text-muted">Kode unik yang akan dimasukkan pengguna (Otomatis kapital).</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="type" class="font-weight-bold">Tipe Promo</label>
                            <select class="form-control select-2 @error('type') is-invalid @enderror" name="type" id="type">
                                <option value="">- Pilih Tipe -</option>
                                <option value="affiliator" {{ old('type') == 'affiliator' ? 'selected' : '' }}>Affiliator</option>
                                <option value="non_affiliator" {{ old('type') == 'non_affiliator' ? 'selected' : '' }}>Non Affiliator</option>
                            </select>
                            @error('type')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="name" class="font-weight-bold">Nama Kampanye</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" placeholder="Contoh: Promo Pengguna Baru">
                            @error('name')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-12 form-group">
                            <label for="description" class="font-weight-bold">Deskripsi Promo</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="3" placeholder="Jelaskan detail promo ini...">{{ old('description') }}</textarea>
                            @error('description')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
            </div>

            @include('promocode::partials._affiliator_block', ['affiliatorUsers' => $affiliatorUsers])

            <h3 class="element-header mt-3">Konfigurasi Nilai</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <ul class="nav nav-tabs nav-tabs-card mb-3" id="configValueTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-registrasi" data-toggle="tab" href="#content-registrasi" role="tab">Registrasi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-perpanjangan" data-toggle="tab" href="#content-perpanjangan" role="tab">Perpanjangan</a>
                        </li>
                    </ul>
                    <div class="tab-content" id="configValueTabContent">
                        {{-- Tab Registrasi --}}
                        <div class="tab-pane fade show active" id="content-registrasi" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="font-weight-bold">Tipe Diskon <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        <div class="btn-group w-100" role="group">
                                            <label class="btn {{ old('discount_type_registrasi') == 'percentage' || !old('discount_type_registrasi') ? 'btn-primary' : 'btn-outline-primary' }}" id="label_percent_registrasi" style="cursor: pointer;">
                                                <input type="radio" name="discount_type_registrasi" value="percentage" {{ old('discount_type_registrasi') == 'percentage' || !old('discount_type_registrasi') ? 'checked' : '' }} style="display:none;"> Persentase (%)
                                            </label>
                                            <label class="btn {{ old('discount_type_registrasi') == 'nominal' ? 'btn-primary' : 'btn-outline-primary' }}" id="label_nominal_registrasi" style="cursor: pointer;">
                                                <input type="radio" name="discount_type_registrasi" value="nominal" {{ old('discount_type_registrasi') == 'nominal' ? 'checked' : '' }} style="display:none;"> Nominal (Rp)
                                            </label>
                                        </div>
                                        @error('discount_type_registrasi')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" id="discount_percentage_field_registrasi">
                                        <label class="font-weight-bold text-primary">Besar Persentase (%) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control text-right font-weight-bold @error('discount_percentage_registrasi') is-invalid @enderror" name="discount_percentage_registrasi" value="{{ old('discount_percentage_registrasi') }}" min="0" max="100" placeholder="0">
                                            <div class="input-group-append"><span class="input-group-text bg-primary text-white font-weight-bold">%</span></div>
                                        </div>
                                        @error('discount_percentage_registrasi')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group d-none" id="discount_amount_field_registrasi">
                                        <label class="font-weight-bold text-success">Besar Potongan (Rp) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                                            <input type="number" class="form-control font-weight-bold @error('discount_amount_registrasi') is-invalid @enderror" name="discount_amount_registrasi" value="{{ old('discount_amount_registrasi') }}" min="0" placeholder="0">
                                        </div>
                                        @error('discount_amount_registrasi')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row border-top pt-3 mt-2">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Minimal Transaksi (Opsional)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text text-muted">Rp</span></div>
                                        <input type="number" class="form-control @error('min_purchase_registrasi') is-invalid @enderror" name="min_purchase_registrasi" value="{{ old('min_purchase_registrasi') }}" min="0" placeholder="0">
                                    </div>
                                    @error('min_purchase_registrasi')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-md-6 form-group" id="max_discount_container_registrasi">
                                    <label class="font-weight-bold">Maksimal Diskon (Opsional)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text text-muted">Rp</span></div>
                                        <input type="number" class="form-control @error('max_discount_registrasi') is-invalid @enderror" name="max_discount_registrasi" value="{{ old('max_discount_registrasi') }}" min="0" placeholder="0">
                                    </div>
                                    @error('max_discount_registrasi')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                </div>
                            </div>
                        </div>
                        {{-- Tab Perpanjangan --}}
                        <div class="tab-pane fade" id="content-perpanjangan" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="font-weight-bold">Tipe Diskon <span class="text-danger">*</span></label>
                                    <div class="form-group">
                                        <div class="btn-group w-100" role="group">
                                            <label class="btn {{ old('discount_type_perpanjangan') == 'percentage' || !old('discount_type_perpanjangan') ? 'btn-primary' : 'btn-outline-primary' }}" id="label_percent_perpanjangan" style="cursor: pointer;">
                                                <input type="radio" name="discount_type_perpanjangan" value="percentage" {{ old('discount_type_perpanjangan') == 'percentage' || !old('discount_type_perpanjangan') ? 'checked' : '' }} style="display:none;"> Persentase (%)
                                            </label>
                                            <label class="btn {{ old('discount_type_perpanjangan') == 'nominal' ? 'btn-primary' : 'btn-outline-primary' }}" id="label_nominal_perpanjangan" style="cursor: pointer;">
                                                <input type="radio" name="discount_type_perpanjangan" value="nominal" {{ old('discount_type_perpanjangan') == 'nominal' ? 'checked' : '' }} style="display:none;"> Nominal (Rp)
                                            </label>
                                        </div>
                                        @error('discount_type_perpanjangan')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group" id="discount_percentage_field_perpanjangan">
                                        <label class="font-weight-bold text-primary">Besar Persentase (%) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control text-right font-weight-bold @error('discount_percentage_perpanjangan') is-invalid @enderror" name="discount_percentage_perpanjangan" value="{{ old('discount_percentage_perpanjangan') }}" min="0" max="100" placeholder="0">
                                            <div class="input-group-append"><span class="input-group-text bg-primary text-white font-weight-bold">%</span></div>
                                        </div>
                                        @error('discount_percentage_perpanjangan')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                    </div>
                                    <div class="form-group d-none" id="discount_amount_field_perpanjangan">
                                        <label class="font-weight-bold text-success">Besar Potongan (Rp) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                                            <input type="number" class="form-control font-weight-bold @error('discount_amount_perpanjangan') is-invalid @enderror" name="discount_amount_perpanjangan" value="{{ old('discount_amount_perpanjangan') }}" min="0" placeholder="0">
                                        </div>
                                        @error('discount_amount_perpanjangan')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row border-top pt-3 mt-2">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Minimal Transaksi (Opsional)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text text-muted">Rp</span></div>
                                        <input type="number" class="form-control @error('min_purchase_perpanjangan') is-invalid @enderror" name="min_purchase_perpanjangan" value="{{ old('min_purchase_perpanjangan') }}" min="0" placeholder="0">
                                    </div>
                                    @error('min_purchase_perpanjangan')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                </div>
                                <div class="col-md-6 form-group" id="max_discount_container_perpanjangan">
                                    <label class="font-weight-bold">Maksimal Diskon (Opsional)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text text-muted">Rp</span></div>
                                        <input type="number" class="form-control @error('max_discount_perpanjangan') is-invalid @enderror" name="max_discount_perpanjangan" value="{{ old('max_discount_perpanjangan') }}" min="0" placeholder="0">
                                    </div>
                                    @error('max_discount_perpanjangan')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="element-header mt-3">Periode & Batasan</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="start_date" class="font-weight-bold">Tanggal Mulai</label>
                            <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" name="start_date" id="start_date" value="{{ old('start_date') }}">
                            @error('start_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="end_date" class="font-weight-bold">Tanggal Berakhir</label>
                            <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" name="end_date" id="end_date" value="{{ old('end_date') }}">
                            <small class="text-muted">Kosongkan jika berlaku selamanya.</small>
                            @error('end_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="border-top my-3"></div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="usage_limit" class="font-weight-bold">Kuota Total (Global)</label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('usage_limit') is-invalid @enderror" name="usage_limit" id="usage_limit" value="{{ old('usage_limit') }}" min="0" placeholder="Unlimited">
                                <div class="input-group-append"><span class="input-group-text">x Pakai</span></div>
                            </div>
                            @error('usage_limit')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6 form-group mb-0">
                            <label for="per_user_limit" class="font-weight-bold">Batas Per User</label>
                            <div class="input-group">
                                <input type="number" class="form-control @error('per_user_limit') is-invalid @enderror" name="per_user_limit" id="per_user_limit" value="{{ old('per_user_limit', 1) }}" min="0" placeholder="1">
                                <div class="input-group-append"><span class="input-group-text">x Pakai</span></div>
                            </div>
                            @error('per_user_limit')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <h3 class="element-header mt-3">Publikasi</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="form-group mb-0">
                        <label class="font-weight-bold mb-2">Status Promo</label>
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                            <span class="text-muted">Aktifkan Kode?</span>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active"></label>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-6">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold">Simpan</button>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('promo-code.index') }}" class="btn btn-outline-secondary btn-block">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('script')
<script>
$(document).ready(function() {
    $('#code').on('input', function() { $(this).val($(this).val().toUpperCase()); });

    function toggleAffiliatorBlock() {
        var isAffiliator = ($('#type').val() === 'affiliator');
        if (isAffiliator) {
            $('#affiliator-block').removeClass('d-none');
            $('#affiliator_user_id').prop('required', true);
        } else {
            $('#affiliator-block').addClass('d-none');
            $('#affiliator_user_id').prop('required', false).val('');
        }
    }
    $('#type').on('change', toggleAffiliatorBlock);
    toggleAffiliatorBlock();

    function handleDiscountType(prefix, type) {
        var p = prefix;
        if (type === 'percentage') {
            $('#discount_percentage_field_' + p).removeClass('d-none');
            $('#discount_amount_field_' + p).addClass('d-none');
            $('#max_discount_container_' + p).removeClass('d-none');
            $('input[name="discount_percentage_' + p + '"]').prop('required', true);
            $('input[name="discount_amount_' + p + '"]').prop('required', false);
            $('#label_percent_' + p).removeClass('btn-outline-primary').addClass('btn-primary');
            $('#label_nominal_' + p).removeClass('btn-primary').addClass('btn-outline-primary');
        } else {
            $('#discount_percentage_field_' + p).addClass('d-none');
            $('#discount_amount_field_' + p).removeClass('d-none');
            $('#max_discount_container_' + p).addClass('d-none');
            $('input[name="discount_percentage_' + p + '"]').prop('required', false);
            $('input[name="discount_amount_' + p + '"]').prop('required', true);
            $('#label_percent_' + p).removeClass('btn-primary').addClass('btn-outline-primary');
            $('#label_nominal_' + p).removeClass('btn-outline-primary').addClass('btn-primary');
        }
    }

    $('input[name="discount_type_registrasi"]').on('change', function() { handleDiscountType('registrasi', $(this).val()); });
    $('input[name="discount_type_perpanjangan"]').on('change', function() { handleDiscountType('perpanjangan', $(this).val()); });

    var typeReg = $('input[name="discount_type_registrasi"]:checked').val();
    var typePer = $('input[name="discount_type_perpanjangan"]:checked').val();
    handleDiscountType('registrasi', typeReg || 'percentage');
    handleDiscountType('perpanjangan', typePer || 'percentage');
});
</script>
@endpush
