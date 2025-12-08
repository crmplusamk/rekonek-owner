<a href="#" data-toggle="dropdown" class="text-muted" aria-expanded="false">
    <i class="mdi mdi-dots-horizontal mdi-24px"></i>
</a>
<div class="dropdown-menu dropdown-menu-right" role="menu" x-placement="bottom-end" style="position: absolute; transform: translate3d(-144px, 18px, 0px); top: 0px; left: 0px; will-change: transform;">
    <a href="{{ route('package.edit', $package->id) }}" class="dropdown-item pointer">
        <i class="mdi mdi-pencil"></i> Edit
    </a>
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#status-{{ $package->id }}">
        <i class="mdi mdi-{{ $package->is_active ? "close" : "check" }}"></i> {{ $package->is_active ? "Nonaktifkan" : "Aktifkan" }}
    </a>
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#destroy-{{ $package->id }}">
        <i class="mdi mdi-delete"></i>
        Hapus
    </a>
</div>

<div class="modal fade" id="status-{{ $package->id }}" aria-labelledby="status" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body modal-body-lg text-center text-wrap text-break">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                <div class="text-center mt-4">
                    <h5>Konfirmasi {{ $package->is_active ? "Nonaktifkan" : "Aktifkan" }} Data</h5>
                    <p class="mt-2 text-lg">Apakah anda yakin ingin {{ $package->is_active ? "menonaktifkan" : "mengaktifkan" }} package {{ $package->name }} ? </p>
                    <p class="text-danger">Proses ini tidak dapat dibatalkan</p>
                </div>
                <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                    <a data-dismiss="modal" class="btn btn-danger mr-3">
                        Batal
                    </a>
                    <a href="{{ route('package.status', $package->id) }}" class="btn btn-primary mr-3">
                        {{ $package->is_active ? "Nonaktifkan" : "Aktifkan" }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="destroy-{{ $package->id }}" aria-labelledby="destroy" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body modal-body-lg text-center text-wrap text-break">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                <div class="text-center mt-4">
                    <h5>Konfirmasi Hapus Data</h5>
                    <p class="mt-2 text-lg">Apakah anda yakin ingin menghapus package {{ $package->name }} ? </p>
                    <p class="text-danger">Proses ini tidak dapat dibatalkan</p>
                </div>
                <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                    <a data-dismiss="modal" class="btn btn-danger mr-3">
                        Batal
                    </a>
                    <form action="{{ route('package.destroy', $package->id) }}" method="post">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-primary">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


