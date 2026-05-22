@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12 mt-2">
        <div class="row">
            <div class="col-md-9 mb-2">
                <h4 class="pt-2 mb-1">Pengumuman Banner</h4>
                <p class="text-muted mb-0"><small>Kelola banner pengumuman global dan per company untuk ditampilkan di aplikasi Rekonek.</small></p>
            </div>
            <div class="col-md-3 text-right">
                <a href="{{ route('announcement.create') }}" class="btn btn-primary">
                    <i class="mdi mdi-plus"></i> Tambah
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 mt-4 pt-1">
        <div class="border-1">
            <div class="p-3 border-bottom">
                <div class="row flex-column-reverse flex-md-row">
                    <div class="col-md-4 mb-2">
                        <input type="text" id="search" class="form-control" placeholder="Cari judul atau pesan">
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
                <table class="table announcement-table" style="width:100%">
                    <thead>
                        <tr>
                            <th class="text-center">Judul</th>
                            <th class="text-center">Tipe</th>
                            <th class="text-center">Target</th>
                            <th class="text-center">Jadwal</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
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
    $(document).ready(function() {
        handleAnnouncementTable();
    });

    let announcementTable;
    let search = '';

    function handleAnnouncementTable() {
        announcementTable = $('.announcement-table').DataTable({
            paging: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('announcement.table') }}",
                dataType: 'json',
                data: function(d) {
                    d.search = search;
                },
            },
            columns: [
                { data: "title" },
                { data: "type_badge", sortable: false },
                { data: "target_summary", sortable: false },
                { data: "schedule", sortable: false },
                { data: "status_badge", sortable: false },
                { data: "action", sortable: false },
            ],
            columnDefs: [{ className: 'dt-center', targets: [1, 2, 3, 4, 5] }],
            dom: 'lrtip',
            order: [[0, 'asc']],
            length: 10,
            lengthChange: false,
        });
    }

    $('#search').on('keyup', debounce(function() {
        search = this.value;
        announcementTable.ajax.reload();
    }, 500));

    $('#showCount').on('change', function() {
        announcementTable.page.len(this.value).draw();
    });
</script>
@endpush
