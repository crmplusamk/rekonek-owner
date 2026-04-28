@php
    $showButton = true;
    $actionText = 'Aktifkan Kembali';
@endphp

@extends('emails.grace.layout')

@section('content')
    <div style="padding-bottom:30px">
        Akses pengiriman pesan dijeda. Data Anda masuk masa aman (30 hari). Klik untuk aktivasi kembali agar dashboard aktif lagi.
    </div>
@endsection
