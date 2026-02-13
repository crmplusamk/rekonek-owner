<div class="modal fade" id="modalConfigAffiliator" tabindex="-1" aria-labelledby="modalConfigAffiliator" aria-hidden="true">
    <div class="modal-dialog modal-dialog-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfigAffiliatorTitle">Konfigurasi Komisi</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="post" id="formConfigAffiliator">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="user_id" id="config_user_id" value="">
                    <input type="hidden" name="commission_type_registrasi" value="percentage">
                    <input type="hidden" name="commission_type_perpanjangan" value="percentage">
                    <div class="row">
                        <div class="col-12 form-group">
                            <label class="font-weight-bold">Komisi Registrasi (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="commission_value_registrasi" id="config_commission_value_registrasi" min="0" max="100" step="0.01" placeholder="0" required>
                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                            </div>
                        </div>
                        <div class="col-12 form-group">
                            <label class="font-weight-bold">Komisi Perpanjangan (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="commission_value_perpanjangan" id="config_commission_value_perpanjangan" min="0" max="100" step="0.01" placeholder="0" required>
                                <div class="input-group-append"><span class="input-group-text">%</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary btn-block">Simpan Konfigurasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
