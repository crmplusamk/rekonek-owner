@extends('template.admin.main')

@section('content')
<div class="row">
    <div class="col-12 mt-2">
        <div class="row">
            <div class="col-md-10"></div>
            <div class="col-md-2 text-right">
                <a href="javascript:void(0)" data-toggle="modal" data-target="#modalCreateAffiliator" class="btn btn-primary">
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
                        <input type="text" id="search" class="form-control" placeholder="Pencarian (nama / email)">
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
                <table class="table affiliator-table" style="width:100%">
                    <thead>
                        <tr>
                            <th data-orderable="false" class="td-checkbox"><input type="checkbox" id="checkAll"></th>
                            <th class="text-center">Nama</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Kode Promo</th>
                            <th data-orderable="false" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('settingaffiliator::modals._create')
@include('settingaffiliator::modals._edit')
@include('settingaffiliator::modals._config')
@endsection

@push('head')
<style>
.modal-dialog-top { margin-top: 1.75rem; }
</style>
@endpush

@push('script')
<script>
$(document).ready(function() {
    @if($errors->any() && (old('name') || old('email')))
    $('#modalCreateAffiliator').modal('show');
    @endif

    var affiliatorTable;
    var search = '';

    function initTable() {
        affiliatorTable = $('.affiliator-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('setting-affiliator.table') }}",
                dataType: 'json',
                data: function(d) {
                    d.search = search;
                }
            },
            columns: [
                { data: 'checkbox', orderable: false },
                { data: 'name' },
                { data: 'email' },
                { data: 'promo_code' },
                { data: 'action', orderable: false }
            ],
            columnDefs: [{ className: 'dt-center', targets: [0, 1, 2, 3, 4] }],
            dom: 'lrtip',
            order: [[1, 'asc']],
            length: 10,
            lengthChange: false
        });
    }

    initTable();

    $('#search').on('keyup', debounce(function() {
        search = this.value;
        affiliatorTable.ajax.reload();
    }, 500));

    $('#showCount').on('change', function() {
        affiliatorTable.page.len(parseInt(this.value, 10)).draw();
    });

    $(document).on('click', '.btn-config-affiliator', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var configUrl = "{{ url('setting-affiliator') }}/" + id + "/config";
        var updateUrl = "{{ url('setting-affiliator') }}/" + id + "/config";
        $('#modalConfigAffiliatorTitle').text('Konfigurasi Komisi - ' + name);
        $('#formConfigAffiliator').attr('action', updateUrl);
        $('#config_user_id').val(id);
        $.get(configUrl, function(data) {
            $('#config_commission_value_registrasi').val(data.commission_value_registrasi || '');
            $('#config_commission_value_perpanjangan').val(data.commission_value_perpanjangan || '');
        });
        $('#modalConfigAffiliator').modal('show');
    });

    $(document).on('click', '.btn-edit-affiliator', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        var email = $(this).data('email');
        var url = "{{ url('setting-affiliator') }}/" + id;
        $('#formEditAffiliator').attr('action', url);
        $('#edit_affiliator_name').val(name);
        $('#edit_affiliator_email').val(email);
        $('#modalEditAffiliator').modal('show');
    });

    $('#checkAll').on('change', function() {
        $('.check-item').prop('checked', this.checked);
    });
});
</script>
@endpush
