@extends('template.admin.main')
@section('content')
@include('tabs.setting')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('user.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Edit User
        </h4>
    </div>
</div>
<div class="row">
    <div class="col-12 mt-4">
        <div class="p-4 border-1">
            <form action="{{ route('user.update', $data->id) }}" method="post">
                @csrf
                @method('put')
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label>Nama <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control" data-validator-label="Nama User" data-validator="required" placeholder="Nama User" value="{{ old('name', $data->name) }}">
                            <div class="form-control-feedback"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label>Email <span class="text-danger">*</span></label>
                            <input name="email" type="email" class="form-control" data-validator-label="Email User" data-validator="required|email" placeholder="Email User" value="{{ old('email', $data->email) }}">
                            <div class="form-control-feedback"></div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label>Password <span class="text-danger">*</span></label>
                            <input name="password" type="password" class="form-control" placeholder="Isi password untuk mengubah" value="{{ old('password') }}">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label><br>
                            <select name="is_active" class="form-control select-2 ">
                                <option value="1" {{ $data->is_active == true ? 'selected' :'' }}> Aktif </option>
                                <option value="0" {{ $data->is_active == false ? 'selected' :'' }}> Nonaktif </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label>Role <span class="text-danger">*</span></label><br>
                            <select name="role" class="form-control select-2" data-validator-label="Role" data-validator="required" required>
                                <option value="" selected></option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ $data->roles->contains($role->id) ? 'selected' :'' }}> {{ $role->alias }} </option>
                                @endforeach
                            </select>
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

