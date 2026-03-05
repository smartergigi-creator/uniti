@extends('admin.layout')

@section('title', 'Ebook List')

@section('content')
    <div class="page-heading mb-4">
        <h3>Ebook List</h3>
        <p class="text-muted">All uploaded ebooks with uploader details</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-3">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive ebooks-table-wrap">
                <table class="table table-striped table-hover align-middle mb-0 ebooks-table">
                    <thead class="table-light">
                        <tr>
                            <th class="sticky-action-col">Action</th>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Uploaded By</th>
                            <th>Uploaded Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ebooks as $ebook)
                            <tr>
                                <td class="sticky-action-col">
                                    <div class="action-compact-group">
                                        <a href="{{ route('admin.ebooks.edit', $ebook->id) }}" class="btn btn-sm btn-action-edit">
                                            Edit
                                        </a>
                                        <a href="{{ route('ebook.view', $ebook->slug) }}" class="btn btn-sm btn-action-preview"
                                            target="_blank" rel="noopener">
                                            Preview
                                        </a>
                                        <button type="button" class="btn btn-sm btn-action-delete"
                                            onclick="deleteEbook({{ $ebook->id }})">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                                <td>{{ $loop->iteration + ($ebooks->currentPage() - 1) * $ebooks->perPage() }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $ebook->title }}</div>
                                    <small class="text-muted">{{ $ebook->file_title }}</small>
                                </td>
                                <td>{{ $ebook->category->name ?? '-' }}</td>
                                <td>{{ $ebook->uploader->name ?? ($ebook->uploadedByUser->name ?? '-') }}</td>
                                <td>{{ $ebook->created_at?->format('d-m-Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No ebooks found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $ebooks->links('pagination::bootstrap-5') }}
    </div>
@endsection

<style>
    .ebooks-table-wrap {
        overflow-x: auto;
        overflow-y: visible;
    }

    .ebooks-table {
        min-width: 1050px;
    }

    .ebooks-table .sticky-action-col {
        position: sticky;
        left: 0;
        z-index: 4;
        min-width: 172px;
        white-space: nowrap;
        background: #f4fbff;
        box-shadow: 2px 0 0 rgba(222, 226, 230, 0.9);
    }

    .ebooks-table .action-compact-group {
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .ebooks-table .action-compact-group .btn {
        padding: 0.25rem 0.45rem;
    }

    .ebooks-table .btn-action-edit {
        background: #14c7bb;
        border-color: #14c7bb;
        color: #fff;
    }

    .ebooks-table .btn-action-edit:hover,
    .ebooks-table .btn-action-edit:focus {
        background: #10b5ab;
        border-color: #10b5ab;
        color: #fff;
    }

    .ebooks-table .btn-action-preview {
        background: #1aacc4;
        border-color: #1aacc4;
        color: #fff;
    }

    .ebooks-table .btn-action-preview:hover,
    .ebooks-table .btn-action-preview:focus {
        background: #16a9c1;
        border-color: #16a9c1;
        color: #fff;
    }

    .ebooks-table .btn-action-delete {
        background: #e78587;
        border-color: #e78587;
        color: #fff;
    }

    .ebooks-table .btn-action-delete:hover,
    .ebooks-table .btn-action-delete:focus {
        background: #de7275;
        border-color: #de7275;
        color: #fff;
    }

    .ebooks-table thead .sticky-action-col {
        z-index: 5;
        color: #0c4a6e !important;
        background: linear-gradient(180deg, #dcf4ff 0%, #cdeefe 100%) !important;
    }

    .ebooks-table.table-striped>tbody>tr:nth-of-type(odd)>* {
        --bs-table-accent-bg: #f8f9fa;
    }

    .ebooks-table.table-striped>tbody>tr:nth-of-type(odd)>.sticky-action-col {
        background: #eefdff;
    }

    .ebooks-table.table-hover>tbody>tr:hover>.sticky-action-col {
        background: #def3ff;
    }
</style>
