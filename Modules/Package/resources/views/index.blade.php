@extends('template.admin.main')
@section('content')
{{-- <div class="mt-2 mb-5">
    <div class="d-flex justify-content-end mb-5">
        <a href="" class="btn btn-primary text-right">
            <i class="mdi mdi-plus"></i> Tambah
        </a>
    </div>
    <div class="">
        <div class="">
            <table class="table table-lg table-hover table-borderless bg-white">
                <thead class="border-bottom">
                    <tr class="text-center">
                        <th scope="col" class="text-left">
                            <div class="mb-5">
                                <div class="h4 font-weight-bold">Package</div>
                            </div>
                        </th>
                        @foreach ($packages as $package)
                            <th scope="col" class="text-center text-nowrap">
                                <div class="h4 font-weight-bold mb-0">{{ $package->name }}</div>
                                <p class="font-weight-normal text-muted">{{ $package->price ? "Rp. ".number_format($package->price, 0, ',', '.'). " / " : '' }} {{ $package->duration . " " . $package->duration_type }}</p>
                                <button class="btn btn-white btn-sm mt-3">Detail</button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>

                    @foreach ($features as $i => $feature)

                        <tr class="border-bottom bg-light">
                            <th scope="row" class="h6 py-3 font-weight-semibold text-nowrap border-0 mb-0">{{ $feature->name }}</th>
                            <td class="py-3 border-0"></td>
                            <td class="py-3 border-0"></td>
                            <td class="py-3 border-0"></td>
                            <td class="py-3 border-0"></td>
                        </tr>

                        @foreach ($feature->childs as $j => $sub)
                            <tr>
                                <th class="font-weight-normal" scope="row">{{ $sub->name }}</th>
                                @foreach ($rules as $rule)
                                    <td class="text-center">
                                        @if(isset($rule["features"][$sub->id]) && $rule["features"][$sub->id]['pivot']['limit'])
                                            {{ $rule["features"][$sub->id]['pivot']['limit'] == -1 ? "Unlimited" : $rule["features"][$sub->id]['pivot']['limit'] }}

                                            @if ($rule["features"][$sub->id]['pivot']['limit_type'] == 'max')
                                                Maks data
                                            @elseif ($rule["features"][$sub->id]['pivot']['limit_type'] == 'day')
                                                Hari
                                            @elseif ($rule["features"][$sub->id]['pivot']['limit_type'] == 'time')
                                                X
                                            @elseif ($rule["features"][$sub->id]['pivot']['limit_type'] == 'month')
                                                Perbulan
                                            @endif
                                        @else
                                            <i class='mdi mdi-18px {{ isset($rule["features"][$sub->id]) && $rule["features"][$sub->id]['pivot']['included'] == true ? "mdi-checkbox-marked text-success" : "mdi-close-box text-danger" }}'></i>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div> --}}

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
                <a href="{{ route('package.create') }}" class="btn btn-primary">
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
                <table class="table package-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox">
                                <input type="checkbox" id="checkAll">
                            </th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Harga</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Dibuat pada</th>
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

    $(document).ready(function ()
    {
        handlePackageTable();
    });

    let packageTable;
    let search = '';

    function handlePackageTable()
    {
        packageTable = $('.package-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('package.table') }}",
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
                    data: "price",
                },
                {
                    data: "status",
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
        packageTable.ajax.reload();
    }, 500));

    $('#showCount').on('change', function() {
        packageTable.page.len(this.value).draw();
    });
</script>
@endpush
