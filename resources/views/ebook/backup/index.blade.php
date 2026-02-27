@extends('layout.app')

@section('content')

<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<div class="container">

    <!-- 🔥 REQUIRED: Hidden base URL for Share Modal -->
    <input type="hidden" id="baseShareUrl" value="{{ url('/ebook/public') }}">

    <h1 class="title">PDF Folder Upload</h1>
    <p class="subtitle">Upload folder or single PDF and convert to Flipbook</p>

    <!-- SUCCESS / ERROR MESSAGES -->
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- UPLOAD FORM -->
    <form action="{{ route('ebooks.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <!-- EBOOK NAME -->
        <input type="text" name="ebook_name" class="ebook-input" placeholder="Enter Ebook Name" required>

        <!-- UPLOAD BOX -->
        <div class="upload-box" id="dropzone">
            <div class="upload-icon">📁</div>
            <p class="upload-text">Drag & drop PDFs or folder here<br>or click to select</p>

            <!-- HIDDEN INPUT -->
            {{-- <input type="file" id="folderInput" name="pdfs[]" webkitdirectory directory multiple style="display:none;"> --}}
<!-- SINGLE / MULTI PDF -->
<input type="file"
       id="pdfInput"
       name="pdfs[]"
       multiple
       accept="application/pdf"
       style="display:none;">

<!-- FOLDER UPLOAD -->
<input type="file"
       id="folderInput"
       name="pdfs[]"
       webkitdirectory
       directory
       multiple
       accept="application/pdf"
       style="display:none;">
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

            {{-- <button type="button" class="select-btn" id="selectFolder">Select Folder / PDFs</button> --}}
        </div>

        <!-- SELECTED FILES SECTION -->
        <div id="fileList" class="file-list" style="display:none;">
            <h3>Selected Files (<span id="fileCount">0</span>)</h3>
            <ul id="fileItems"></ul>

            <button type="submit" class="upload-btn">Upload & Save</button>
        </div>

    </form>

    <hr style="margin:40px 0;">

    <!-- TABLE LIST OF EBOOKS -->
    <h2 class="title">Uploaded eBooks</h2>

    <div class="table-wrapper">
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
                @foreach($ebooks as $ebook)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="name">{{ $ebook->title }}</td>
                    <td>{{ $ebook->folder_path }}</td>
                    <td>{{ $ebook->created_at->format('d-m-Y H:i A') }}</td>

                    <td>
                        <a href="{{ route('ebook.view', $ebook->id) }}" class="btn btn-primary btn-sm">View</a>

                        <!-- SHARE BUTTON -->
                        <button class="btn btn-sm btn-info" onclick="openShareModal({{ $ebook->id }})">
                            Share
                        </button>

                        <!-- DELETE -->
                        <form action="{{ route('ebook.delete', $ebook->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>

        </table>
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

        <input type="text"
               id="shareLinkInput"
               class="form-control"
               readonly>

        <button class="btn btn-success mt-2"
                onclick="copyShareLink()">
            Copy Link
        </button>
      </div>

    </div>
  </div>
</div>


<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Include Turn.js -->
<script src="{{ asset('js/turn.min.js') }}"></script>

<!-- Your Custom JS -->
<script src="{{ asset('js/script.js') }}"></script>

@endsection
