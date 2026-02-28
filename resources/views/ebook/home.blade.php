@extends('layout.app')

@section('title', 'Home')
@section('body-class', 'ebook-home')

@section('content')

    <section class="home-hero">
        <div class="container">
            <div class="row align-items-center">

                <div class="col-lg-6">
                    <h1>Explore Our Digital eBook Collection</h1>
                    <p>Access company, brand and industry eBooks in one place.</p>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <a href="#ebooksSection" class="btn btn-info">
                            Browse eBooks &rarr;
                        </a>

                        @if ($canUploadNow)
                            <button type="button" class="btn btn-outline-info" id="openUploadMetaModal">
                                + Upload PDF
                            </button>
                        @endif
                    </div>

                    @if ($canUploadNow)
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="modal fade" id="uploadMetaModal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Upload eBook</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="file" id="pdfInput" name="pdfs[]" multiple
                                                accept="application/pdf" hidden>
                                            <input type="file" id="folderInput" name="pdfs[]" webkitdirectory directory
                                                multiple hidden>

                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                <button type="button" class="btn btn-outline-primary" id="selectFiles">
                                                    Select PDF(s)
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" id="selectFolder">
                                                    Select Folder
                                                </button>
                                            </div>

                                            <div id="fileList" class="border rounded p-3 mb-3" style="display:none;">
                                                <strong>Selected Files (<span id="fileCount">0</span>)</strong>
                                                <ul id="fileItems" class="mb-0 mt-2"></ul>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Ebook Name</label>
                                                <input type="text" name="ebook_name" class="form-control"
                                                    placeholder="Enter eBook name" required>
                                            </div>

                                            <div class="mb-3" id="uploadCategoryField">
                                                <label class="form-label fw-semibold">Category</label>
                                                <select name="category_id" id="uploadCategorySelect" class="form-select"
                                                    required>
                                                    <option value="">Select Category</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-3" id="uploadSubCategoryField" style="display:none;">
                                                <label class="form-label fw-semibold">Sub Category</label>
                                                <select name="subcategory_id" id="uploadSubCategorySelect"
                                                    class="form-select">
                                                    <option value="">Select Subcategory</option>
                                                </select>
                                            </div>

                                            <div class="mb-3" id="uploadRelatedSubCategoryField" style="display:none;">
                                                <label class="form-label fw-semibold">Related Sub Category</label>
                                                <select name="related_subcategory_id" id="uploadRelatedSubCategorySelect"
                                                    class="form-select">
                                                    <option value="">Select Related Subcategory</option>
                                                </select>
                                            </div>

                                            <div id="uploadStatus" class="upload-status mt-3" style="display:none;">
                                                <span class="spinner"></span>
                                                <span class="text">Uploading ebook... Please wait</span>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-info">Upload & Save</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>

                <div class="col-lg-6 text-center">
                    <img src="{{ asset('images/homepage.png') }}" alt="Hero">
                </div>

            </div>
        </div>
    </section>

    <div class="container mt-5" id="ebooksSection">

        <div class="home-toolbar mb-5">
            <form class="row g-3" method="GET" action="{{ url('/home') }}" id="ebookFilterForm">

                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search ebooks..."
                        value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <select class="form-control" name="category" id="categorySelect" data-cascade-mode="filter">
                        <option value="">All Categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected($selectedCategoryId == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3" id="subCategoryField"
                    style="{{ $subcategories->isNotEmpty() || $selectedSubcategoryId ? '' : 'display:none;' }}">
                    <select class="form-control" name="subcategory" id="subCategorySelect"
                        data-selected="{{ $selectedSubcategoryId }}">
                        <option value="">All Sub Categories</option>
                        @foreach ($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" @selected($selectedSubcategoryId == $subcategory->id)>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3" id="relatedSubCategoryField"
                    style="{{ $relatedSubcategories->isNotEmpty() || $selectedRelatedSubcategoryId ? '' : 'display:none;' }}">
                    <select class="form-control" name="related_subcategory" id="relatedSubCategorySelect"
                        data-selected="{{ $selectedRelatedSubcategoryId }}">
                        <option value="">All Related Sub Categories</option>
                        @foreach ($relatedSubcategories as $relatedSubcategory)
                            <option value="{{ $relatedSubcategory->id }}" @selected($selectedRelatedSubcategoryId == $relatedSubcategory->id)>
                                {{ $relatedSubcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-12 col-lg-2">
                    <button class="btn btn-dark w-100" type="submit">
                        Filter
                    </button>
                </div>

                <div class="col-md-12 col-lg-2">
                    <a href="{{ url('/home') }}" class="btn btn-outline-secondary w-100" id="clearFilterBtn">
                        Clear Filter
                    </a>
                </div>

            </form>
        </div>


        {{-- BOOK GRID --}}
        <div id="ebookResults">
            <div class="row">
                @forelse ($ebooks as $book)
                    @php
                        $pdfUrl = asset(ltrim(str_replace('\\', '/', $book->pdf_path), '/'));
                    @endphp
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-5 text-center">
                        {{-- <p>{{ $book->pdf_path }}</p> --}}
                        <div class="book-wrapper">
                            <div class="book">
                                <img src="{{ asset('images/homecover.png') }}" data-pdf-cover="1"
                                    data-pdf-url="{{ $pdfUrl }}" alt="{{ $book->title }} cover">
                            </div>

                            <div class="book-actions" aria-label="Book actions">
                                <a href="{{ route('ebook.view', $book->slug) }}" class="book-action-btn"
                                    title="Preview">
                                    <i class="bi bi-eye"></i>
                                    <span>Preview</span>
                                </a>

                                @if ($canShareNow)
                                    <button type="button" class="book-action-btn share-action-btn"
                                        onclick="openShareModal({{ $book->id }})" title="Share">
                                        <i class="bi bi-share"></i>
                                        <span>Share</span>
                                    </button>
                                @endif
                            </div>

                            <div class="book-info">
                                <h6>{{ $book->title }}</h6>
                                <small>{{ $book->created_at?->format('d M Y') }}</small>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="col-12 text-center">
                        <p>No eBooks found.</p>
                    </div>
                @endforelse

            </div>

            <div class="mt-3">
                {{ $ebooks->withQueryString()->links('pagination::bootstrap-5') }}
            </div>
        </div>

    </div>

    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Ebook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="shareLinkInput" class="form-control" readonly>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="copyShareLink()">Copy Link</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.pdfjsLib) return;

            const coverImages = Array.from(document.querySelectorAll('img[data-pdf-cover="1"][data-pdf-url]'));
            if (!coverImages.length) return;

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

            const renderCover = async (imgEl) => {
                const pdfUrl = imgEl.getAttribute('data-pdf-url');
                if (!pdfUrl) return;

                try {
                    const pdf = await pdfjsLib.getDocument(pdfUrl).promise;
                    const page = await pdf.getPage(1);
                    const viewport = page.getViewport({
                        scale: 0.8
                    });

                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);

                    await page.render({
                        canvasContext: ctx,
                        viewport
                    }).promise;

                    imgEl.src = canvas.toDataURL('image/jpeg', 0.9);
                } catch (e) {
                    // Keep default placeholder image if PDF render fails.
                }
            };

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries, obs) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) return;
                        renderCover(entry.target);
                        obs.unobserve(entry.target);
                    });
                }, {
                    rootMargin: '120px 0px'
                });

                coverImages.forEach((imgEl) => observer.observe(imgEl));
            } else {
                coverImages.forEach((imgEl) => renderCover(imgEl));
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const canShareNow = @json((bool) $canShareNow);
            if (!canShareNow) {
                document.querySelectorAll('.share-action-btn').forEach((btn) => btn.remove());
            }

            const uploadForm = document.getElementById('uploadForm');
            const openModalBtn = document.getElementById('openUploadMetaModal');
            const uploadModalEl = document.getElementById('uploadMetaModal');
            const pdfInput = document.getElementById('pdfInput');
            const folderInput = document.getElementById('folderInput');

            const categorySelect = document.getElementById('uploadCategorySelect');
            const subCategorySelect = document.getElementById('uploadSubCategorySelect');
            const relatedSubCategorySelect = document.getElementById('uploadRelatedSubCategorySelect');

            const subCategoryField = document.getElementById('uploadSubCategoryField');
            const relatedSubCategoryField = document.getElementById('uploadRelatedSubCategoryField');

            if (!uploadForm || !openModalBtn || !uploadModalEl || !categorySelect || !subCategorySelect) return;

            const uploadModal = new bootstrap.Modal(uploadModalEl);

            const toggleField = (field, show) => {
                if (!field) return;
                field.style.display = show ? '' : 'none';
            };

            const resetSubCategories = () => {
                subCategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
            };

            const resetRelatedSubCategories = () => {
                if (!relatedSubCategorySelect) return;
                relatedSubCategorySelect.innerHTML = '<option value="">Select Related Subcategory</option>';
            };

            const loadChildren = (parentId, targetSelect, placeholder) => {
                targetSelect.innerHTML = `<option value="">${placeholder}</option>`;

                if (!parentId) return Promise.resolve([]);

                return fetch('/get-subcategories/' + encodeURIComponent(parentId), {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json'
                        }
                    })
                    .then((res) => res.json())
                    .then((items) => {
                        if (!Array.isArray(items)) return [];

                        items.forEach((item) => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.name;
                            targetSelect.appendChild(option);
                        });

                        return items;
                    })
                    .catch(() => []);
            };

            openModalBtn.addEventListener('click', function() {
                uploadModal.show();
            });

            categorySelect.addEventListener('change', function() {
                const categoryId = this.value;

                resetSubCategories();
                resetRelatedSubCategories();
                toggleField(relatedSubCategoryField, false);
                subCategorySelect.required = false;
                if (relatedSubCategorySelect) {
                    relatedSubCategorySelect.required = false;
                }

                if (!categoryId) {
                    toggleField(subCategoryField, false);
                    return;
                }

                loadChildren(categoryId, subCategorySelect, 'Select Subcategory').then((items) => {
                    const hasItems = items.length > 0;
                    toggleField(subCategoryField, hasItems);
                    subCategorySelect.required = hasItems;
                });
            });

            subCategorySelect.addEventListener('change', function() {
                const subCategoryId = this.value;
                if (!relatedSubCategorySelect) return;

                resetRelatedSubCategories();
                relatedSubCategorySelect.required = false;

                if (!subCategoryId) {
                    toggleField(relatedSubCategoryField, false);
                    return;
                }

                loadChildren(subCategoryId, relatedSubCategorySelect, 'Select Related Subcategory').then((
                    items) => {
                    const hasItems = items.length > 0;
                    toggleField(relatedSubCategoryField, hasItems);
                    relatedSubCategorySelect.required = hasItems;
                });
            });

            uploadForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(uploadForm);
                const selectedFiles = [];

                if (pdfInput && pdfInput.files?.length) {
                    selectedFiles.push(...Array.from(pdfInput.files));
                }
                if (folderInput && folderInput.files?.length) {
                    selectedFiles.push(...Array.from(folderInput.files));
                }

                if (!selectedFiles.length) {
                    alert('Please select at least one PDF file.');
                    return;
                }

                formData.delete('pdfs[]');
                selectedFiles.forEach((file) => {
                    formData.append('pdfs[]', file);
                });

                const uploadStatus = document.getElementById('uploadStatus');
                if (uploadStatus) uploadStatus.style.display = 'flex';

                try {
                    const res = await fetch('/ebooks/upload', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')
                                ?.getAttribute('content') || '',
                            Accept: 'application/json',
                        },
                        body: formData,
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok || !data.status) {
                        throw new Error(data.message || 'Upload failed');
                    }

                    alert(data.message || 'Upload successful');
                    window.location.reload();
                } catch (err) {
                    alert(err.message || 'Upload failed');
                } finally {
                    if (uploadStatus) uploadStatus.style.display = 'none';
                }
            });
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

@endsection
