@extends('admin.layout')

@section('title', 'Ebooks')


@section('content')

    <div class="page-heading">
        <h3>PDF Folder Upload</h3>
        <p class="text-muted">Upload folder or single PDF and convert to Flipbook</p>
    </div>
    @if (auth()->check() && auth()->user()->role !== 'admin')
        <div class="container mt-3">
            <div class="card shadow-sm border-0" style="background: linear-gradient(135deg, #0d1b2a, #1b263b); color:#fff;">

                <div class="card-body d-flex flex-wrap justify-content-between align-items-center">

                    <!-- Share Limit -->
                    <div class="mb-1">
                        📢 <strong>Share Limit:</strong>
                        {{ auth()->user()->share_limit }}
                    </div>

                    <!-- Upload Limit -->
                    <div class="mb-1">
                        📂 <strong>Upload Limit:</strong>
                        @if (auth()->user()->can_upload && (int) auth()->user()->upload_limit === 0)
                            Unlimited
                        @else
                            {{ auth()->user()->upload_limit }} files
                        @endif
                    </div>

                </div>
            </div>
        </div>
    @endif

    <div class="page-content">

        <section class="row">

            <div class="col-12">




                <!-- Hidden base URL -->
                <input type="hidden" id="baseShareUrl" value="{{ url('/ebook/public') }}">
                @if (
                    auth()->check() &&
                        (auth()->user()->role === 'admin' || auth()->user()->can_upload)
                )

                    <div class="card shadow-sm border-0 rounded-4 mb-4 upload-card">

                        <div class="card-body p-4">

                            <h5 class="mb-4 section-title">ðŸ“˜ Upload New eBook</h5>

                            <form id="uploadForm" enctype="multipart/form-data">

                                <div class="row g-3 mb-4">

                                    <!-- Ebook Name -->
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Ebook Name</label>
                                        <input type="text" name="ebook_name" class="form-control custom-input"
                                            placeholder="Enter Ebook Name" required>
                                    </div>

                                    <!-- Category (Parent Only) -->
                                    <div class="col-md-3" id="categoryField"
                                        style="{{ $categories->count() > 0 ? '' : 'display:none;' }}">
                                        <label class="form-label fw-semibold">Category</label>
                                        <select name="category_id" id="categorySelect" class="form-select custom-input">
                                            <option value="">Select Category</option>

                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach

                                        </select>
                                    </div>

                                    <!-- Subcategory -->
                                    <div class="col-md-3" id="subCategoryField" style="display:none;">
                                        <label class="form-label fw-semibold">Sub Category</label>
                                        <select name="subcategory_id" id="subCategorySelect" class="form-select custom-input">
                                            <option value="">Select Subcategory</option>
                                        </select>
                                    </div>

                                    <!-- Related Subcategory (Level 3) -->
                                    <div class="col-md-3" id="relatedSubCategoryField" style="display:none;">
                                        <label class="form-label fw-semibold">Related Sub Category</label>
                                        <select name="related_subcategory_id" id="relatedSubCategorySelect"
                                            class="form-select custom-input">
                                            <option value="">Select Related Subcategory</option>
                                        </select>
                                    </div>

                                </div>

                                <!-- Upload Box -->
                                <div class="upload-box mb-4" id="dropzone">

                                    <div class="upload-icon mb-2">ðŸ“</div>

                                    <p class="upload-text text-center">
                                        Drag & drop PDFs or folder here <br>
                                        or click to select
                                    </p>

                                    <input type="file" id="pdfInput" name="pdfs[]" multiple accept="application/pdf"
                                        hidden>
                                    <input type="file" id="folderInput" name="pdfs[]" webkitdirectory directory multiple
                                        hidden>

                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-outline-primary me-2" id="selectFiles">
                                            Select PDF(s)
                                        </button>

                                        <button type="button" class="btn btn-outline-primary" id="selectFolder">
                                            Select Folder
                                        </button>
                                    </div>

                                </div>

                                <div id="fileList" class="border rounded p-3 mb-3" style="display:none;">
                                    <strong>Selected Files (<span id="fileCount">0</span>)</strong>
                                    <ul id="fileItems" class="mb-0 mt-2"></ul>
                                </div>

                                <div id="uploadStatus" class="upload-status mt-3 mb-3" style="display:none;">
                                    <span class="spinner"></span>
                                    <span class="text">Uploading ebook... Please wait</span>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        Upload & Save
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>


                @endif

                <!-- Ebook List Card -->
                <div class="card mt-4">

                    <div class="card-header">
                        <h4>Uploaded eBooks</h4>
                    </div>

                    <div class="card-body">

                        @if ($groupedEbooks->count() > 0)
                            {{-- <div class="table-responsive"> --}}
                            <div class="table-wrapper">
                                {{-- <table class="table table-hover"> --}}
                                <table class="table table-hover premium-table">


                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Title</th>
                                            <th>Folder</th>
                                            <th>Category</th>
                                            <th>Sub Category</th>
                                            <th>Date</th>
                                            <th>Upload By</th>
                                            <th>Shared By</th>
                                            <th>Actions</th>


                                        </tr>
                                    </thead>

                                    <tbody>

                                        @php
                                            $groupNo = ($ebooks->currentPage() - 1) * $ebooks->perPage() + 1;
                                        @endphp

                                        @foreach ($groupedEbooks as $manualTitle => $files)
                                            <!-- Group Title Row -->
                                            <tr class="table-primary">
                                                {{-- Adjust colspan based on total columns --}}
                                                <td colspan="9" class="fw-bold text-start">
                                                    📘 {{ $groupNo }}. {{ $manualTitle }}
                                                    ({{ $files->count() }} files)
                                                </td>
                                            </tr>

                                            <!-- Files -->
                                            @foreach ($files as $file)
                                                <tr>

                                                    <!-- S.No (optional) -->
                                                    <td></td>

                                                    <!-- File Title -->
                                                    <td>{{ $file->file_title }}</td>

                                                    <!-- Folder -->
                                                    <td>{{ $file->folder_path }}</td>

                                                    <!-- Category -->
                                                    <td>{{ $file->category->name ?? '-' }}</td>

                                                    <!-- Subcategory -->
                                                    <td>{{ $file->subcategory->name ?? '-' }}</td>

                                                    <!-- Created Date -->
                                                    <td class="text-nowrap" style="min-width:140px;">
                                                        {{ $file->created_at->format('d-m-Y h:i A') }}
                                                    </td>

                                                    <!-- Updated Date (Optional / Same as created) -->
                                                    {{-- <td>{{ $file->updated_at->format('d-m-Y h:i A') }}</td> --}}

                                                    <!-- Upload By -->
                                                    <td>
                                                        @if ($file->uploader)
                                                            <strong>{{ $file->uploader->serp_id }}</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                {{ $file->uploader->email }}
                                                            </small>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <!-- Shared By -->
                                                    <td>
                                                        @if ($file->sharedUser)
                                                            <strong>{{ $file->sharedUser->serp_id }}</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                {{ $file->sharedUser->email }}
                                                            </small>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>

                                                    <!-- Actions -->
                                                    <td class="text-nowrap">

                                                        <!-- Preview -->
                                                        <a href="/ebook/view/{{ $file->id }}"
                                                            class="btn btn-sm btn-primary action-btn me-1">
                                                            <i class="bi bi-eye"></i>
                                                            Preview
                                                        </a>

                                                        <!-- Share -->
                                                        @if (
                                                            auth()->user()->role === 'admin' ||
                                                                (auth()->user()->can_share &&
                                                                    (int) auth()->user()->share_limit > 0)
                                                        )
                                                            <button class="btn btn-sm btn-info action-btn"
                                                                onclick="openShareModal({{ $file->id }})">
                                                                <i class="bi bi-share"></i>
                                                                <span>Share</span>
                                                            </button>
                                                        @endif

                                                        @if (auth()->user()->role == 'admin')
                                                            <!-- Delete -->
                                                            <button class="btn btn-sm btn-danger action-btn"
                                                                onclick="deleteEbook({{ $file->id }})">
                                                                <i class="bi bi-trash"></i>
                                                                Delete
                                                            </button>
                                                        @endif
                                                    </td>

                                                </tr>
                                            @endforeach

                                            @php $groupNo++; @endphp
                                        @endforeach

                                    </tbody>


                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-3">
                                {{ $ebooks->links('pagination::bootstrap-5') }}
                            </div>
                        @else
                            <p class="text-muted mb-0">No eBooks yet. Add one to see the list here.</p>
                        @endif

                    </div>

                </div>

            </div>

        </section>

    </div>

@endsection

<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Share Ebook</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="shareLinkInput" class="form-control" readonly>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="copyShareLink()">Copy Link</button>
            </div>
        </div>
    </div>
</div>

