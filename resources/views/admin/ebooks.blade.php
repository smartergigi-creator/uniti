@extends('admin.layout')

@section('title', 'Ebook List')

@section('content')
    <div class="page-heading mb-4">
        <h3>Ebook List</h3>
        <p class="text-muted">All uploaded ebooks with uploader details</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Uploaded By</th>
                            <th>Uploaded Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ebooks as $ebook)
                            <tr>
                                <td>{{ $loop->iteration + ($ebooks->currentPage() - 1) * $ebooks->perPage() }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $ebook->title }}</div>
                                    <small class="text-muted">{{ $ebook->file_title }}</small>
                                </td>
                                <td>{{ $ebook->category->name ?? '-' }}</td>
                                <td>{{ $ebook->uploader->name ?? $ebook->uploadedByUser->name ?? '-' }}</td>
                                <td>{{ $ebook->created_at?->format('d-m-Y h:i A') }}</td>
                                <td>
                                    <a href="{{ url('/ebook/view/' . $ebook->id) }}" class="btn btn-sm btn-primary me-1" target="_blank" rel="noopener">
                                        Preview
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteEbook({{ $ebook->id }})">
                                        Delete
                                    </button>
                                </td>
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
