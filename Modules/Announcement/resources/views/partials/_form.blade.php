<form action="{{ $formAction }}" method="post" autocomplete="off">
    @csrf
    @method($method)

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

    @php
        $targetMode = old('target_mode', $announcement && $announcement->targets->isNotEmpty() ? 'company' : 'global');
        $companyOptions = old('company_ids', $selectedCompanies->pluck('id')->all());
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <h3 class="element-header mt-3">Konten Banner</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 form-group">
                            <label for="title" class="font-weight-bold">Judul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" name="title" id="title" value="{{ old('title', optional($announcement)->title) }}" required>
                            @error('title')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-12 form-group">
                            <label for="message" class="font-weight-bold">Pesan <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('message') is-invalid @enderror" name="message" id="message" rows="4" required>{{ old('message', optional($announcement)->message) }}</textarea>
                            @error('message')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="type" class="font-weight-bold">Tipe Banner <span class="text-danger">*</span></label>
                            <select class="form-control select-2 @error('type') is-invalid @enderror" name="type" id="type" required>
                                <option value="info" {{ old('type', optional($announcement)->type) === 'info' ? 'selected' : '' }}>Info</option>
                                <option value="warning" {{ old('type', optional($announcement)->type) === 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="success" {{ old('type', optional($announcement)->type) === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="danger" {{ old('type', optional($announcement)->type) === 'danger' ? 'selected' : '' }}>Danger</option>
                            </select>
                            @error('type')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="action_label" class="font-weight-bold">Label Tombol</label>
                            <input type="text" class="form-control @error('action_label') is-invalid @enderror" name="action_label" id="action_label" value="{{ old('action_label', optional($announcement)->action_label) }}" placeholder="Contoh: Lihat Detail">
                            @error('action_label')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="action_url" class="font-weight-bold">URL Tombol</label>
                            <input type="url" class="form-control @error('action_url') is-invalid @enderror" name="action_url" id="action_url" value="{{ old('action_url', optional($announcement)->action_url) }}" placeholder="https://...">
                            @error('action_url')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="element-header mt-3">Target</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 form-group">
                            <label class="font-weight-bold d-block mb-3">Target <span class="text-danger">*</span></label>
                            <div class="d-flex flex-wrap align-items-center">
                                <div class="custom-control custom-radio mr-4 mb-2">
                                    <input type="radio" id="target_global" name="target_mode" value="global" class="custom-control-input" {{ $targetMode === 'global' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="target_global">Global</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="target_company" name="target_mode" value="company" class="custom-control-input" {{ $targetMode === 'company' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="target_company">Per Company</label>
                                </div>
                            </div>
                            @error('target_mode')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                        </div>

                        <div class="col-12 form-group {{ $targetMode === 'company' ? '' : 'd-none' }}" id="company-target-container">
                            <label class="font-weight-bold d-block mb-2">Pilih Company <span class="text-danger">*</span></label>
                            <div class="w-100">
                                <select class="form-control select-company @error('company_ids') is-invalid @enderror" name="company_ids[]" id="company_ids" multiple style="width: 100%;"></select>
                            </div>
                            @error('company_ids')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                            @error('company_ids.*')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                            <small class="text-muted d-block mt-2">Gunakan pencarian untuk memilih satu atau lebih company target.</small>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="element-header mt-3">Penjadwalan</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="start_at" class="font-weight-bold">Mulai Tayang <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control @error('start_at') is-invalid @enderror" name="start_at" id="start_at" value="{{ old('start_at', optional(optional($announcement)->start_at)->format('Y-m-d\TH:i')) }}" required>
                            @error('start_at')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="end_at" class="font-weight-bold">Selesai Tayang</label>
                            <input type="datetime-local" class="form-control @error('end_at') is-invalid @enderror" name="end_at" id="end_at" value="{{ old('end_at', optional(optional($announcement)->end_at)->format('Y-m-d\TH:i')) }}">
                            <small class="text-muted">Kosongkan jika ingin tampil tanpa batas akhir.</small>
                            @error('end_at')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label for="priority" class="font-weight-bold">Prioritas</label>
                            <input type="number" class="form-control @error('priority') is-invalid @enderror" name="priority" id="priority" min="0" value="{{ old('priority', optional($announcement)->priority ?? 0) }}">
                            <small class="text-muted">Semakin besar nilainya, semakin diprioritaskan.</small>
                            @error('priority')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <h3 class="element-header mt-3">Publikasi</h3>
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    @if ($announcement)
                        <div class="border rounded p-3">
                            <small class="text-muted d-block">Status saat ini</small>
                            <span class="badge badge-pill badge-secondary text-uppercase mt-2">{{ $announcement->status }}</span>
                        </div>
                    @endif

                    <div class="alert alert-light border {{ $announcement ? 'mt-3' : '' }} mb-0">
                        Bila jadwal atau target bentrok dengan pengumuman aktif/terjadwal lain, sistem akan otomatis menyimpan pengumuman sebagai <strong>draft</strong>.
                    </div>

                    <div class="row mt-4">
                        <div class="col-6">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold">Simpan</button>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('announcement.index') }}" class="btn btn-outline-secondary btn-block">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('script')
<script>
    const selectedCompanies = @json(collect($selectedCompanies)->values());

    $(document).ready(function() {
        initializeCompanySelect();
        toggleCompanyTarget();
        $('input[name="target_mode"]').on('change', toggleCompanyTarget);
    });

    function initializeCompanySelect() {
        const $companySelect = $('.select-company');

        $companySelect.select2({
            ajax: {
                method: 'get',
                url: "{{ route('announcement.companies') }}",
                dataType: 'json',
                data: function(params) {
                    return {
                        search: params.term,
                    };
                },
                processResults: function(data) {
                    const formattedData = (data.data || []).map(function(item) {
                        return {
                            id: item.company_id,
                            text: item.name,
                        };
                    });

                    return {
                        results: formattedData
                    };
                },
                cache: true,
            },
            placeholder: 'Pilih company',
            minimumInputLength: 2,
            allowClear: true,
            multiple: true,
            width: '100%',
        });

        selectedCompanies.forEach(function(item) {
            if (!$companySelect.find("option[value='" + item.id + "']").length) {
                const option = new Option(item.text, item.id, true, true);
                $companySelect.append(option);
            }
        });

        $companySelect.trigger('change');
    }

    function toggleCompanyTarget() {
        const isCompanyTarget = $('input[name="target_mode"]:checked').val() === 'company';
        $('#company-target-container').toggleClass('d-none', !isCompanyTarget);
    }
</script>
@endpush
