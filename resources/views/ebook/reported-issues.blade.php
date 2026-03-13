@extends('layout.app')

@section('title', 'Reported Issues')
@section('body-class', 'ebook-issues')

@section('content')
    <section class="issues-shell">
        <div class="issues-hero">
            <div class="issues-hero-copy">
                <span class="issues-kicker">Assigned Issues</span>
                <h1>Reported Issues</h1>
                <p>All issue reports assigned to you will appear here in the card view.</p>
            </div>

            <div class="issues-hero-stats">
                <div class="issues-stat-card">
                    <span>Total Assigned</span>
                    <strong>{{ $openCount }}</strong>
                </div>
                <div class="issues-stat-card">
                    <span>Today</span>
                    <strong>{{ $todayCount }}</strong>
                </div>
            </div>
        </div>

        <div class="issues-toolbar">
            <form method="GET" action="{{ route('reported-issues.index') }}" class="issues-search-form">
                <div class="issues-search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" value="{{ $search }}"
                        placeholder="Search by ebook, reporter or issue text">
                </div>
                <button type="submit" class="btn btn-info">Search</button>
                @if ($search !== '')
                    <a href="{{ route('reported-issues.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>

        @if ($issues->isEmpty())
            <div class="issues-empty-state">
                <div class="issues-empty-icon">
                    <i class="bi bi-inbox"></i>
                </div>
                <h2>No assigned issues</h2>
                <p>Innum ungalukku report assign pannala, illa current filter-ku match aagura issue illa.</p>
            </div>
        @else
            <div class="issues-grid issues-board-grid">
                @foreach ($issues as $issue)
                    @php
                        $pageLabel =
                            (int) $issue->page > 1 ? $issue->page . '-' . ($issue->page + 1) : (string) $issue->page;
                        $initials = collect(explode(' ', trim($issue->reporter?->name ?? 'U')))
                            ->filter()
                            ->take(2)
                            ->map(fn($part) => strtoupper(substr($part, 0, 1)))
                            ->implode('');
                    @endphp
                    <article class="issue-card issue-tile" role="button" tabindex="0" data-bs-toggle="modal"
                        data-bs-target="#issueDetailModal" data-issue-id="#{{ $issue->id }}"
                        data-ebook-title="{{ $issue->ebook?->title ?? 'Unknown Ebook' }}"
                        data-page-label="Page {{ $pageLabel }}" data-description="{{ $issue->description }}"
                        data-reporter-name="{{ $issue->reporter?->name ?? 'Unknown User' }}"
                        data-reporter-email="{{ $issue->reporter?->email }}"
                        data-recipient-name="{{ $issue->recipient?->name ?? auth()->user()->name }}"
                        data-created-at="{{ $issue->created_at?->format('d M Y, h:i A') }}"
                        data-ebook-url="{{ $issue->ebook?->slug ? route('ebook.view', ['slug' => $issue->ebook->slug, 'page' => (int) $issue->page]) : '' }}">
                        <div class="issue-card-header">
                            <span class="issue-priority-chip">Medium</span>
                            <span class="issue-id-chip">#{{ $issue->id }}</span>
                        </div>

                        <div class="issue-tile-body">
                            <div class="issue-book-badge">
                                <span class="issue-book-icon">{{ $initials ?: 'U' }}</span>
                                <div>
                                    <span class="issue-meta-label">Ebook</span>
                                    <h3>{{ $issue->ebook?->title ?? 'Unknown Ebook' }}</h3>
                                </div>
                            </div>

                            <div class="issue-page-line">Page {{ $pageLabel }}</div>
                            <p class="issue-description">{{ \Illuminate\Support\Str::limit($issue->description, 78) }}</p>
                        </div>

                        <div class="issue-tile-meta">
                            <span>{{ $issue->reporter?->name ?? 'Unknown User' }}</span>
                            <span>{{ $issue->created_at?->format('d M Y') }}</span>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="issues-pagination">
                {{ $issues->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>

    <div class="modal fade" id="issueDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content issue-detail-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <span class="issue-detail-kicker" id="issueDetailId">#000</span>
                        <h4 class="modal-title mt-2 mb-1" id="issueDetailTitle">Issue details</h4>
                        <p class="issue-detail-page mb-0" id="issueDetailPage">Page 1</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="issue-detail-section">
                        <span class="issue-footer-label">Ebook</span>
                        <h5 id="issueDetailEbook" class="issue-detail-ebook mb-0">-</h5>
                    </div>

                    <div class="issue-detail-section">
                        <span class="issue-footer-label">Description</span>
                        <p id="issueDetailDescription" class="issue-detail-description mb-0"></p>
                    </div>

                    <div class="issue-detail-meta-grid">
                        <div class="issue-detail-meta-card">
                            <span class="issue-footer-label">Reported by</span>
                            <strong id="issueDetailReporter">-</strong>
                            <small id="issueDetailReporterEmail">-</small>
                        </div>
                        <div class="issue-detail-meta-card">
                            <span class="issue-footer-label">Assigned to</span>
                            <strong id="issueDetailRecipient">-</strong>
                            <small id="issueDetailCreatedAt">-</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <a href="#" id="issueDetailOpenLink" class="btn btn-info d-none">Open Ebook</a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('issueDetailModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                document.getElementById('issueDetailId').textContent = trigger.dataset.issueId || '#000';
                document.getElementById('issueDetailTitle').textContent = trigger.dataset.ebookTitle ||
                    'Issue details';
                document.getElementById('issueDetailEbook').textContent = trigger.dataset.ebookTitle || '-';
                document.getElementById('issueDetailPage').textContent = trigger.dataset.pageLabel || 'Page 1';
                document.getElementById('issueDetailDescription').textContent = trigger.dataset.description ||
                    '-';
                document.getElementById('issueDetailReporter').textContent = trigger.dataset.reporterName ||
                '-';
                document.getElementById('issueDetailReporterEmail').textContent = trigger.dataset
                    .reporterEmail || '-';
                document.getElementById('issueDetailRecipient').textContent = trigger.dataset.recipientName ||
                    '-';
                document.getElementById('issueDetailCreatedAt').textContent = trigger.dataset.createdAt || '-';

                const openLink = document.getElementById('issueDetailOpenLink');
                if (trigger.dataset.ebookUrl) {
                    openLink.href = trigger.dataset.ebookUrl;
                    openLink.classList.remove('d-none');
                } else {
                    openLink.href = '#';
                    openLink.classList.add('d-none');
                }
            });

            document.querySelectorAll('.issue-tile').forEach((tile) => {
                tile.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        tile.click();
                    }
                });
            });
        })();
    </script>
@endpush
