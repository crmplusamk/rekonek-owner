@extends('template.admin.main')

@section('content')

<div class="row">
    <div class="col-12 mt-2">
        <div class="row">
            <div class="col-md-12 text-right">
                <a data-toggle="modal" data-target="#addNewFeatureCategory" class="btn btn-primary pointer">
                    <i class="mdi mdi-plus"></i> Tambah
                </a>
            </div>
            <div class="modal fade" id="addNewFeatureCategory" aria-labelledby="addNewFeatureCategory" aria-hidden="true">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Kategori</h5>
                            <a href="#" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </a>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('feature.category.store') }}" method="post">
                                @csrf
                                @method('post')
                                <div class="form-group">
                                    <label>Nama Kategori <span class="text-danger">*</span></label>
                                    <input name="name" type="text" class="form-control" data-validator="required" data-validator-label="Nama Kategori" placeholder="Masukan nama kategori">
                                    <div class="form-control-feedback"></div>
                                </div>
                                <div class="row mt-4">
                                    <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
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
                    <div class="col-md-4 mb-2"></div>
                    <div class="col-md-3 mb-2  text-right">
                        <div class="btn-group" role="group">
                            <div class="btn-group" role="group">
                                <button class="btn btn-outline-grey btn-sm dropdown-toggle col-sm-12 col-md-12" type="button" id="btnAction" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" disabled>
                                    Action
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="javascript:void(0)">
                                        <i class="mdi mdi-close"></i> &nbsp;Deactivate
                                    </a>
                                    <hr class="m-0">
                                    <a class="dropdown-item" href="javascript:void(0)">
                                        <i class="mdi mdi-trash-can"></i> &nbsp;Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table feature-category-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th class="text-center">Nama Kategori</th>
                            <th class="text-center">Jumlah Fitur</th>
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
@endsection

@push('script')
<script>

    $(document).ready(function ()
    {
        handleCategoryTable();
    });

    let kategorytable;
    let search = '';

    function handleCategoryTable()
    {
        kategorytable = $('.feature-category-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('feature.category.table') }}",
                dataType: 'json',
                data: function(d) {
                    d.search = search;
                },
            },
            columns: [
                {
                    data: "checkbox",
                    sortable: false,
                },
                {
                    data: "name",
                },
                {
                    data: "features_count",
                },
                {
                    data: "created_at",
                    sortable: false,
                },
                {
                    data: "action",
                    sortable: false,
                },
            ],
            columnDefs: [
                {
                    className: 'dt-center',
                    targets: [0, 2, 3, 4]
                }
            ],
            dom: 'lrtip',
            order: [
                [1, 'asc']
            ],
            length: 10,
            lengthChange: false,
        });
    }

    $('#search').on('keyup', debounce(function() {
        search = this.value;
        kategorytable.ajax.reload();
    }, 500));

    $('#showCount').on('change', function() {
        kategorytable.page.len(this.value).draw();
    });
</script>
@endpush


