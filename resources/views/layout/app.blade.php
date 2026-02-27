<!DOCTYPE html>
<html>

<head>
    @include('layout.header')
</head>

<body class="d-flex flex-column min-vh-100 @yield('body-class', 'dashboard-page')">

    @php
        $isEbookView = trim($__env->yieldContent('body-class')) === 'ebook-view';
    @endphp

    @unless ($isEbookView)
        @include('layout.navbar')
    @endunless

    {{-- Page Content --}}
    <main class="flex-grow-1 {{ $isEbookView ? 'py-0' : 'container py-4' }}">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('layout.footer')

</body>

</html>
