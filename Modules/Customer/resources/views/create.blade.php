@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('customer.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Create Customer
        </h4>
    </div>
</div>
<div class="row">
    <div class="col-12 mt-4">
        <div class="p-4 border-1">
            <form action="{{ route('customer.store') }}" method="post">
                @csrf
                @method('post')
                <h3 class="element-header mt-3">Informasi Pribadi</h3>
                <div class="row">
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label>Nama <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control" data-validator-label="Nama Customer" data-validator="required" placeholder="Nama Customer" value="{{ old('name') }}">
                            <div class="form-control-feedback"></div>
                            @error('name')
                                <div class="text-danger text-sm mt-2">{{ $errors->first('name')}}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label>Email <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control" data-validator-label="Email Customer" data-validator="required|email" placeholder="Email Customer" value="{{ old('email') }}">
                            <div class="form-control-feedback"></div>
                            @error('email')
                                <div class="text-danger text-sm mt-2">{{ $errors->first('email')}}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="form-group">
                            <label>Nomor Telepon <span class="text-danger">*</span></label>
                            <input name="phone" type="number" class="form-control" data-validator-label="Nomor Telepon Customer" data-validator="required" placeholder="Nomor Telepon Customer" value="{{ old('phone') }}">
                            <div class="form-control-feedback"></div>
                            @error('phone')
                                <div class="text-danger text-sm mt-2">{{ $errors->first('phone')}}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-right">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

