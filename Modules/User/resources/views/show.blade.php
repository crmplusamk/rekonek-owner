@extends('template.admin.main')
@section('content')
@include('tabs.setting')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('user.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail User
        </h4>
    </div>
</div>
<div class="row">
    <div class="col-12 mt-4">
        <div class="p-4 border-1">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Nama</label>
                        <input type="text" class="form-control" value="{{ $data->name }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" class="form-control" value="{{ $data->email }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" class="form-control" value="{{ $data->is_active ? 'Aktif' : 'Nonaktif' }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" class="form-control" value="{{ $data->roles->first()->alias }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Dibuat Pada</label>
                        <input type="text" class="form-control" value="{{ date('d M Y h:i:s', strtotime($data->created_at)) }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Terakhir Update</label>
                        <input type="text" class="form-control" value="{{ $data->updated_at ? date('d M Y h:i:s', strtotime($role->updated_at)) : '' }}" readonly>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-right">
                <a href="{{ route('user.index') }}" class="btn btn-primary">Kembali</a>
            </div>
        </div>
    </div>
</div>
@endsection

