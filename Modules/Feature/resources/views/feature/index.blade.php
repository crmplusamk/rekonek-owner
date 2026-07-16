@extends('template.admin.main')

@section('content')

<div class="row">
    <div class="col-12 mt-2">
        <div class="row">
            <div class="col-md-12 text-right">
                <a data-toggle="modal" data-target="#addNewFeature" class="btn btn-primary pointer">
                    <i class="mdi mdi-plus"></i> Tambah
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4 pt-1">
        <div class="border-1">
            <div class="p-3 border-bottom">
                <div class="row flex-column-reverse flex-md-row">
                    <div class="col-md-3 mb-2">
                        <input type="text" id="search" class="form-control" placeholder="Pencarian">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control select-2" id="showCount">
                            <option value="10" selected>Tampil 10 data</option>
                            <option value="25">Tampil 25 data</option>
                            <option value="50">Tampil 50 data</option>
                            <option value="100">Tampil 100 data</option>
                        </select>
                    </div>
                    <div class="col-md-7 mb-2"></div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table feature-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th class="text-center">Nama Fitur</th>
                            <th class="text-center">Kode</th>
                            <th class="text-center">Kategori</th>
                            <th class="text-center">Addon</th>
                            <th class="text-center">Dibuat pada</th>
                            <th data-orderable="false"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addNewFeature" aria-labelledby="addNewFeature" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Fitur</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></a>
            </div>
            <div class="modal-body">
                <form action="{{ route('feature.store') }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label>Kategori <span class="text-danger">*</span></label>
                        <select name="parent" class="form-control select-category" style="width: 100%" required>
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Fitur <span class="text-danger">*</span></label>
                        <input name="name" type="text" class="form-control" placeholder="Masukan nama fitur" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group mt-4">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input setting_is_addon" id="is_addon">
                            <label class="custom-control-label" for="is_addon">Aktifkan Addon</label>
                            <input type="hidden" name="is_addon" value="off">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>

    $(document).ready(function () {
        getCategory();
        handleFeatureTable();
    });

    let featuretable;
    let search = '';

    function handleFeatureTable() {
        featuretable = $('.feature-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('feature.table') }}",
                dataType: 'json',
                data: function (d) {
                    d.search = search;
                },
            },
            columns: [
                { data: 'checkbox', sortable: false },
                { data: 'name' },
                { data: 'key' },
                { data: 'category' },
                { data: 'addon' },
                { data: 'created_at', sortable: false },
                { data: 'action', sortable: false },
            ],
            columnDefs: [
                { className: 'dt-center', targets: [0, 3, 4] }
            ],
            dom: 'lrtip',
            order: [[1, 'asc']],
            length: 10,
            lengthChange: false,
        });
    }

    $('#search').on('keyup', debounce(function () {
        search = this.value;
        featuretable.ajax.reload();
    }, 500));

    $('#showCount').on('change', function () {
        featuretable.page.len(this.value).draw();
    });

    function getCategory() {
        $('.select-category').select2({
            tags: false,
            placeholder: "Pilih kategori",
            ajax: {
                type: "get",
                url: "{{ route('feature.category.list') }}",
                dataType: 'json',
                delay: 500,
                data: function (params) {
                    return { search: params.term };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data.data, function (item) {
                            return { text: item.name, id: item.id };
                        })
                    };
                },
            },
            language: {
                noResults: function () {
                    return "Tidak ada data";
                },
            },
        });
    }

    // Sinkronkan hidden input is_addon dengan toggle (create & edit modal).
    $('body').on('change', '.setting_is_addon', function () {
        let checked = $(this).is(':checked');
        $(this).closest('.custom-switch').find('input[name="is_addon"]').val(checked ? 'on' : 'off');
    });
</script>
@endpush
