<!DOCTYPE html>
<html>

<head>
    @include('layout.header')
</head>

<body class="d-flex flex-column min-vh-100 @yield('body-class', 'dashboard-page')">

    @php
        $bodyClass = trim($__env->yieldContent('body-class'));
        $isEbookView = $bodyClass === 'ebook-view';
        $isFluidPage = in_array($bodyClass, ['ebook-view', 'website-page'], true);
    @endphp

    @unless ($isEbookView)
        @include('layout.navbar')
    @endunless

    {{-- Page Content --}}
    <main class="flex-grow-1 {{ $isFluidPage ? 'py-0' : 'container py-4' }}">
        @yield('content')
    </main>

    {{-- Footer --}}
    @include('layout.footer')

    @stack('scripts')

</body>

</html>
