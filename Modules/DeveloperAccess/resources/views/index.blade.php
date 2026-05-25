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
                    <div class="col-md-7 mb-2 text-right">
                        <div class="btn-group mr-2" role="group">
                            <button class="btn btn-outline-grey btn-sm dropdown-toggle" type="button" id="btnBulkAction" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" disabled>
                                Action
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item text-danger" href="javascript:void(0)" id="btnBulkDelete">
                                    <i class="mdi mdi-trash-can"></i> Hapus
                                </a>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createAccessModal">
                            <i class="mdi mdi-plus"></i> Buat Akses Developer
                        </button>
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
                                    <input type="checkbox" class="check-item" name="ids[]" value="{{ $data->id }}">
                                </td>
                                <td> {{ $data->account_name }} </td>
                                <td> {{ $data->account_email }} </td>
                                <td> {{ str_replace('_', ' ', $data->time_access) }} </td>
                                <td> {{ substr($data->note ?? '', 0, 50) }} </td>
                                <td> {{ $data->company_name }} </td>
                                @php
                                    $targetDate = \Carbon\Carbon::parse($data->end_date);
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
                                        <a class="dropdown-item text-danger pointer" data-toggle="modal" data-target="#destroy-{{ $data->id }}">
                                            <i class="mdi mdi-delete"></i> Hapus
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

@foreach ($access as $data)
    <div class="modal fade" id="destroy-{{ $data->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-top" role="document">
            <div class="modal-content">
                <div class="modal-body modal-body-lg text-center text-wrap text-break">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                    <div class="text-center mt-4">
                        <h5>Konfirmasi Hapus Akses</h5>
                        <p class="mt-2 text-lg">Hapus akses developer untuk <strong>{{ $data->account_name }}</strong> ({{ $data->account_email }})?</p>
                        <p class="text-danger">Token tidak bisa dipakai lagi setelah dihapus.</p>
                    </div>
                    <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                        <a data-dismiss="modal" class="btn btn-light mr-3">Batal</a>
                        <form action="{{ route('developer-access.destroy') }}" method="post" class="d-inline">
                            @csrf
                            <input type="hidden" name="ids[]" value="{{ $data->id }}">
                            <button type="submit" class="btn btn-danger">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<!-- Create Access Modal -->
<div class="modal fade" id="createAccessModal" tabindex="-1" role="dialog" aria-labelledby="createAccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAccessModalLabel">Buat Akses Developer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createAccessForm" method="POST" action="{{ route('developer-access.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="user_id" class="mb-1">Pilih User <span class="text-danger">*</span></label>
                        <select class="form-control" id="user_id" name="user_id" required style="width: 100%;">
                            <option value="">-- Pilih User --</option>
                            @foreach ($crmUsers as $u)
                                @php
                                    $label = ($u->name ?? '-') . ' (' . ($u->email ?? '-') . ')';
                                    if (!empty($u->company_name)) {
                                        $label .= ' — ' . $u->company_name;
                                    }
                                    if (!empty($u->is_superadmin)) {
                                        $label .= ' [superadmin]';
                                    }
                                    if (isset($u->is_active) && ! $u->is_active) {
                                        $label .= ' [nonaktif]';
                                    }
                                @endphp
                                <option
                                    value="{{ $u->id }}"
                                    data-company-id="{{ $u->company_id }}"
                                    data-company-name="{{ e($u->company_name ?? '') }}"
                                >{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Daftar user dari database CRM (koneksi <code>client</code>), di-render saat halaman ini dimuat.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="time_access" class="mb-1">Durasi Akses <span class="text-danger">*</span></label>
                        <select class="form-control" id="time_access" name="time_access" required>
                            <option value="1_day">1 Hari</option>
                            <option value="3_days">3 Hari</option>
                            <option value="7_days" selected>7 Hari</option>
                            <option value="14_days">14 Hari</option>
                            <option value="30_days">30 Hari</option>
                            <option value="90_days">90 Hari</option>
                            <option value="forever">Selamanya</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label for="note" class="mb-1">Catatan</label>
                        <textarea class="form-control" id="note" name="note" rows="3" placeholder="Contoh: Akses untuk development fitur baru"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="mdi mdi-check"></i> Buat Akses
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="bulkDeleteForm" action="{{ route('developer-access.destroy') }}" method="post" class="d-none">
    @csrf
</form>
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
            stateSave: false,
            language: {
                emptyTable: 'Belum ada data akses developer'
            }
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

        function toggleBulkActionButton() {
            $('#btnBulkAction').prop('disabled', $('.check-item:checked').length === 0);
        }

        $('#checkAll').on('change', function () {
            $('.check-item').prop('checked', this.checked);
            toggleBulkActionButton();
        });

        $(document).on('change', '.check-item', function () {
            toggleBulkActionButton();
            $('#checkAll').prop('checked', $('.check-item').length > 0 && $('.check-item:checked').length === $('.check-item').length);
        });

        $('#btnBulkDelete').on('click', function () {
            var ids = $('.check-item:checked').map(function () { return $(this).val(); }).get();
            if (ids.length === 0) {
                return;
            }
            if (!confirm('Hapus ' + ids.length + ' akses developer yang dipilih? Token tidak bisa dipakai lagi.')) {
                return;
            }
            var $form = $('#bulkDeleteForm');
            $form.find('input[name="ids[]"]').remove();
            ids.forEach(function (id) {
                $form.append($('<input>', { type: 'hidden', name: 'ids[]', value: id }));
            });
            $form.trigger('submit');
        });

        var $userSelect = $('#user_id');
        var $accessModal = $('#createAccessModal');

        function destroyUserSelect2() {
            if ($userSelect.hasClass('select2-hidden-accessible')) {
                $userSelect.select2('destroy');
            }
        }

        function initUserSelect2() {
            if ($userSelect.hasClass('select2-hidden-accessible')) {
                $userSelect.select2('destroy');
            }
            $userSelect.select2({
                placeholder: '-- Pilih User --',
                allowClear: true,
                width: '100%',
                dropdownParent: $accessModal
            });
        }

        // Select2 di modal: opsi user sudah di-render dari server (Blade); init saat modal dibuka agar dropdownParent benar
        $accessModal.on('show.bs.modal', function () {
            initUserSelect2();
        });

        $accessModal.on('hidden.bs.modal', function () {
            destroyUserSelect2();
        });

        // Submit form biasa (POST + CSRF): notifikasi lewat Laravel notify() setelah redirect
        $('#createAccessForm').on('submit', function () {
            if (!$userSelect.val()) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('error', 'Silakan pilih user terlebih dahulu');
                } else {
                    alert('Silakan pilih user terlebih dahulu');
                }
                return false;
            }
            return true;
        });
    })
</script>
@endpush


