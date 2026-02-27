@extends('layout.app')

@section('title', 'Flipbook')
@section('body-class', 'ebook-view')

@section('content')
    <div class="container-fluid vh-100 d-flex flex-column">
        <div id="ebookLoader">
            <div class="text-center">
                <div class="spinner mb-3"></div>
                <div id="ebookLoadingText" class="fw-semibold">Flipbook loading... 0%</div>
            </div>
        </div>

        <div class="row ebook-header-row">
            <div class="col-12 text-start py-2 ebook-header-col">
                <a href="{{ url('/home#ebooksSection') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left"></i>Back
                </a>


                <h2 class="title mb-0">{{ $ebook->title }}</h2>
            </div>
        </div>

        <div class="row flex-grow-1">
            <div class="col-12 d-flex justify-content-center align-items-center">
                <div id="viewer-wrapper" class="mb-3">

                    <div class="viewer-toolbar position-absolute top-0 end-0 m-3 d-flex gap-2">

                        <button id="zoomIn" class="btn btn-light btn-sm">
                            <i class="bi bi-zoom-in"></i>
                        </button>

                        <button id="zoomOut" class="btn btn-light btn-sm">
                            <i class="bi bi-zoom-out"></i>
                        </button>

                        <button id="zoomReset" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>

                        <button id="fullscreenToggle" class="btn btn-light btn-sm">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>

                    </div>


                    <div id="zoom-wrapper" class="position-relative">
                        <div id="ebook-scale">
                            <div id="flipbook">
                            </div>
                        </div>

                        <div class="ebook-side-nav">
                            <button class="side-btn prev" id="prevPage" type="button">
                                <img src="{{ asset('images/back.png') }}" alt="Previous">
                            </button>
                            <button class="side-btn next" id="nextPage" type="button">
                                <img src="{{ asset('images/share.png') }}" alt="Next">
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <audio id="flipSound" src="{{ asset('sound/page-flip-12.wav') }}" preload="auto"></audio>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        (function() {
            const flipbook = document.getElementById('flipbook');
            const pdfUrl = @json(asset($ebook->pdf_path));
            const loadingText = document.getElementById('ebookLoadingText');

            if (!flipbook || !window.pdfjsLib || !pdfUrl) {
                return;
            }

            // Start viewer after initial pages; remaining pages render in background.
            window.__PDF_PAGES_READY_PROMISE__ = new Promise(async (resolve, reject) => {
                const PLACEHOLDER_IMG =
                    'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

                pdfjsLib.GlobalWorkerOptions.workerSrc =
                    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

                try {
                    const pdf = await pdfjsLib.getDocument(pdfUrl).promise;
                    const isMobile = window.innerWidth <= 900;
                    const renderScale = isMobile ? 1.1 : 1.3;
                    const jpegQuality = isMobile ? 0.75 : 0.8;
                    const initialReadyPages = Math.min(pdf.numPages, isMobile ? 1 : 2);
                    const pageImages = [];

                    for (let i = 1; i <= pdf.numPages; i++) {
                        const wrapper = document.createElement('div');
                        wrapper.className = i === 1 ? 'page cover' : 'page';
                        wrapper.dataset.pageNo = String(i);
                        wrapper.dataset.loaded = '0';

                        const img = document.createElement('img');
                        img.src = PLACEHOLDER_IMG;
                        img.alt = 'Page ' + i;
                        img.draggable = false;
                        img.addEventListener('dragstart', (e) => e.preventDefault());

                        wrapper.appendChild(img);
                        flipbook.appendChild(wrapper);
                        pageImages.push(img);
                    }

                    const renderPageIntoImage = async (i) => {
                        const page = await pdf.getPage(i);
                        const viewport = page.getViewport({
                            scale: renderScale
                        });

                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');
                        canvas.width = Math.floor(viewport.width);
                        canvas.height = Math.floor(viewport.height);

                        await page.render({
                            canvasContext: ctx,
                            viewport
                        }).promise;

                        const img = pageImages[i - 1];
                        const wrapper = img?.closest('.page');
                        if (img) {
                            img.src = canvas.toDataURL('image/jpeg', jpegQuality);
                        }
                        if (wrapper) {
                            wrapper.dataset.loaded = '1';
                        }

                        if (typeof window.__PDF_PAGE_RENDERED_HOOK__ === 'function') {
                            window.__PDF_PAGE_RENDERED_HOOK__(i);
                        }

                        if (loadingText) {
                            const percent = Math.round((i / pdf.numPages) * 100);
                            loadingText.textContent = `Flipbook loading... ${percent}%`;
                        }
                    };

                    for (let i = 1; i <= initialReadyPages; i++) {
                        await renderPageIntoImage(i);
                    }

                    resolve();

                    // Background render for remaining pages.
                    (async () => {
                        for (let i = initialReadyPages + 1; i <= pdf.numPages; i++) {
                            try {
                                await renderPageIntoImage(i);
                            } catch (_) {
                                // Keep placeholder on failed page render.
                            }
                        }
                    })();
                } catch (error) {
                    reject(error);
                }
            });
        })();
    </script>
@endsection
