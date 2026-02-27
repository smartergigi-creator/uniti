<!DOCTYPE html>
<html>

<head>
    <title>{{ $ebook->title }}</title>
</head>

<body>

    <h2>{{ $ebook->title }}</h2>

    {{-- Reuse existing flipbook --}}
    @include('ebook.flipbook', ['ebook' => $ebook])

</body>

</html>
