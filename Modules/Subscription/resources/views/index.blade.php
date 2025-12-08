@extends('template.admin.main')

@section('content')

<div class="row">
    <div class="col-12 mt-2">
        <div class="row">
            <div class="col-md-10">
                <div class="btn-group btn-group-toggle btn-group-status" data-toggle="buttons">
                    <label for="status_active" class="btn btn-outline-primary active">
                        <input type="radio" name="filter_status" id="status_active" value="1">
                        Active (0)
                    </label>
                    <label for="status_inactive" class="btn btn-outline-primary">
                        <input type="radio" name="filter_status" id="status_inactive" value="0">
                        Inactive (0)
                    </label>
                </div>
            </div>

            <div class="col-md-2 text-right">
                <a href="{{ route('subscription.create') }}" class="btn btn-primary">
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
                <table class="table subscription-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th class="text-center">Customer</th>
                            <th class="text-center">Subscription Code</th>
                            <th class="text-center">Package</th>
                            <th class="text-center">Started at</th>
                            <th class="text-center">Expired at</th>
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
@endsection

@push('script')
<script>

    $(document).ready(function ()
    {
        subscriptionTable();
    });

    let subscriptiontable;
    let search = '';

    function subscriptionTable()
    {
        subscriptiontable = $('.subscription-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('subscription.table') }}",
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
                    data: "customer",
                    sortable: false,
                },
                {
                    data: "code",
                },
                {
                    data: "package",
                    sortable: false,
                },
                {
                    data: "started_at",
                    sortable: false,
                },
                {
                    data: "expired_at",
                    sortable: false,
                },
                {
                    data: "status",
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
                    targets: [0, 2, 3, 4, 5, 6]
                }
            ],
            order: [
                [1, 'asc']
            ],
            dom: 'lrtip',
            length: 10,
            lengthChange: false,
        });
    }

    $('#showCount').on('change', function() {
        subscriptiontable.page.len(this.value).draw();
    });
</script>
@endpush


