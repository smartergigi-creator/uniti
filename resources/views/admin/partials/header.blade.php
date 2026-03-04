<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title', 'Admin Panel')</title>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<link rel="stylesheet" href="{{ asset('admin/dist/assets/css/bootstrap.css') }}">
<link rel="stylesheet" href="{{ asset('admin/dist/assets/vendors/iconly/bold.css') }}">
<link rel="stylesheet" href="{{ asset('admin/dist/assets/vendors/perfect-scrollbar/perfect-scrollbar.css') }}">
<link rel="stylesheet" href="{{ asset('admin/dist/assets/vendors/bootstrap-icons/bootstrap-icons.css') }}">
<link rel="stylesheet" href="{{ asset('admin/dist/assets/css/app.css') }}">

@php
    $faviconVer = file_exists(public_path('favicon.ico')) ? filemtime(public_path('favicon.ico')) : time();
@endphp
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $faviconVer }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $faviconVer }}">
<link rel="icon" type="image/png" sizes="200x200" href="{{ asset('favicon-200x200.png') }}?v={{ $faviconVer }}">
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVer }}" sizes="any">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVer }}">
<link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ $faviconVer }}" sizes="200x200">

<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/style.css') }}">
