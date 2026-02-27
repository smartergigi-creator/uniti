@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')

    <div class="page-heading mb-4">
        <h3>User Management Dashboard</h3>
        <p class="text-muted">Manage user upload and share permissions</p>
    </div>

    <div class="page-content admin-dashboard">

        <section class="row justify-content-center">

            <div class="col-12">

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card shadow-sm border-0 h-100 stat-card stat-users">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small">Total Users</div>
                                        <div class="fs-4 fw-bold">{{ $totalUsers }}</div>
                                    </div>
                                    <i class="bi bi-people fs-2 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <a href="{{ route('admin.ebooks') }}" target="_blank" rel="noopener" class="text-decoration-none">
                            <div class="card shadow-sm border-0 h-100 stat-card stat-ebooks">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="text-muted small">Total Ebooks</div>
                                            <div class="fs-4 fw-bold">{{ $totalEbooks }}</div>
                                        </div>
                                        <i class="bi bi-book fs-2 text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <a href="{{ route('admin.todayUploads') }}" target="_blank" rel="noopener" class="text-decoration-none">
                            <div class="card shadow-sm border-0 h-100 stat-card stat-uploads">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="text-muted small">Today Uploads</div>
                                            <div class="fs-4 fw-bold">{{ $todayUploads }}</div>
                                        </div>
                                        <i class="bi bi-cloud-upload fs-2 text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card shadow-sm border-0 h-100 stat-card stat-expired">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="text-muted small">Expired Shares</div>
                                        <div class="fs-4 fw-bold">{{ $expiredShares }}</div>
                                    </div>
                                    <i class="bi bi-hourglass-split fs-2 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- User Permission Management -->
                <div class="card shadow-sm border-0 mb-4 admin-users-card">

                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">User Permission Management</h5>
                        <small>
                            Control upload and sharing limits
                        </small>
                    </div>


                    <div class="card-body p-0">

                        {{-- Success Message --}}
                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show m-3">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show m-3">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif


                        <div class="table-responsive admin-users-table-wrap">
                            <table class="table table-striped table-hover align-middle text-center mb-0 admin-users-table">

                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Upload</th>
                                    <th>Share</th>
                                    <th>Upload Limit</th>
                                    <th>Share Limit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($users as $user)
                                    @php
                                        $uploadLocked = $user->upload_limit_reached ?? false;
                                        $shareLocked = $user->share_limit_reached ?? false;
                                        $rowLocked = $uploadLocked || $shareLocked;
                                    @endphp
                                    <tr>
                                        <!-- Name -->
                                        <td class="fw-bold">
                                            {{ $user->name }}
                                        </td>

                                        <!-- Email -->
                                        <td class="text-muted">
                                            {{ $user->email }}
                                        </td>

                                        <!-- Role -->
                                        <td>
                                            <span class="badge bg-secondary px-3 py-2">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                        </td>

                                        <!-- Upload -->
                                        <td>
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" name="can_upload"
                                                    id="can-upload-{{ $user->id }}"
                                                    form="update-user-{{ $user->id }}"
                                                    data-upload-limit-target="upload-limit-{{ $user->id }}"
                                                    {{ $uploadLocked ? 'disabled' : '' }}
                                                    {{ $user->can_upload ? 'checked' : '' }}>
                                            </div>
                                        </td>

                                        <!-- Share -->
                                        <td>
                                            <div class="form-check form-switch d-flex justify-content-center">
                                                <input class="form-check-input" type="checkbox" name="can_share"
                                                    id="can-share-{{ $user->id }}"
                                                    form="update-user-{{ $user->id }}"
                                                    data-share-limit-target="share-limit-{{ $user->id }}"
                                                    {{ $shareLocked ? 'disabled' : '' }}
                                                    {{ $user->can_share ? 'checked' : '' }}>
                                            </div>
                                        </td>

                                        <!-- Upload Limit -->
                                        <td style="max-width:90px; {{ $uploadLocked ? 'cursor:not-allowed;' : '' }}"
                                            @if ($uploadLocked) onclick="alert('Upload limit reached. Please use Reset Uploads first, then set a new upload limit.')" @endif>
                                            <input type="number" name="upload_limit" min="0"
                                                id="upload-limit-{{ $user->id }}"
                                                form="update-user-{{ $user->id }}"
                                                class="form-control form-control-sm text-center"
                                                {{ $user->can_upload && !$uploadLocked ? '' : 'disabled' }}
                                                value="{{ old('upload_limit') !== null && old('id') == $user->id ? old('upload_limit') : $user->upload_limit }}">
                                            <small class="d-block mt-1 text-muted">
                                                Used {{ $user->upload_used ?? 0 }} /
                                                {{ (int) $user->upload_limit === 0 ? 'Unlimited' : (int) $user->upload_limit }}
                                            </small>
                                            @if ($user->upload_limit_reached ?? false)
                                                <span class="badge bg-danger mt-1">Upload Limit Reached</span>
                                            @endif
                                        </td>

                                        <!-- Share Limit -->
                                        <td style="max-width:90px; {{ $shareLocked ? 'cursor:not-allowed;' : '' }}"
                                            @if ($shareLocked) onclick="alert('Share limit reached. Please use Reset Shares first, then set a new share limit.')" @endif>
                                            <input type="number" name="share_limit" min="0"
                                                id="share-limit-{{ $user->id }}"
                                                form="update-user-{{ $user->id }}"
                                                class="form-control form-control-sm text-center"
                                                {{ $user->can_share && !$shareLocked ? '' : 'disabled' }}
                                                value="{{ $user->share_limit }}">
                                            <small class="d-block mt-1 text-muted">
                                                Used {{ $user->share_used ?? 0 }} /
                                                {{ (int) $user->share_limit === 0 ? 'Unlimited' : (int) $user->share_limit }}
                                            </small>
                                            @if (($user->share_reached ?? 0) > 0)
                                                <small class="d-block mt-1 text-danger fw-semibold">
                                                    Reached {{ $user->share_reached }} link(s)
                                                </small>
                                            @endif
                                            @if ($user->share_limit_reached ?? false)
                                                <span class="badge bg-danger mt-1">Share Limit Reached</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="action-wrapper">
                                                <form id="update-user-{{ $user->id }}" method="POST"
                                                    action="{{ route('admin.users.update', $user->id) }}">
                                                    @csrf
                                                </form>

                                                <!-- Save -->
                                                @if ($rowLocked)
                                                    <button type="button" class="btn btn-sm admin-action-btn admin-action-locked"
                                                        onclick="alert('Limit reached. Please use Reset Uploads / Reset Shares first, then set a new limit.')">
                                                        <i class="bi bi-lock me-1"></i>
                                                        Save
                                                    </button>
                                                @else
                                                    <button type="submit" form="update-user-{{ $user->id }}"
                                                        class="btn btn-sm admin-action-btn admin-action-save">
                                                        <i class="bi bi-check-circle me-1"></i>
                                                        Save
                                                    </button>
                                                @endif

                                                <!-- Reset -->
                                                <form method="POST" action="{{ route('admin.users.resetUploads', $user->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm admin-action-btn admin-action-reset-upload"
                                                        onclick="return confirm('Reset upload usage for this user? Existing ebooks will not be deleted.');">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                                                        Reset Uploads
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.users.resetShares', $user->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm admin-action-btn admin-action-reset-share"
                                                        onclick="return confirm('Reset all active shares for this user?');">
                                                        <i class="bi bi-link-45deg me-1"></i>
                                                        Reset Shares
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm admin-action-btn admin-action-delete"
                                                        onclick="return confirm('Delete this user and all related ebooks? This cannot be undone.');">
                                                        <i class="bi bi-trash me-1"></i>
                                                        Delete User
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>

                            </table>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            {{ $users->links('pagination::bootstrap-5') }}
                        </div>

                    </div>

                </div>
                <!-- End User Permission Management -->


            </div>

        </section>

    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadToggles = document.querySelectorAll('input[name="can_upload"][data-upload-limit-target]');
            const shareToggles = document.querySelectorAll('input[name="can_share"][data-share-limit-target]');

            const syncUploadLimitState = (toggle) => {
                const targetId = toggle.getAttribute('data-upload-limit-target');
                const limitInput = document.getElementById(targetId);

                if (!limitInput) return;
                limitInput.disabled = !toggle.checked;
            };

            const syncShareLimitState = (toggle) => {
                const targetId = toggle.getAttribute('data-share-limit-target');
                const limitInput = document.getElementById(targetId);

                if (!limitInput) return;
                limitInput.disabled = !toggle.checked;

                if (!toggle.checked) {
                    limitInput.value = 0;
                }
            };

            uploadToggles.forEach((toggle) => {
                syncUploadLimitState(toggle);
                toggle.addEventListener('change', () => syncUploadLimitState(toggle));
            });

            shareToggles.forEach((toggle) => {
                syncShareLimitState(toggle);
                toggle.addEventListener('change', () => syncShareLimitState(toggle));
            });
        });
    </script>
@endpush
