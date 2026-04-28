@php
    $showButton = true;
    $actionText = 'Selamatkan Akun Saya';
@endphp

@extends('emails.grace.layout')

@section('content')
    <div style="padding-bottom:30px">
        Hari terakhir. Data Anda dijadwalkan untuk dihapus dalam 24 jam. Ini adalah pesan terakhir dari kami sebelum sistem membersihkan akun.
    </div>
@endsection
