@extends('layout.app')

@section('content')
<div class="flipbook-container">

    <h2 class="title">{{ $ebook->title }}</h2>

    <div id="viewer-wrapper">

        {{-- 🔝 TOP TOOLBAR (NAV + ZOOM + FULLSCREEN) --}}
        <div class="viewer-toolbar">

            <button id="prevPage" title="Previous Page">←</button>

            <span class="page-indicator">
                 <span id="currentPage">1</span>
            </span>

            <button id="nextPage" title="Next Page">→</button>

            <div class="toolbar-divider"></div>

            <button id="zoomIn">+</button>
            <button id="zoomOut">−</button>
            <button id="zoomReset">⟳</button>
            <button id="fullscreenToggle">⛶</button>

        </div>

        {{-- 🔑 ZOOM WRAPPER (ONLY BOOK ZOOMS) --}}
        <div id="zoom-wrapper">
            <div id="flipbook">

                {{-- ✅ COVER --}}
                <div class="page cover">
                    @if (!empty($pages) && isset($pages[0]))
                        <img src="{{ $pages[0] }}" alt="Cover">
                    @endif
                </div>

                {{-- ✅ BLANK PAGE (DESKTOP ONLY) --}}
                {{-- @if (!request()->is('share/*'))
                    <div class="page blank"></div>
                @endif --}}

                {{-- ✅ REAL PAGES --}}
                @foreach ($pages as $index => $img)
                    @if ($index !== 0)
                        <div class="page">
                            <img src="{{ $img }}" alt="Page {{ $index + 1 }}">
                        </div>
                    @endif
                @endforeach

            </div>
        </div>

    </div>

    {{-- 🔊 PAGE TURN SOUND --}}
    <audio id="flipSound" src="{{ asset('sound/pageflip.mp3') }}" preload="auto"></audio>

</div>

{{-- ✅ REQUIRED JS --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="{{ asset('js/turn.min.js') }}"></cript>
<script src="https://unpkg.com/@panzoom/panzoom/dist/panzoom.min.js"></script>
<script src="{{ asset('js/script.js') }}"></script>
@endsection
