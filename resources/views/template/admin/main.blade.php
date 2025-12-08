@php
    use App\Libraries\AppProperties;
    $uri_segment = str_replace(['add_', 'add_sub', 'edit_', 'edit_sub', 'editgroup_', 'view_', 'view_sub', 'sub'], '', Request::segment(2));
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="theme-color" content="#131c5b">
    <meta name="author" content="{{ AppProperties::getProperties('author') }}">
    <meta name="description" content="{{ AppProperties::getProperties('description') }}">
    <meta name="csrf-token" id="csrf_token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ URL::to(AppProperties::getProperties('logo')) }}" type="image/png" sizes="16x16">
    <title>
        {{ env('APP_NAME') . (isset($title) ? ' | ' . $title : ucwords(str_replace('_', ' ', request()->segment(1) ? ' | ' . request()->segment(1) : ' | Dashboard'))) }}
    </title>

    @notifyCss

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emojionearea@3.4.2/dist/emojionearea.min.css">

    @include('template.admin.style')

    @stack('head')
</head>

<body class="hold-transition sidebar-mini layout-navbar-fixed pace-primary">

    <div class="wrapper">
        @include('template.admin.navbar')
        <div id="loader">
            <div class="lds-ellipsis">
                <div></div>
                <div></div>
                <div></div>
                <div></div>
            </div>
        </div>
        <div class="page-body sidebar-collpased">
            @include('template.admin.sidebar')
            <div class="page-content-wrapper">
                <div class="page-content-wrapper-inner">
                    <div class="content-viewport">
                        @auth
                            @yield('content')
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('notify::components.notify')

    <div class="mr-1 d-flex flex-column justify-content-end align-content-end items-end" id="custom-toast" style="z-index: 1; position: fixed; right: 0; top: 30px"></div>
    <div class="flasher fixed inset-0 flex items-end justify-center px-4 py-6 pointer-events-none sm:p-6 sm:items-start sm:justify-end d-none"></div>

    @notifyJs
    @include('template.admin.script')
    @stack('script')
</body>

</html>
