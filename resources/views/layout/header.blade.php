<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

@php
    $faviconVer = file_exists(public_path('favicon.ico')) ? filemtime(public_path('favicon.ico')) : time();
@endphp
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}?v={{ $faviconVer }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}?v={{ $faviconVer }}">
<link rel="icon" type="image/png" sizes="200x200" href="{{ asset('favicon-200x200.png') }}?v={{ $faviconVer }}">
<link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVer }}" sizes="any">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVer }}">
<link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ $faviconVer }}" sizes="200x200">

<meta name="csrf-token" content="{{ csrf_token() }}">

<title>Ebook</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

@php
    $styleCssVer = file_exists(public_path('css/style.css')) ? filemtime(public_path('css/style.css')) : time();
@endphp
<link rel="stylesheet" href="{{ asset('css/style.css') }}?v={{ $styleCssVer }}">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
