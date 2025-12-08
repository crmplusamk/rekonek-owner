@extends('template.admin.main')
@section('content')
@include('tabs.setting')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('role.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Detail Role
        </h4>
    </div>
</div>
<div class="row">
    <div class="col-12 mt-4">
        <div class="p-4 border-1">
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Nama Role</label>
                        <input type="text" class="form-control" value="{{ $role->alias }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <input type="text" class="form-control" value="{{ $role->is_active ? 'Aktif' : 'Nonaktif' }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Jumlah User</label>
                        <input type="text" class="form-control" value="{{ $role->users_count }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Jumlah Akses</label>
                        <input type="text" class="form-control" value="{{ $role->permissions_count }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Dibuat Pada</label>
                        <input type="text" class="form-control" value="{{ date('d M Y h:i:s', strtotime($role->created_at)) }}" readonly>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>Terakhir Update</label>
                        <input type="text" class="form-control" value="{{ $role->updated_at ? date('d M Y h:i:s', strtotime($role->updated_at)) : '' }}" readonly>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-right">
                <a href="{{ route('role.index') }}" class="btn btn-primary">Kembali</a>
            </div>
        </div>
    </div>
</div>
@endsection

