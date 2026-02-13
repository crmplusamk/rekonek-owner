<div class="modal fade" id="modalCreateAffiliator" tabindex="-1" aria-labelledby="modalCreateAffiliator" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Affiliator</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="{{ route('setting-affiliator.store') }}" method="post" id="formCreateAffiliator">
                    @csrf
                    <div class="form-group">
                        <label for="affiliator_name">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="affiliator_name" value="{{ old('name') }}" required maxlength="255" placeholder="Nama lengkap">
                        @error('name')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group">
                        <label for="affiliator_email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="affiliator_email" value="{{ old('email') }}" required placeholder="email@contoh.com">
                        @error('email')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
