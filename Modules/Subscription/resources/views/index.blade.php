@extends('template.admin.main')

@section('content')

<div class="row">
    <div class="col-12 mt-2">
        <div class="row">
            <div class="col-md-12">
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
                <table class="table subscription-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th class="text-center">Customer</th>
                            <th class="text-center">Kode</th>
                            <th class="text-center">Package</th>
                            <th class="text-center">Started</th>
                            <th class="text-center">Expired</th>
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

    $(document).ready(function () {
        subscriptionTableInit();
    });

    let subscriptionTable;
    let search = '';
    let filterStatus = '';

    function subscriptionTableInit() {
        subscriptionTable = $('.subscription-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('subscription.table') }}",
                dataType: 'json',
                data: function (d) {
                    d.search = search;
                    d.status = filterStatus;
                },
            },
            columns: [
                { data: 'checkbox', sortable: false },
                { data: 'customer', sortable: false },
                { data: 'code' },
                { data: 'package', sortable: false },
                { data: 'started_at', sortable: false },
                { data: 'expired_at', sortable: false },
                { data: 'status', sortable: false },
                { data: 'action', sortable: false },
            ],
            columnDefs: [
                { className: 'dt-center', targets: [0, 2, 3, 4, 5, 6] }
            ],
            dom: 'lrtip',
            order: [],
            length: 10,
            lengthChange: false,
        });
    }

    $('#search').on('keyup', debounce(function () {
        search = this.value;
        subscriptionTable.ajax.reload();
    }, 500));

    $('input[name="filter_status"]').on('change', function () {
        filterStatus = this.value;
        subscriptionTable.ajax.reload();
    });

    $('#showCount').on('change', function () {
        subscriptionTable.page.len(this.value).draw();
    });
</script>
@endpush
