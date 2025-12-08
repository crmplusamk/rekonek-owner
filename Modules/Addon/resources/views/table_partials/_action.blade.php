<a href="#" data-toggle="dropdown" class="text-muted" aria-expanded="false">
    <i class="mdi mdi-dots-horizontal mdi-24px"></i>
</a>
<div class="dropdown-menu dropdown-menu-right" role="menu" x-placement="bottom-end" style="position: absolute; transform: translate3d(-144px, 18px, 0px); top: 0px; left: 0px; will-change: transform;">
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#edit-{{ $addon->id }}">
        <i class="mdi mdi-pencil"></i> Edit
    </a>
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#is-active-{{ $addon->id }}">
        <i class="mdi mdi-{{ $addon->is_active ? "close" : "check" }}"></i> {{ $addon->is_active ? "Nonaktifkan" : "Aktifkan" }}
    </a>
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#destroy-{{ $addon->id }}">
        <i class="mdi mdi-delete"></i>
        Hapus
    </a>
</div>

<div class="modal fade" id="is-active-{{ $addon->id }}" aria-labelledby="is-active" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body modal-body-lg text-center text-wrap text-break">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                <div class="text-center mt-4">
                    <h5>Konfirmasi {{ $addon->is_active ? "Nonaktifkan" : "Aktifkan" }} Data</h5>
                    <p class="mt-2 text-lg">Apakah anda yakin ingin {{ $addon->is_active ? "menonaktifkan" : "mengaktifkan" }} addon {{ $addon->name }} ? </p>
                    <p class="text-danger">Proses ini tidak dapat dibatalkan</p>
                </div>
                <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                    <a data-dismiss="modal" class="btn btn-danger mr-3">
                        Batal
                    </a>
                    <a href="{{ route('addon.status', $addon->id) }}" class="btn btn-primary mr-3">
                        {{ $addon->is_active ? "Nonaktifkan" : "Aktifkan" }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade text-left" id="edit-{{ $addon->id }}" aria-labelledby="edit" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Addon</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </a>
            </div>
            <div class="modal-body">
                <form action="{{ route('addon.update', $addon->id) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-group">
                        <label>Nama Addon <span class="text-danger">*</span></label>
                        <input name="name" value="{{ $addon->name }}" type="text" class="form-control" data-validator="required" data-validator-label="Nama Addon" placeholder="Masukan nama addon" required>
                        <div class="form-control-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label>Charge <span class="text-danger">*</span></label>
                        <input name="charge" value="{{ $addon->charge }}" type="numeric" class="form-control" data-validator="required" data-validator-label="Charge addon" required>
                        <div class="form-control-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label>Harga <span class="text-danger">*</span></label>
                        <input name="price" type="number" value="{{ number_format($addon->price, 0, '', '') }}" class="form-control" data-validator="required" data-validator-label="Harga addon" placeholder="Masukan harga addon" required>
                        <div class="form-control-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3">{{ $addon->description }}</textarea>
                    </div>
                    <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="destroy-{{ $addon->id }}" aria-labelledby="destroy" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body modal-body-lg text-center text-wrap text-break">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                <div class="text-center mt-4">
                    <h5>Konfirmasi Hapus Data</h5>
                    <p class="mt-2 text-lg">Apakah anda yakin ingin menghapus addon {{ $addon->name }} ? </p>
                    <p class="text-danger">Proses ini tidak dapat dibatalkan</p>
                </div>
                <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                    <a data-dismiss="modal" class="btn btn-danger mr-3">
                        Batal
                    </a>
                    <form action="{{ route('addon.destroy', $addon->id) }}" method="post">
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


