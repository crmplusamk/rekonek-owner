@extends('template.admin.main')

@section('content')
@include('tabs.setting')

<div class="row">
    <div class="col-md-12">
        <div class="btn-group btn-group-toggle btn-group-status" data-toggle="buttons">
            <label for="status_active" class="btn btn-outline-primary active">
                <input type="radio" name="filter_status" id="status_active" value="1">
                Active ({{ $count_active }})
            </label>
            <label for="status_inactive" class="btn btn-outline-primary">
                <input type="radio" name="filter_status" id="status_inactive" value="0">
                Inactive ({{ $count_inactive }})
            </label>
        </div>
    </div>
</div>

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
                <table class="table access-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Waktu Akses</th>
                            <th>Catatan</th>
                            <th>Company</th>
                            <th class="text-center">Status</th>
                            <th data-orderable="false"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($access as $data)
                            <tr>
                                <td>
                                    <input type="checkbox" name="id[]" value="{{ $data->id }}">
                                </td>
                                <td> {{ $data->account_name }} </td>
                                <td> {{ $data->account_email }} </td>
                                <td> {{ str_replace('_', ' ', $data->time_access) }} </td>
                                <td> {{ substr($data->note, 0, 50) }} </td>
                                <td> {{ $data->company_name }} </td>
                                @php
                                    $targetDate = \Carbon\Carbon::parse($data->end_date);
                                    $currentDate = \Carbon\Carbon::now();
                                @endphp
                                <td class="text-center {{ !$targetDate->isPast() ? "text-success" : "text-danger" }}">
                                    <i class="mdi mdi-check-circle"></i>
                                    {{ !$targetDate->isPast() ? "Active" : "Inactive" }}
                                </td>
                                <td class="actions text-center">
                                    <a href="#" data-toggle="dropdown" class="text-muted element-restriction" aria-expanded="false">
                                        <i class="mdi mdi-dots-horizontal mdi-24px"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right" role="menu" x-placement="bottom-end" style="position: absolute; transform: translate3d(-144px, 18px, 0px); top: 0px; left: 0px; will-change: transform;">
                                        <a class="dropdown-item edit" target="_blank" href="{{ env("CRM_CLIENT_HOST").'/login-developer?token='.$data->token_access }}">
                                            <i class="mdi mdi-share"></i> Open Akses
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    $(() =>
    {
        var table = $('.access-table').DataTable({
            lengthChange: false,
            destroy: true,
            columnDefs: [{
                orderable: false,
                targets: 0
            }],
            order: [
                [1, 'asc']
            ],
            heightMatch: 'none',
            dom: 'lrtip',
            length: 10,
            stateSave: true
        });

        $('#search').on('keyup', function()
        {
            table.search(this.value).draw();
        });

        $('#showCount').on('change', function()
        {
            table.page.len(this.value).draw();
        });

        $('input[name=filter_status]').on('change', function()
        {
            var val = this.value == '1' ? 'Active' : 'Inactive';
            table.column(6).search(val, true, false, false).draw();
        });

        table.column(6).search('Active', true, false, false).draw();
    })
</script>
@endpush


