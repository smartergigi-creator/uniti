@extends('layout.app')

@section('title', 'Flipbook')
@section('body-class', 'ebook-view')

@section('content')
    @php
        $canReportIssue = auth()->check() && isset($reportRecipients) && $reportRecipients->isNotEmpty();
    @endphp

    <div class="container-fluid vh-100 d-flex flex-column">
        <div id="ebookLoader">
            <div class="text-center">
                <div class="spinner mb-3"></div>
                <div id="ebookLoadingText" class="fw-semibold">Flipbook loading... 0%</div>
            </div>
        </div>

        <div class="row ebook-header-row">
            <div class="col-12 ebook-header-col">

                <a id="ebookBackButton" href="{{ url('/home#ebooksSection') }}"
                    class="btn btn-outline-dark btn-sm rounded-pill px-3 mb-2">
                    <i class="bi bi-arrow-left"></i> Back
                </a>

                <div class="ebook-title-card">
                    <h2 class="title mb-0">{{ $ebook->title }}</h2>
                </div>

            </div>
        </div>

        <div class="row flex-grow-1">
            <div class="col-12 d-flex justify-content-center align-items-center">
                <div id="viewer-wrapper" class="mb-3">

                    <div class="viewer-toolbar position-absolute top-0 end-0 m-3 d-flex gap-2">
                        <a id="downloadEbook" href="{{ $downloadUrl }}" class="btn btn-light btn-sm"
                            data-download-name="{{ trim($ebook->file_title ?: $ebook->title ?: 'ebook') . '.pdf' }}"
                            aria-label="Download ebook">
                            <i class="bi bi-download"></i>
                        </a>

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

                    <div class="ebook-footer-bar" aria-label="Viewer footer controls">
                        <div class="ebook-bottom-controls" aria-label="Viewer controls">
                            <div class="ebook-page-pill">
                                <span id="ebookCurrentPage">1</span>
                                <span class="ebook-page-divider">/</span>
                                <span id="ebookTotalPages">1</span>
                            </div>

                            <span class="ebook-bottom-separator" aria-hidden="true"></span>

                            <button type="button" class="ebook-refresh-btn" id="refreshViewer" aria-label="Refresh viewer">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>

                        @if ($canReportIssue)
                            <button type="button" class="report-issue-trigger" data-bs-toggle="modal"
                                data-bs-target="#reportIssueModal">
                                <span class="report-issue-icon">
                                    <img src="{{ asset('images/report.png') }}" alt="Report">
                                </span>
                                <span>Report Issue</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <audio id="flipSound" src="{{ asset('sound/page-flip-12.wav') }}" preload="auto"></audio>
    </div>

    @if ($canReportIssue)
        <div class="modal fade" id="reportIssueModal" tabindex="-1" aria-labelledby="reportIssueModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content report-issue-modal">
                    <div class="modal-header border-0 pb-0">
                        <div class="d-flex align-items-center gap-3">
                            <span class="report-issue-title-icon">
                                <i class="bi bi-exclamation-circle-fill"></i>
                            </span>
                            <div>
                                <h5 class="modal-title mb-0" id="reportIssueModalLabel">Report an Issue</h5>
                            </div>
                        </div>
                        <button type="button" class="btn-close report-issue-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <form id="reportIssueForm" data-report-url="{{ route('ebook.report-issue', $ebook->id) }}">
                        <div class="modal-body pt-3">
                            <div class="report-issue-meta">
                                <p><strong>Ebook:</strong> {{ $ebook->title }}</p>
                                <p><strong>Page:</strong> <span id="reportIssuePageLabel">1</span></p>
                            </div>

                            <input type="hidden" id="reportIssuePageInput" name="page" value="1">
                            <input type="hidden" id="reportIssueUserId" name="recipient_id" value="">

                            <div class="mb-3">
                                <label for="reportUserSearch" class="form-label fw-semibold">Send to:</label>
                                <div class="report-user-picker" id="reportUserPicker">
                                    <button type="button" class="report-user-toggle" id="reportUserToggle"
                                        aria-expanded="false">
                                        <span>
                                            <strong id="reportSelectedUserName">Select user...</strong>
                                            <small id="reportSelectedUserEmail">Choose a recipient from the list</small>
                                        </span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>

                                    <div class="report-user-dropdown d-none" id="reportUserDropdown">
                                        <div class="report-user-search-wrap">
                                            <i class="bi bi-search"></i>
                                            <input type="search" id="reportUserSearch" class="form-control"
                                                placeholder="Select user...">
                                        </div>

                                        <div class="report-user-list" id="reportUserList">
                                            @foreach ($reportRecipients as $recipient)
                                                @php
                                                    $initials = collect(explode(' ', trim($recipient->name)))
                                                        ->filter()
                                                        ->take(2)
                                                        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                                                        ->implode('');
                                                @endphp
                                                <button type="button" class="report-user-option"
                                                    data-user-id="{{ $recipient->id }}"
                                                    data-user-name="{{ $recipient->name }}"
                                                    data-user-email="{{ $recipient->email }}">
                                                    <span class="report-user-avatar">{{ $initials ?: 'U' }}</span>
                                                    <span class="report-user-copy">
                                                        <strong>{{ $recipient->name }}</strong>
                                                        <small>{{ $recipient->email }}</small>
                                                    </span>
                                                    <i class="bi bi-check-lg report-user-check"></i>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="reportIssueDescription" class="form-label fw-semibold">Describe the
                                    issue:</label>
                                <textarea id="reportIssueDescription" name="description" rows="4" class="form-control"
                                    placeholder="Type the issue you found on this page..."></textarea>
                            </div>

                            <div id="reportIssueFeedback" class="small mt-2"></div>
                        </div>

                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary px-4"
                                data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        (function() {
            const backButton = document.getElementById('ebookBackButton');

            if (backButton) {
                backButton.addEventListener('click', () => {
                    window.setTimeout(() => {
                        if (document.visibilityState === 'visible' &&
                            window.location.pathname.startsWith('/ebook/')) {
                            window.location.assign(backButton.href);
                        }
                    }, 150);
                });
            }

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

        (function() {
            const modalEl = document.getElementById('reportIssueModal');
            if (!modalEl) {
                return;
            }

            const toggle = document.getElementById('reportUserToggle');
            const dropdown = document.getElementById('reportUserDropdown');
            const searchInput = document.getElementById('reportUserSearch');
            const userIdInput = document.getElementById('reportIssueUserId');
            const selectedName = document.getElementById('reportSelectedUserName');
            const selectedEmail = document.getElementById('reportSelectedUserEmail');
            const pageLabel = document.getElementById('reportIssuePageLabel');
            const pageInput = document.getElementById('reportIssuePageInput');
            const feedback = document.getElementById('reportIssueFeedback');
            const description = document.getElementById('reportIssueDescription');
            const form = document.getElementById('reportIssueForm');
            const submitButton = form.querySelector('button[type="submit"]');
            const options = Array.from(document.querySelectorAll('.report-user-option'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const reportUrl = form.dataset.reportUrl;

            const syncCurrentPage = () => {
                const viewer = window.__EBOOK_VIEWER__;
                const pageNo = Number(viewer?.getCurrentPageNumber?.() || 1);
                const pageDisplay = viewer?.getCurrentPageLabel?.() || String(pageNo);
                pageLabel.textContent = pageDisplay;
                pageInput.value = pageNo;
            };

            const filterUsers = (term) => {
                const keyword = term.trim().toLowerCase();

                options.forEach((option) => {
                    const haystack =
                        `${option.dataset.userName || ''} ${option.dataset.userEmail || ''}`.toLowerCase();
                    option.classList.toggle('d-none', keyword !== '' && !haystack.includes(keyword));
                });
            };

            const closeDropdown = () => {
                dropdown.classList.add('d-none');
                toggle.setAttribute('aria-expanded', 'false');
            };

            const openDropdown = () => {
                dropdown.classList.remove('d-none');
                toggle.setAttribute('aria-expanded', 'true');
                searchInput.focus();
            };

            toggle.addEventListener('click', () => {
                if (dropdown.classList.contains('d-none')) {
                    openDropdown();
                    return;
                }

                closeDropdown();
            });

            searchInput.addEventListener('input', (event) => {
                filterUsers(event.target.value);
            });

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    userIdInput.value = option.dataset.userId || '';
                    selectedName.textContent = option.dataset.userName || 'Select user...';
                    selectedEmail.textContent = option.dataset.userEmail ||
                        'Choose a recipient from the list';
                    feedback.textContent = '';
                    feedback.className = 'small mt-2';

                    options.forEach((item) => item.classList.remove('active'));
                    option.classList.add('active');
                    closeDropdown();
                });
            });

            document.addEventListener('click', (event) => {
                if (!event.target.closest('#reportUserPicker')) {
                    closeDropdown();
                }
            });

            modalEl.addEventListener('show.bs.modal', () => {
                document.body.classList.add('viewer-modal-open');
                syncCurrentPage();
                filterUsers('');
                searchInput.value = '';
                closeDropdown();
                feedback.textContent = '';
                feedback.className = 'small mt-2';
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
                document.body.classList.remove('viewer-modal-open');
            });

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                syncCurrentPage();

                if (!userIdInput.value) {
                    feedback.textContent = 'Please select a user.';
                    feedback.className = 'small mt-2 text-danger';
                    openDropdown();
                    return;
                }

                if (!description.value.trim()) {
                    feedback.textContent = 'Please describe the issue.';
                    feedback.className = 'small mt-2 text-danger';
                    description.focus();
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';
                feedback.textContent = 'Saving issue report...';
                feedback.className = 'small mt-2 text-muted';

                try {
                    const response = await fetch(reportUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            recipient_id: userIdInput.value,
                            page: pageInput.value,
                            description: description.value.trim(),
                        }),
                    });

                    const result = await response.json();

                    if (!response.ok || !result.status) {
                        throw new Error(result.message || 'Issue report could not be saved.');
                    }

                    feedback.textContent = result.message || 'Issue report saved successfully.';
                    feedback.className = 'small mt-2 text-success';
                    description.value = '';
                    userIdInput.value = '';
                    selectedName.textContent = 'Select user...';
                    selectedEmail.textContent = 'Choose a recipient from the list';
                    options.forEach((item) => item.classList.remove('active'));
                } catch (error) {
                    feedback.textContent = error.message || 'Issue report could not be saved.';
                    feedback.className = 'small mt-2 text-danger';
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit';
                }
            });
        })();
    </script>
@endsection
