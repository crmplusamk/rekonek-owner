{{-- Block User Affiliator saja. Requires $affiliatorUsers. Optional: $promoCode (for edit). --}}
<div id="affiliator-block" class="card mb-4 shadow-sm border-0 {{ (old('type', isset($promoCode) ? $promoCode->type : '') === 'affiliator') ? '' : 'd-none' }}">
    <div class="card-body">
        <h3 class="element-header mb-3">User Affiliator</h3>
        <div class="row">
            <div class="col-12 form-group">
                <label for="affiliator_user_id" class="font-weight-bold">User Affiliator <span class="text-danger">*</span></label>
                <select class="form-control select-2 @error('affiliator_user_id') is-invalid @enderror" name="affiliator_user_id" id="affiliator_user_id" data-placeholder="Pilih user affiliator..." style="width:100%">
                    <option value="">- Pilih User Affiliator -</option>
                    @foreach ($affiliatorUsers as $u)
                        <option value="{{ $u->id }}" {{ old('affiliator_user_id', isset($promoCode) ? $promoCode->affiliator_user_id : '') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->email }})</option>
                    @endforeach
                </select>
                @error('affiliator_user_id')<small class="text-danger mt-1 d-block">{{ $message }}</small>@enderror
                <small class="form-text text-muted">Konfigurasi komisi affiliator dapat diatur di menu Affiliator (Setting Affiliator).</small>
            </div>
        </div>
    </div>
</div>
