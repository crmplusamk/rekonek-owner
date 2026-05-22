@extends('template.admin.main')
@section('content')

<div class="row">
    <div class="col-12">
        <h4 class="pt-2">
            <a href="{{ route('announcement.index') }}">
                <i class="mdi mdi-chevron-left-circle-outline text-gray pr-2 border-right"></i>
            </a> Ubah Pengumuman
        </h4>
        <p class="text-muted mb-4"><small>Perbarui banner pengumuman dan aturan penayangannya.</small></p>
    </div>
</div>

@include('announcement::partials._form', [
    'formAction' => route('announcement.update', $announcement->id),
    'method' => 'put',
    'announcement' => $announcement,
    'selectedCompanies' => $selectedCompanies,
])
@endsection
