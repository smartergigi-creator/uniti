@extends('layout.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @include('layout.navbar')
    <div class="container">
        <!-- LOGOUT BUTTON -->


        {{-- <div class="logout-box">
            <a href="/logout" class="logout-btn">
                <img src="{{ asset('images/logout.png') }}" alt="Logout">
            </a>
        </div> --}}




        <!-- 🔥 REQUIRED: Hidden base URL for Share Modal -->
        <input type="hidden" id="baseShareUrl" value="{{ url('/ebook/public') }}">

        <h1 class="title">PDF Folder Upload</h1>
        <p class="subtitle">Upload folder or single PDF and convert to Flipbook</p>

        <!-- SUCCESS / ERROR MESSAGES -->
        {{-- @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif --}}
        <div id="messageBox" style="display:none;"></div>


        <!-- UPLOAD FORM -->
        <form id="uploadForm" enctype="multipart/form-data">

            <!-- EBOOK NAME -->
            <input type="text" name="ebook_name" class="ebook-input" placeholder="Enter Ebook Name" required>

            <!-- UPLOAD BOX -->
            <div class="upload-box" id="dropzone">

                <div class="upload-icon">📁</div>

                <p class="upload-text">
                    Drag & drop PDFs or folder here<br>
                    or click to select
                </p>

                <!-- SINGLE / MULTI PDF -->
                <input type="file" id="pdfInput" name="pdfs[]" multiple accept="application/pdf" style="display:none;">

                <!-- FOLDER UPLOAD -->
                <input type="file" id="folderInput" name="pdfs[]" webkitdirectory directory multiple
                    accept="application/pdf" style="display:none;">

                <button type="button" class="select-btn" id="selectFiles">
                    Select PDF(s)
                </button>

                <button type="button" class="select-btn" id="selectFolder">
                    Select Folder
                </button>

                <p class="upload-subtext">
                    Choose <strong>Select PDF(s)</strong> to upload one or more PDF files.<br>
                    Choose <strong>Select Folder</strong> to upload a folder containing PDF files.
                </p>

            </div>

            <!-- SELECTED FILES -->
            <div id="fileList" class="file-list" style="display:none;">

                <h3>
                    Selected Files (<span id="fileCount">0</span>)
                </h3>

                <ul id="fileItems"></ul>

                <button type="submit" class="upload-btn">
                    Upload & Save
                </button>

            </div>

        </form>


        <hr style="margin:40px 0;">

        <!-- TABLE LIST OF EBOOKS -->
        <h2 class="title">Uploaded eBooks</h2>

        <div class="table-wrapper">
            <div class="table-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Title</th>
                            <th>Folder</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php $groupNo = ($ebooks->currentPage() - 1) * $ebooks->perPage() + 1; @endphp

                        @foreach ($groupedEbooks as $manualTitle => $files)
                            {{-- 🔵 MANUAL TITLE ROW --}}
                            <tr class="ebook-group-row">
                                <td colspan="5">
                                    📘 {{ $groupNo }}. {{ $manualTitle }}
                                    <span class="file-count">
                                        ({{ $files->count() }} file{{ $files->count() > 1 ? 's' : '' }})
                                    </span>
                                </td>
                            </tr>

                            {{-- 📄 FILE ROWS --}}
                            @foreach ($files as $file)
                                <tr>
                                    <td></td>

                                    <td class="file-name">
                                        {{ $file->file_title }}
                                    </td>

                                    <td>{{ $file->folder_path }}</td>

                                    <td class="date-col">
                                        {{ $file->created_at->format('d-m-Y H:i A') }}
                                    </td>

                                    <td class="actions-col">

                                        <!-- VIEW -->
                                        <a href="/ebook/view/{{ $file->id }}" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-eye"></i> Preview
                                        </a>

                                        <!-- SHARE -->
                                        <button class="btn btn-info btn-sm" onclick="openShareModal({{ $file->id }})">
                                            <i class="fa-solid fa-share-nodes"></i> Share
                                        </button>

                                        <!-- DELETE -->
                                        <button class="btn btn-danger btn-sm" onclick="deleteEbook({{ $file->id }})">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>

                                    </td>
                                </tr>
                            @endforeach


                            @php $groupNo++; @endphp
                        @endforeach
                    </tbody>



                </table>
                <div class="pagination-wrapper">
                    {{ $ebooks->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

    <!-- SHARE MODAL -->
    <!-- SHARE MODAL -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Share Ebook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <label>Sharable Link:</label>

                    <input type="text" id="shareLinkInput" class="form-control" readonly>

                    <button class="btn btn-success mt-2" onclick="copyShareLink()">
                        Copy Link
                    </button>
                </div>

            </div>
        </div>
    </div>


    <!-- Include jQuery -->
    {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> --}}

    <!-- Include Turn.js -->
    {{-- <script src="{{ asset('js/turn.min.js') }}"></script> --}}

    <!-- Your Custom JS -->
    {{-- <script src="{{ asset('js/script.js') }}"></script> --}}
@endsection
