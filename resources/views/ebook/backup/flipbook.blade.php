@extends('layout.app')

@section('content')




<div class="container flipbook-container">

    <h2 class="title">{{ $ebook->title }}</h2>

    <div id="viewer-wrapper">

        <div id="flipbook">

            {{-- ✅ COVER (PAGE 1) --}}
            <div class="page cover">
                {{-- <img src="{{ $pages[0] }}" alt="Cover"> --}}
                @if(!empty($pages) && isset($pages[0]))
    <img src="{{ $pages[0] }}" alt="Cover">
@endif

            </div>


            {{-- ✅ BLANK PAGE (for smooth desktop open animation) --}}
            {{-- <div class="page blank"></div> --}}
            @if(!request()->is('share/*'))
    <div class="page blank"></div>
@endif


            {{-- ✅ REAL PAGES --}}
            @foreach($pages as $index => $img)
                @if($index !== 0)
                    <div class="page">
                        <img src="{{ $img }}" alt="Page {{ $index + 1 }}">
                    </div>
                @endif
            @endforeach

        </div>

        {{-- ✅ NAVIGATION --}}
        <div class="nav-buttons">
            <img src="{{ asset('images/back.png') }}" id="prevPage" alt="Previous">
            <img src="{{ asset('images/share.png') }}" id="nextPage" alt="Next">
        </div>

    </div>

    {{-- 🔊 PAGE TURN SOUND --}}
    <audio id="flipSound" src="{{ asset('sound/pageflip.mp3') }}" preload="auto"></audio>

</div>

{{-- ✅ REQUIRED JS ORDER --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/turn.min.js') }}"></script>
<script src="{{ asset('js/script.js') }}"></script>

@endsection
