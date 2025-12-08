@extends('template.admin.main')

@section('content')
@include('tabs.setting')

<div class="row">
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
                </div>
            </div>

            <div class="table-responsive">
                <table class="table otp-history-table" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-center">Pengirim</th>
                            <th class="text-center">Penerima</th>
                            <th class="text-center">Token</th>
                            <th class="text-center">Status</th>
                            <th data-orderable="false"></th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>

    let otptable;
    let search = '';

    $(document).ready(function () {
        otpTable();
    });

    function otpTable()
    {
        otpTable = $('.otp-history-table').DataTable({
            destroy: true,
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('otphistory.table') }}",
                dataType: 'json',
                data: function(d) {
                    d.search = search;
                },
            },
            columns: [
                {
                    data: "sender",
                    sortable: false,
                },
                {
                    data: "receiver",
                    sortable: false,
                },
                {
                    data: "token",
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
                    targets: [0, 1, 2, 3, 4]
                }
            ],
            dom: 'lrtip',
            length: 10,
            lengthChange: false,
        });
    }

    $('#search').on('keyup', debounce(function() {
        search = this.value;
        otpTable.ajax.reload();
    }, 500));

</script>
@endpush


