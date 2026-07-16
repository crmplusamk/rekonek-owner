@php
    $categories = \Modules\Feature\App\Models\Feature::where('is_parent', true)->orderBy('order')->get();
@endphp

<a href="#" data-toggle="dropdown" class="text-muted" aria-expanded="false">
    <i class="mdi mdi-dots-horizontal mdi-24px"></i>
</a>
<div class="dropdown-menu dropdown-menu-right" role="menu">
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#edit-{{ $feature->id }}">
        <i class="mdi mdi-pencil"></i> Edit
    </a>
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#destroy-{{ $feature->id }}">
        <i class="mdi mdi-delete"></i> Hapus
    </a>
</div>

<div class="modal fade text-left" id="edit-{{ $feature->id }}" aria-labelledby="edit" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Fitur</h5>
                <a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></a>
            </div>
            <div class="modal-body">
                <form action="{{ route('feature.update', $feature->id) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-group">
                        <label>Kategori <span class="text-danger">*</span></label>
                        <select name="parent" class="form-control" style="width: 100%" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ $feature->parent_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nama Fitur <span class="text-danger">*</span></label>
                        <input name="name" value="{{ $feature->name }}" type="text" class="form-control" placeholder="Masukan nama fitur" required>
                    </div>
                    <div class="form-group mt-4">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input setting_is_addon" id="is_addon-{{ $feature->id }}" {{ $feature->is_addon ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_addon-{{ $feature->id }}">Aktifkan Addon</label>
                            <input type="hidden" name="is_addon" value="{{ $feature->is_addon ? 'on' : 'off' }}">
                        </div>
                    </div>
                    <div class="modal-action mt-4 mb-3">
                        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="destroy-{{ $feature->id }}" aria-labelledby="destroy" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body modal-body-lg text-center text-wrap text-break">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <i class="mdi mdi-comment-question-outline text-primary" style="font-size: 50px"></i>
                <div class="text-center mt-4">
                    <h5>Konfirmasi Hapus Data</h5>
                    <p class="mt-2 text-lg">Apakah anda yakin ingin menghapus fitur {{ $feature->name }}?</p>
                    <p class="text-danger">Proses ini tidak dapat dibatalkan</p>
                </div>
                <div class="modal-action mt-4 mb-3 d-flex justify-content-center align-items-center">
                    <a data-dismiss="modal" class="btn btn-danger mr-3">Batal</a>
                    <form action="{{ route('feature.destroy', $feature->id) }}" method="post">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn btn-primary">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
