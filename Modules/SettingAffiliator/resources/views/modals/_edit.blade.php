<div class="modal fade" id="modalEditAffiliator" tabindex="-1" aria-labelledby="modalEditAffiliator" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Affiliator</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="post" id="formEditAffiliator">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="edit_affiliator_name">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_affiliator_name" required maxlength="255" placeholder="Nama lengkap">
                    </div>
                    <div class="form-group">
                        <label for="edit_affiliator_email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" id="edit_affiliator_email" required placeholder="email@contoh.com">
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
