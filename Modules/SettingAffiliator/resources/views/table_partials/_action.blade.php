<a href="#" data-toggle="dropdown" class="text-muted" aria-expanded="false">
    <i class="mdi mdi-dots-horizontal mdi-24px"></i>
</a>
<div class="dropdown-menu dropdown-menu-right">
    <a class="dropdown-item btn-config-affiliator pointer" href="javascript:void(0)" data-id="{{ $user->id }}" data-name="{{ $user->name }}">
        <i class="mdi mdi-settings"></i> Konfigurasi Komisi
    </a>
    <a class="dropdown-item btn-edit-affiliator pointer" href="javascript:void(0)" data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-email="{{ $user->email }}">
        <i class="mdi mdi-pencil"></i> Edit
    </a>
    <a class="dropdown-item pointer" data-toggle="modal" data-target="#destroy-{{ $user->id }}">
        <i class="mdi mdi-delete"></i> Hapus
    </a>
</div>

@include('settingaffiliator::modals._confirm_delete', ['user' => $user])
