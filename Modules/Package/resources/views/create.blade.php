@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('package.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Tambah Package
        </h4>
    </div>
</div>

<form action="{{ route('package.store') }}" method="post">
    @csrf

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h6 class="mb-3 text-muted font-weight-bold">Informasi Package</h6>
            <div class="row">
                <div class="col-12 col-md-6 form-group">
                    <label for="name">Nama Package <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" value="{{ old('name') }}" placeholder="Masukan nama package">
                    @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="price">Harga <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" name="price" id="price" value="{{ old('price') }}" placeholder="Contoh: 150000">
                    @error('price') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="duration">Durasi <span class="text-danger">*</span></label>
                    <input type="number" class="form-control @error('duration') is-invalid @enderror" name="duration" id="duration" value="{{ old('duration') }}" placeholder="Contoh: 1">
                    @error('duration') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-12 col-md-6 form-group">
                    <label for="duration_type">Tipe Durasi <span class="text-danger">*</span></label>
                    <select class="form-control select-2 @error('duration_type') is-invalid @enderror" name="duration_type">
                        <option value="">Pilih salah satu</option>
                        <option value="month" {{ old('duration_type') == 'month' ? 'selected' : '' }}>Bulan</option>
                        <option value="day" {{ old('duration_type') == 'day' ? 'selected' : '' }}>Hari</option>
                    </select>
                    @error('duration_type') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-12 form-group">
                    <label for="description">Deskripsi</label>
                    <textarea class="form-control" name="description" id="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input setting_is_publish" id="is_publish" {{ old('is_publish') == 'on' ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_publish">Publish Package</label>
                        <input type="hidden" class="input_is_publish" name="is_publish" value="{{ old('is_publish', 'off') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h6 class="mb-3 text-muted font-weight-bold">Aturan Fitur</h6>
            @if ($features->isEmpty())
                <p class="text-muted text-center py-4 mb-0">Belum ada fitur. Tambahkan fitur terlebih dahulu.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 320px">Fitur</th>
                                <th>Pengaturan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($features as $i => $feature)
                                <tr class="bg-light">
                                    <th colspan="2" class="font-weight-bold">{{ $feature->name }}</th>
                                </tr>
                                @foreach ($feature->childs as $j => $sub)
                                    <tr class="row-data" data-id="{{ $sub->id }}">
                                        <th class="font-weight-normal" scope="row">{{ $sub->name }}</th>
                                        <td class="d-flex flex-wrap align-items-center p-2">
                                            <div class="fv-add mr-4">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input setting_visiblity" id="visiblity-{{ $j . '-' . $i }}">
                                                    <label class="custom-control-label" for="visiblity-{{ $j . '-' . $i }}">Visibility</label>
                                                    <input type="hidden" class="input_visiblity" name="visiblity[{{ $sub->id }}]" value="off">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="text-right mb-5">
        <a href="{{ route('package.index') }}" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </div>
</form>
@endsection

@push('script')
<script>

    $('body').on('change', '.setting_is_publish', function (e) {
        let field = $(this).closest('.setting_is_publish:checked').is(':checked');
        $('.input_is_publish').val(field ? 'on' : 'off');
    });

    $('body').on('change', '.setting_visiblity', function (e) {
        let field = $(this).closest('.setting_visiblity:checked').is(':checked');
        let row = $(this).closest('.row-data');
        let featureId = row.attr("data-id");
        let checkboxId = Math.random();

        if (!field) {
            row.find('.input_visiblity').val('off');
            row.find('.select_include').remove();
            row.find('.select_limitless').remove();
            row.find('.input_limit').remove();
            row.find('.select_limit_tipe').remove();
            return
        }

        row.find('.input_visiblity').val('on');

        $(this).closest('.fv-add').after(`
            <div class="fv-add mr-4 select_include">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input setting_include" id="include-${checkboxId}">
                    <label class="custom-control-label" for="include-${checkboxId}">Include</label>
                    <input type="hidden" class="input_include" name="include[${featureId}]" value="off">
                </div>
            </div>
        `);
    });

    $('body').on('change', '.setting_include', function (e) {
        let field = $(this).closest('.setting_include:checked').is(':checked');
        let row = $(this).closest('.row-data');
        let featureId = row.attr("data-id");

        if (!field) {
            row.find('.input_include').val('off');
            row.find('.select_limitless').remove();
            row.find('.input_limit').remove();
            row.find('.select_limit_tipe').remove();
            return
        }

        row.find('.input_include').val('on');

        $(this).closest('.fv-add').after(`
            <div class="mr-3 select_limitless">
                <div class="form-group mb-0">
                    <select class="form-control limitless" name="limit_option[${featureId}]" style="width: 200px" required>
                        <option value="" selected>Setting limit data</option>
                        <option value="none">Tidak diatur</option>
                        <option value="limited">Limit</option>
                        <option value="unlimited">Unlimited</option>
                    </select>
                </div>
            </div>
        `);

        $('.limitless').select2({ placeholder: "Setting limit data" });
    })

    $('body').on('change', '.limitless', function (e) {
        let field = $(this).val();
        let row = $(this).closest('.row-data');
        let featureId = row.attr("data-id");

        if (field == "unlimited" || field == "none") {
            row.find('.input_limit').remove();
            row.find('.select_limit_tipe').remove();
            return
        }

        $(this).closest('.select_limitless').after(`
            <div class="mr-3 input_limit">
                <div class="form-group mb-0">
                    <input type="number" name="limit[${featureId}]" class="form-control" style="width: 200px" placeholder="Masukan Limit data" min="1" required>
                </div>
            </div>
            <div class="mr-3 select_limit_tipe">
                <div class="form-group mb-0">
                    <select class="form-control limit_type" name="limit_type[${featureId}]" style="width: 200px" required>
                        <option value="" selected>Setting limit tipe</option>
                        <option value="max">Maksimal</option>
                        <option value="day">Perhari</option>
                        <option value="month">Perbulan</option>
                        <option value="time">Kali</option>
                    </select>
                </div>
            </div>
        `);

        $('.limit_type').select2({ placeholder: "Setting limit tipe" });
    })

</script>
@endpush
