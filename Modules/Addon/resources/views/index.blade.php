@extends('template.admin.main')

@section('content')

<div class="row">
    <div class="col-12 mt-2">
        <div class="row">
            <div class="col-md-10">
                <div class="btn-group btn-group-toggle btn-group-status" data-toggle="buttons">
                    <label class="btn btn-outline-primary active">
                        <input type="radio" name="filter_status" value="" checked> Semua
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="filter_status" value="1"> Active
                    </label>
                    <label class="btn btn-outline-primary">
                        <input type="radio" name="filter_status" value="0"> Inactive
                    </label>
                </div>
            </div>

            <div class="col-md-2 text-right">
                <a data-toggle="modal" data-target="#addAddon" class="btn btn-primary pointer">
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
                <table class="table addon-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Fitur</th>
                            <th class="text-center">Charge</th>
                            <th class="text-center">Harga</th>
                            <th class="text-center">Status</th>
                            <th data-orderable="false"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addAddon" aria-labelledby="addAddon" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Addon</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></a>
            </div>
            <div class="modal-body">
                <form action="{{ route('addon.store') }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label>Fitur <span class="text-danger">*</span></label>
                        <select name="feature" class="form-control select-2" style="width: 100%" required>
                            <option value="">Pilih fitur</option>
                            @foreach ($availableFeature as $feature)
                                <option value="{{ $feature->id }}">{{ $feature->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Addon <span class="text-danger">*</span></label>
                        <input name="name" type="text" class="form-control" placeholder="Masukan nama addon" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Charge <span class="text-danger">*</span></label>
                        <input name="charge" type="number" class="form-control" value="{{ old('charge') }}" required>
                        <small class="form-text text-muted">Jumlah unit yang ditambahkan ke limit per pembelian</small>
                    </div>
                    <div class="form-group">
                        <label>Harga <span class="text-danger">*</span></label>
                        <input name="price" type="number" class="form-control" placeholder="Masukan harga addon" value="{{ old('price') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
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
        addonTableInit();
    });

    let addonTable;
    let search = '';
    let filterStatus = '';

    function addonTableInit() {
        addonTable = $('.addon-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('addon.table') }}",
                dataType: 'json',
                data: function (d) {
                    d.search = search;
                    d.status = filterStatus;
                },
            },
            columns: [
                { data: 'checkbox', sortable: false },
                { data: 'name' },
                { data: 'feature' },
                { data: 'charge' },
                { data: 'price' },
                { data: 'is_active', sortable: false },
                { data: 'action', sortable: false },
            ],
            columnDefs: [
                { className: 'dt-center', targets: [0, 2, 3, 4, 5] }
            ],
            dom: 'lrtip',
            order: [[1, 'asc']],
            length: 10,
            lengthChange: false,
        });
    }

    $('#search').on('keyup', debounce(function () {
        search = this.value;
        addonTable.ajax.reload();
    }, 500));

    $('input[name="filter_status"]').on('change', function () {
        filterStatus = this.value;
        addonTable.ajax.reload();
    });

    $('#showCount').on('change', function () {
        addonTable.page.len(this.value).draw();
    });
</script>
@endpush
