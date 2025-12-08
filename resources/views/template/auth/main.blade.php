<?php use App\Libraries\AppProperties; ?>
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#131c5b">
    <meta name="author" content="{{ AppProperties::getProperties('author') }}">
    <meta name="description" content="{{ AppProperties::getProperties('description') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ URL::to(AppProperties::getProperties('logo')) }}" type="image/png" sizes="16x16">
    <title>{{ env('APP_NAME').ucwords(str_replace('_',' ',((request()->segment(2))? ' | '.request()->segment(2):''))) }}</title>
    @include('template.auth.style')
</head>
<body>
    @yield('content')
    @include('template.auth.script')
    @yield('js')
    @yield('ajax')
</body>
</html>
