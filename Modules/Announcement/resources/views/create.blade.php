@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('announcement.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Tambah Pengumuman
        </h4>
        <p class="text-muted mb-4"><small>Buat banner pengumuman untuk ditampilkan di aplikasi Rekonek.</small></p>
    </div>
</div>

@include('announcement::partials._form', [
    'formAction' => route('announcement.store'),
    'method' => 'post',
    'announcement' => null,
    'selectedCompanies' => collect(),
])
@endsection
