@extends('admin.layout')

@section('title', 'Category Management')

@section('content')
    <div class="admin-category-page">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="mb-1">Category Management</h3>
                <p class="text-muted mb-0">Create and organize main categories and sub-categories</p>
            </div>
            <button type="button" class="btn category-add-btn login-style-btn" data-bs-toggle="modal"
                data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg me-1"></i>
                Add New Category
            </button>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show auto-fade-alert" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-12">
                <div class="card category-card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0">Category List</h5>
                        <span class="badge bg-light text-dark">Total: {{ $categories->total() }}</span>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle category-table">
                                <thead>
                                    <tr>
                                        <th class="text-center sticky-action-col">Actions</th>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Parent Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($categories as $category)
                                        <tr>
                                            <td class="sticky-action-col">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-sm action-btn action-btn-edit edit-category-btn"
                                                        title="Edit" data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                        data-category-id="{{ $category->id }}"
                                                        data-category-name="{{ $category->name }}"
                                                        data-parent-id="{{ $category->parent_id ?? '' }}"
                                                        data-update-url="{{ route('admin.categories.update', $category->id) }}">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                    <form method="POST"
                                                        action="{{ route('admin.categories.delete', $category->id) }}"
                                                        onsubmit="return confirm('Delete this category? Child categories may also be removed.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm action-btn action-btn-delete"
                                                            title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="fw-semibold">{{ $category->id }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->parent?->name ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                No categories found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of
                                {{ $categories->total() }} entries
                            </small>
                            <div>
                                {{ $categories->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger py-2">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form id="category-form" method="POST" action="{{ route('admin.categories.storeTree') }}">
                            @csrf
                            <input type="hidden" id="wizard_mode" name="wizard_mode" value="{{ old('wizard_mode', 'create') }}">

                            <div class="category-wizard">
                                <div class="wizard-progress mb-3">
                                    <div class="wizard-progress-step active" data-step-marker="1">
                                        <span>1</span>
                                        <small>Action</small>
                                    </div>
                                    <div class="wizard-progress-step" data-step-marker="2">
                                        <span>2</span>
                                        <small>Parent</small>
                                    </div>
                                    <div class="wizard-progress-step" data-step-marker="3">
                                        <span>3</span>
                                        <small>Sub</small>
                                    </div>
                                    <div class="wizard-progress-step" data-step-marker="4">
                                        <span>4</span>
                                        <small>Related</small>
                                    </div>
                                </div>

                                <div id="wizardAlert" class="alert alert-danger py-2 d-none mb-3"></div>

                                <div class="wizard-panel active" data-step-panel="1">
                                    <div class="step-title">Step 1 - Choose Action</div>
                                    <small class="step-hint">Select how you want to add the new category flow.</small>

                                    <div class="wizard-mode-cards mt-3">
                                        <label class="wizard-mode-card">
                                            <input type="radio" name="wizard_mode_choice" value="create">
                                            <span class="wizard-mode-title">Create New Category Flow</span>
                                            <small>Create new parent/sub/related categories in one go.</small>
                                        </label>
                                        <label class="wizard-mode-card">
                                            <input type="radio" name="wizard_mode_choice" value="existing">
                                            <span class="wizard-mode-title">Add To Existing Category</span>
                                            <small>Choose existing parent/sub and add related category.</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="wizard-panel" data-step-panel="2">
                                    <div class="step-title">Step 2 - Select Parent Category</div>
                                    <small class="step-hint">Create new parent or choose existing parent category.</small>

                                    <div class="mt-3 wizard-mode-create-only">
                                        <label for="new_parent_name_modal" class="form-label">New Parent Category</label>
                                        <input id="new_parent_name_modal" name="new_parent_name" type="text"
                                            class="form-control" value="{{ old('new_parent_name') }}"
                                            placeholder="Ex: Department">
                                    </div>

                                    <div class="mt-3">
                                        <label for="parent_category_modal" class="form-label">Existing Parent Category</label>
                                        <select id="parent_category_modal" name="parent_category_id" class="form-select">
                                            <option value="">Select Parent Category</option>
                                            @foreach ($parentCategories as $parentCategory)
                                                <option value="{{ $parentCategory->id }}"
                                                    @selected(old('parent_category_id') == $parentCategory->id)>{{ $parentCategory->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="wizard-panel" data-step-panel="3">
                                    <div class="step-title">Step 3 - Select Sub Category</div>
                                    <small class="step-hint">Pick parent first, then create/select sub category.</small>

                                    <div class="mt-3">
                                        <label for="new_sub_name_modal" class="form-label">New Sub Category</label>
                                        <input id="new_sub_name_modal" name="new_sub_names[]" type="text" class="form-control"
                                            value="{{ old('new_sub_names.0', old('new_sub_name')) }}" placeholder="Ex: Handbook">
                                        <div id="newSubFields" class="mt-2 d-grid gap-2">
                                            @foreach (old('new_sub_names', []) as $idx => $oldSubName)
                                                @if ($idx > 0)
                                                    <div class="input-group extra-sub-field">
                                                        <input type="text" name="new_sub_names[]" class="form-control"
                                                            value="{{ $oldSubName }}" placeholder="Ex: Handbook">
                                                        <button type="button" class="btn theme-outline-btn remove-extra-field">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-sm theme-outline-btn mt-2" id="addSubFieldBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Add Field
                                        </button>
                                    </div>

                                    <div class="mt-3">
                                        <label for="sub_category_modal" class="form-label">Existing Sub Category</label>
                                        <select id="sub_category_modal" name="sub_category_id" class="form-select" disabled>
                                            <option value="">Select parent category first</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="wizard-panel" data-step-panel="4">
                                    <div class="step-title">Step 4 - Add Related Sub Category</div>
                                    <small class="step-hint">Pick sub category first, then create/select related sub category.</small>

                                    <div class="mt-3">
                                        <label class="form-label mb-2">New Related Sub Categories</label>
                                        <div class="related-row">
                                            <div class="related-row-head">
                                                <small class="text-muted">Related Name</small>
                                                <small class="text-muted">Under Sub Category</small>
                                            </div>
                                            <div class="related-row-grid">
                                                <input id="new_related_name_modal" name="new_related_names[]" type="text"
                                                    class="form-control" value="{{ old('new_related_names.0', old('new_related_name')) }}"
                                                    placeholder="Ex: Service Manuals">
                                                <select class="form-select related-parent-sub-select" name="related_parent_subs[]"
                                                    data-old-value="{{ old('related_parent_subs.0', old('related_parent_sub')) }}" disabled>
                                                    <option value="">Select sub category</option>
                                                </select>
                                            </div>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            Each related row can be mapped to a different sub category.
                                        </small>
                                        <div id="newRelatedFields" class="mt-2 d-grid gap-2">
                                            @foreach (old('new_related_names', []) as $idx => $oldRelatedName)
                                                @if ($idx > 0)
                                                    <div class="related-row extra-related-field">
                                                        <div class="related-row-grid">
                                                            <input type="text" name="new_related_names[]" class="form-control"
                                                                value="{{ $oldRelatedName }}" placeholder="Ex: Service Manuals">
                                                            <select class="form-select related-parent-sub-select"
                                                                name="related_parent_subs[]"
                                                                data-old-value="{{ old('related_parent_subs.' . $idx) }}" disabled>
                                                                <option value="">Select sub category</option>
                                                            </select>
                                                        </div>
                                                        <button type="button" class="btn theme-outline-btn remove-related-row mt-2">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        <button type="button" class="btn btn-sm theme-outline-btn mt-2" id="addRelatedFieldBtn">
                                            <i class="bi bi-plus-lg me-1"></i> Add Field
                                        </button>
                                    </div>

                                    <div class="mt-3">
                                        <label for="related_sub_category_modal" class="form-label">Existing Related Sub Category</label>
                                        <select id="related_sub_category_modal" class="form-select" disabled>
                                            <option value="">Select sub category first</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="wizard-footer d-flex justify-content-between align-items-center mt-4">
                                    <button type="button" class="btn theme-outline-btn px-4" data-bs-dismiss="modal">
                                        Cancel
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" id="wizardBackBtn" class="btn theme-outline-btn px-4">
                                            Back
                                        </button>
                                        <button type="button" id="wizardNextBtn" class="btn px-4 login-style-btn">
                                            Next
                                        </button>
                                        <button type="submit" id="wizardSaveBtn" class="btn px-4 login-style-btn">
                                            Finish
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="edit-category-form" method="POST" action="">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_category_name" class="form-label">Category Name</label>
                                <input type="text" id="edit_category_name" name="name" class="form-control"
                                    value="{{ old('name') }}" required>
                            </div>

                            <div class="mb-2">
                                <label for="edit_parent_id" class="form-label">Parent Category</label>
                                <select id="edit_parent_id" name="parent_id" class="form-select">
                                    <option value="">None</option>
                                    @foreach ($allCategories as $availableCategory)
                                        <option value="{{ $availableCategory->id }}"
                                            @selected((string) old('parent_id') === (string) $availableCategory->id)>
                                            {{ $availableCategory->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <small class="text-muted">Use None for top-level parent category.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn theme-outline-btn" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn login-style-btn">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

<style>
    .admin-category-page .table-responsive {
        overflow-x: auto;
        overflow-y: visible;
    }

    .admin-category-page .category-table {
        min-width: 760px;
    }

    .admin-category-page .category-table .sticky-action-col {
        position: sticky;
        left: 0;
        z-index: 4;
        min-width: 140px;
        background: #f4fbff;
        box-shadow: 2px 0 0 rgba(215, 230, 238, 0.95);
    }

    .admin-category-page .category-table thead .sticky-action-col {
        z-index: 5;
        color: #0c4a6e !important;
        background: linear-gradient(180deg, #dcf4ff 0%, #cdeefe 100%) !important;
    }

    .admin-category-page .category-table tbody tr:nth-child(even) .sticky-action-col {
        background: #eefdff;
    }

    .admin-category-page .category-table tbody tr:hover .sticky-action-col {
        background: #def3ff !important;
    }

    .admin-category-page .category-table .action-btn {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-width: 1px;
    }

    .admin-category-page .category-table .action-btn i {
        font-size: 14px;
    }

    .admin-category-page .category-table .action-btn-edit {
        color: #0f8198;
        border-color: #7cdce8;
        background: #ebfbff;
    }

    .admin-category-page .category-table .action-btn-edit:hover {
        color: #ffffff;
        border-color: #0f8fa9;
        background: #0f8fa9;
    }

    .admin-category-page .category-table .action-btn-delete {
        color: #0f8198;
        border-color: #7cdce8;
        background: #ebfbff;
    }

    .admin-category-page .category-table .action-btn-delete:hover {
        color: #ffffff;
        border-color: #0b7388;
        background: #0b7388;
    }

    .admin-category-page {
        --theme-btn-start: #0ea4c3;
        --theme-btn-end: #62d9e4;
        --theme-btn-start-hover: #0d9ab7;
        --theme-btn-end-hover: #58cfdd;
        --theme-btn-shadow: rgba(24, 132, 158, 0.3);
        --theme-outline-bg: #f1fbfe;
        --theme-outline-border: #7bdbe8;
        --theme-outline-text: #117f99;
    }

    .admin-category-page .login-style-btn {
        border: none !important;
        border-radius: 999px;
        font-weight: 600;
        color: #f6fcff !important;
        background: linear-gradient(90deg, var(--theme-btn-start) 0%, var(--theme-btn-end) 100%);
        box-shadow: 0 14px 24px var(--theme-btn-shadow);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .admin-category-page .login-style-btn:hover,
    .admin-category-page .login-style-btn:focus {
        color: #f6fcff !important;
        transform: translateY(-1px);
        box-shadow: 0 18px 28px rgba(24, 132, 158, 0.35);
        background: linear-gradient(90deg, var(--theme-btn-start-hover) 0%, var(--theme-btn-end-hover) 100%);
    }

    .admin-category-page .theme-outline-btn {
        border-radius: 12px;
        border: 1px solid var(--theme-outline-border);
        background: var(--theme-outline-bg);
        color: var(--theme-outline-text);
        font-weight: 600;
        transition: all 0.2s ease;
    }

    .admin-category-page .theme-outline-btn:hover,
    .admin-category-page .theme-outline-btn:focus {
        border-color: #31b6cc;
        background: #dff6fb;
        color: #0d6f85;
    }

    .admin-category-page .category-wizard {
        background: linear-gradient(180deg, #fbfdff 0%, #f4f9ff 100%);
        border: 1px solid #d9e7f7;
        border-radius: 16px;
        padding: 18px;
    }

    .admin-category-page .wizard-progress {
        display: flex;
        justify-content: space-between;
        gap: 8px;
        padding: 0 4px;
    }

    .admin-category-page .wizard-progress-step {
        flex: 1;
        text-align: center;
        position: relative;
        opacity: 0.7;
    }

    .admin-category-page .wizard-progress-step span {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid #0f8fa9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #f6fcff;
        background: linear-gradient(90deg, var(--theme-btn-start) 0%, var(--theme-btn-end) 100%);
        box-shadow: 0 10px 18px rgba(24, 132, 158, 0.22);
    }

    .admin-category-page .wizard-progress-step small {
        display: block;
        margin-top: 4px;
        color: #44608f;
        font-size: 12px;
        font-weight: 600;
    }

    .admin-category-page .wizard-progress-step:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 18px;
        left: calc(50% + 20px);
        width: calc(100% - 40px);
        height: 2px;
        background: color-mix(in srgb, var(--theme-btn-start) 35%, #ffffff 65%);
    }

    .admin-category-page .wizard-progress-step.active,
    .admin-category-page .wizard-progress-step.completed {
        opacity: 1;
    }

    .admin-category-page .wizard-progress-step.active span,
    .admin-category-page .wizard-progress-step.completed span {
        border-color: #0f8fa9;
        background: linear-gradient(90deg, var(--theme-btn-start) 0%, var(--theme-btn-end) 100%);
        color: #ffffff;
        box-shadow: 0 12px 20px rgba(24, 132, 158, 0.3);
    }

    .admin-category-page .wizard-progress-step.completed:not(:last-child)::after,
    .admin-category-page .wizard-progress-step.active:not(:last-child)::after {
        background: linear-gradient(90deg, var(--theme-btn-start) 0%, var(--theme-btn-end) 100%);
    }

    .admin-category-page .wizard-panel {
        display: none;
        background: #ffffff;
        border: 1px solid #dbe7f5;
        border-radius: 14px;
        padding: 16px;
    }

    .admin-category-page .wizard-panel.active {
        display: block;
    }

    .admin-category-page .wizard-mode-cards {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .admin-category-page .wizard-mode-card {
        border: 1px solid #d5e2f3;
        border-radius: 12px;
        background: #f8fbff;
        padding: 12px 14px;
        display: block;
        cursor: pointer;
    }

    .admin-category-page .wizard-mode-card input {
        margin-right: 8px;
    }

    .admin-category-page .wizard-mode-title {
        font-weight: 700;
        color: #1d3557;
    }

    .admin-category-page .wizard-mode-card small {
        display: block;
        margin-top: 6px;
        color: #5d6f88;
    }

    .admin-category-page .wizard-mode-create-only.is-hidden {
        display: none;
    }

    .admin-category-page .related-row {
        border: 1px solid #d7e7f4;
        border-radius: 12px;
        background: #f9fcff;
        padding: 10px;
    }

    .admin-category-page .related-row-head {
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 10px;
        margin-bottom: 8px;
        padding: 0 2px;
    }

    .admin-category-page .related-row-grid {
        display: grid;
        grid-template-columns: 1.3fr 1fr;
        gap: 10px;
        align-items: start;
    }

    .admin-category-page .remove-related-row {
        width: 42px;
        min-width: 42px;
        border-radius: 10px;
        padding: 0;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .admin-category-page .wizard-footer {
        border-top: 1px solid #d8e4f3;
        padding-top: 14px;
    }

    @media (max-width: 767.98px) {
        .admin-category-page .wizard-mode-cards {
            grid-template-columns: 1fr;
        }

        .admin-category-page .wizard-progress-step small {
            display: none;
        }

        .admin-category-page .wizard-footer {
            flex-direction: column;
            align-items: stretch !important;
            gap: 10px;
        }

        .admin-category-page .wizard-footer>div {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .admin-category-page .related-row-head {
            display: none;
        }

        .admin-category-page .related-row-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

@push('scripts')
    <script>
        (function() {
            const parentSelect = document.getElementById('parent_category_modal');
            const subSelect = document.getElementById('sub_category_modal');
            const relatedSelect = document.getElementById('related_sub_category_modal');
            const endpointBase = "{{ url('/get-subcategories') }}";
            const newSubFields = document.getElementById('newSubFields');
            const newRelatedFields = document.getElementById('newRelatedFields');
            const addSubFieldBtn = document.getElementById('addSubFieldBtn');
            const addRelatedFieldBtn = document.getElementById('addRelatedFieldBtn');
            const categoryForm = document.getElementById('category-form');
            const newParentInput = document.getElementById('new_parent_name_modal');
            const oldParentId = "{{ old('parent_category_id') }}";
            const oldSubId = "{{ old('sub_category_id') }}";
            const oldRelatedParentSub = @json(old('related_parent_sub'));
            const hasErrors = @json($errors->any());
            const openModal = @json(session('open_modal'));
            const editCategoryId = @json(session('edit_category_id'));
            const oldEditName = @json(old('name'));
            const oldEditParentId = @json(old('parent_id'));
            const addModalEl = document.getElementById('addCategoryModal');
            const editModalEl = document.getElementById('editCategoryModal');
            const oldErrorKeys = @json($errors->keys());
            const oldWizardMode = @json(old('wizard_mode'));
            const addCategoryModal = (addModalEl && typeof bootstrap !== 'undefined') ? new bootstrap.Modal(addModalEl) :
                null;
            const editCategoryModal = (editModalEl && typeof bootstrap !== 'undefined') ? new bootstrap.Modal(editModalEl) :
                null;
            const editForm = document.getElementById('edit-category-form');
            const editNameInput = document.getElementById('edit_category_name');
            const editParentSelect = document.getElementById('edit_parent_id');
            const wizardPanels = Array.from(document.querySelectorAll('[data-step-panel]'));
            const wizardMarkers = Array.from(document.querySelectorAll('[data-step-marker]'));
            const wizardBackBtn = document.getElementById('wizardBackBtn');
            const wizardNextBtn = document.getElementById('wizardNextBtn');
            const wizardSaveBtn = document.getElementById('wizardSaveBtn');
            const wizardModeInput = document.getElementById('wizard_mode');
            const wizardAlert = document.getElementById('wizardAlert');
            const wizardModeRadios = Array.from(document.querySelectorAll('input[name="wizard_mode_choice"]'));
            const newSubPrimaryInput = document.getElementById('new_sub_name_modal');

            let currentStep = 1;

            function showWizardAlert(message) {
                if (!wizardAlert) return;
                wizardAlert.textContent = message;
                wizardAlert.classList.remove('d-none');
            }

            function hideWizardAlert() {
                if (!wizardAlert) return;
                wizardAlert.textContent = '';
                wizardAlert.classList.add('d-none');
            }

            function setWizardMode(mode) {
                const normalizedMode = mode === 'existing' ? 'existing' : 'create';

                if (wizardModeInput) {
                    wizardModeInput.value = normalizedMode;
                }

                wizardModeRadios.forEach((radio) => {
                    radio.checked = radio.value === normalizedMode;
                });

                document.querySelectorAll('.wizard-mode-create-only').forEach((section) => {
                    const shouldHide = normalizedMode === 'existing';
                    section.classList.toggle('is-hidden', shouldHide);
                    section.querySelectorAll('input, select, textarea, button').forEach((field) => {
                        field.disabled = shouldHide;
                    });
                });
            }

            function setWizardStep(step) {
                const safeStep = Math.max(1, Math.min(4, step));
                currentStep = safeStep;
                hideWizardAlert();

                wizardPanels.forEach((panel) => {
                    panel.classList.toggle('active', Number(panel.dataset.stepPanel) === safeStep);
                });

                wizardMarkers.forEach((marker) => {
                    const markerStep = Number(marker.dataset.stepMarker);
                    marker.classList.toggle('active', markerStep === safeStep);
                    marker.classList.toggle('completed', markerStep < safeStep);
                });

                if (wizardBackBtn) {
                    wizardBackBtn.classList.toggle('d-none', safeStep === 1);
                }
                if (wizardNextBtn) {
                    wizardNextBtn.classList.toggle('d-none', safeStep === 4);
                }
                if (wizardSaveBtn) {
                    wizardSaveBtn.classList.toggle('d-none', safeStep !== 4);
                }
            }

            function fieldHasError(name) {
                return Array.isArray(oldErrorKeys) && oldErrorKeys.some((key) => key === name || key.startsWith(`${name}.`));
            }

            function determineInitialStep() {
                if (fieldHasError('new_related_names') || fieldHasError('new_related_name')) return 4;
                if (fieldHasError('new_sub_names') || fieldHasError('new_sub_name') || fieldHasError('sub_category_id')) return 3;
                if (fieldHasError('parent_category_id') || fieldHasError('new_parent_name')) return 2;
                return 1;
            }

            function determineInitialMode() {
                if (oldWizardMode === 'existing' || oldWizardMode === 'create') {
                    return oldWizardMode;
                }

                return 'create';
            }

            function validateCurrentStep() {
                const mode = wizardModeInput?.value || 'create';
                const hasNewSub = getNewSubValues().length > 0;
                const hasExistingSub = !!subSelect?.value;
                const hasParentContext = !!parentSelect?.value || !!newParentInput?.value?.trim();

                if (currentStep === 2 && mode === 'existing' && !parentSelect?.value) {
                    showWizardAlert('Please select a parent category to continue.');
                    return false;
                }

                if (currentStep === 3 && !hasParentContext) {
                    showWizardAlert('Please select existing parent category or enter new parent category first.');
                    return false;
                }

                if (currentStep === 3 && !hasExistingSub && !hasNewSub) {
                    showWizardAlert('Please select a sub category or enter new sub category to continue.');
                    return false;
                }

                return true;
            }

            function resetSelect(selectEl, placeholder, disable = true) {
                selectEl.innerHTML = `<option value="">${placeholder}</option>`;
                selectEl.disabled = disable;
            }

            function getTextInputsByName(name) {
                return Array.from(document.querySelectorAll(`input[name="${name}"]`))
                    .map((input) => (input.value || '').trim())
                    .filter((value) => value !== '');
            }

            function getNewSubValues() {
                return getTextInputsByName('new_sub_names[]');
            }

            function getNewRelatedValues() {
                return getTextInputsByName('new_related_names[]');
            }

            function getRelatedParentSubSelects() {
                return Array.from(document.querySelectorAll('.related-parent-sub-select'));
            }

            function getRelatedRows() {
                const firstRow = document.querySelector('.wizard-panel[data-step-panel="4"] .related-row');
                const extraRows = Array.from(document.querySelectorAll('#newRelatedFields .related-row'));
                return [firstRow, ...extraRows].filter(Boolean);
            }

            function buildRelatedParentOptions() {
                const options = [];
                const seen = new Set();

                const selectedSubText = subSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
                if (subSelect?.value) {
                    const value = `existing:${subSelect.value}`;
                    options.push({
                        value,
                        label: `Existing - ${selectedSubText || 'Selected Sub Category'}`
                    });
                    seen.add(value);
                }

                getNewSubValues().forEach((name) => {
                    const value = `new:${name}`;
                    if (seen.has(value)) return;
                    options.push({
                        value,
                        label: `New - ${name}`
                    });
                    seen.add(value);
                });

                return options;
            }

            function updateRelatedParentSubChoices() {
                const selectElements = getRelatedParentSubSelects();
                if (!selectElements.length) return;

                const options = buildRelatedParentOptions();

                selectElements.forEach((selectEl) => {
                    const oldValue = selectEl.getAttribute('data-old-value') || '';
                    const previousValue = selectEl.value || oldValue || oldRelatedParentSub || '';

                    if (!options.length) {
                        resetSelect(selectEl, 'Select sub category', true);
                        return;
                    }

                    selectEl.innerHTML = '<option value="">Select sub category</option>';
                    options.forEach((item) => {
                        const option = document.createElement('option');
                        option.value = item.value;
                        option.textContent = item.label;
                        selectEl.appendChild(option);
                    });
                    selectEl.disabled = false;

                    const hasPrevious = options.some((item) => item.value === previousValue);
                    selectEl.value = hasPrevious ? previousValue : options[0].value;
                });
            }

            function setEditModalData(data) {
                if (!editForm) return;
                editForm.action = data.updateUrl || '';
                editNameInput.value = data.name || '';
                editParentSelect.value = data.parentId || '';
            }

            async function loadChildren(parentId, targetSelect, placeholder) {
                resetSelect(targetSelect, 'Loading...', true);

                try {
                    const response = await fetch(`${endpointBase}/${parentId}`);
                    if (!response.ok) throw new Error('Failed to load');

                    const items = await response.json();
                    targetSelect.innerHTML = `<option value="">${placeholder}</option>`;

                    if (!Array.isArray(items) || items.length === 0) {
                        targetSelect.innerHTML = `<option value="">No options found</option>`;
                        targetSelect.disabled = true;
                        return;
                    }

                    items.forEach((item) => {
                        const option = document.createElement('option');
                        option.value = item.id;
                        option.textContent = item.name;
                        targetSelect.appendChild(option);
                    });

                    targetSelect.disabled = false;
                } catch (error) {
                    resetSelect(targetSelect, 'Unable to load options', true);
                }
            }

            parentSelect?.addEventListener('change', async function() {
                const parentId = this.value;
                resetSelect(relatedSelect, 'Select sub category first', true);
                updateRelatedParentSubChoices();

                if (!parentId) {
                    resetSelect(subSelect, 'Select parent category first', true);
                    return;
                }

                await loadChildren(parentId, subSelect, 'Choose sub category');
            });

            subSelect?.addEventListener('change', async function() {
                const subId = this.value;
                updateRelatedParentSubChoices();
                if (!subId) {
                    resetSelect(relatedSelect, 'Select sub category first', true);
                    return;
                }

                await loadChildren(subId, relatedSelect, 'Choose related sub category');
            });

            if (oldParentId && parentSelect) {
                parentSelect.value = oldParentId;
                loadChildren(oldParentId, subSelect, 'Choose sub category').then(() => {
                    if (oldSubId) {
                        subSelect.value = oldSubId;
                        updateRelatedParentSubChoices();
                        loadChildren(oldSubId, relatedSelect, 'Choose related sub category');
                    }
                });
            }

            newSubPrimaryInput?.addEventListener('input', updateRelatedParentSubChoices);
            newSubFields?.addEventListener('input', updateRelatedParentSubChoices);

            document.querySelectorAll('.edit-category-btn').forEach((btn) => {
                btn.addEventListener('click', () => {
                    setEditModalData({
                        updateUrl: btn.dataset.updateUrl,
                        name: btn.dataset.categoryName,
                        parentId: btn.dataset.parentId,
                    });
                });
            });

            if (hasErrors && openModal === 'add' && addCategoryModal) {
                addCategoryModal.show();
            }

            if (hasErrors && openModal === 'edit' && editCategoryModal && editCategoryId) {
                const targetBtn = document.querySelector(
                    `.edit-category-btn[data-category-id="${editCategoryId}"]`
                );
                if (targetBtn) {
                    setEditModalData({
                        updateUrl: targetBtn.dataset.updateUrl,
                        name: oldEditName || targetBtn.dataset.categoryName,
                        parentId: oldEditParentId || targetBtn.dataset.parentId,
                    });
                    editCategoryModal.show();
                }
            }

            wizardModeRadios.forEach((radio) => {
                radio.addEventListener('change', function() {
                    setWizardMode(this.value);
                    hideWizardAlert();
                });
            });

            wizardNextBtn?.addEventListener('click', function() {
                if (!validateCurrentStep()) return;
                setWizardStep(currentStep + 1);
            });

            wizardBackBtn?.addEventListener('click', function() {
                setWizardStep(currentStep - 1);
            });

            // Auto close success alerts after a few seconds
            document.querySelectorAll('.auto-fade-alert').forEach((alertEl) => {
                setTimeout(() => {
                    if (typeof bootstrap !== 'undefined') {
                        bootstrap.Alert.getOrCreateInstance(alertEl).close();
                    } else {
                        alertEl.style.transition = 'opacity 0.4s ease';
                        alertEl.style.opacity = '0';
                        setTimeout(() => alertEl.remove(), 400);
                    }
                }, 3500);
            });

            function buildExtraField(name, placeholder, wrapperClass) {
                const wrapper = document.createElement('div');
                wrapper.className = `input-group ${wrapperClass}`;
                wrapper.innerHTML = `
                    <input type="text" name="${name}" class="form-control" placeholder="${placeholder}">
                    <button type="button" class="btn theme-outline-btn remove-extra-field">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;

                wrapper.querySelector('.remove-extra-field').addEventListener('click', () => {
                    wrapper.remove();
                });

                return wrapper;
            }

            addSubFieldBtn?.addEventListener('click', function() {
                newSubFields.appendChild(buildExtraField('new_sub_names[]', 'Ex: Handbook', 'extra-sub-field'));
                updateRelatedParentSubChoices();
            });

            addRelatedFieldBtn?.addEventListener('click', function() {
                const wrapper = document.createElement('div');
                wrapper.className = 'related-row extra-related-field';
                wrapper.innerHTML = `
                    <div class="related-row-grid">
                        <input type="text" name="new_related_names[]" class="form-control" placeholder="Ex: Service Manuals">
                        <select class="form-select related-parent-sub-select" name="related_parent_subs[]" disabled>
                            <option value="">Select sub category</option>
                        </select>
                    </div>
                    <button type="button" class="btn theme-outline-btn remove-related-row mt-2">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                newRelatedFields.appendChild(wrapper);
                updateRelatedParentSubChoices();
            });

            document.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove-related-row');
                if (removeBtn) {
                    removeBtn.closest('.related-row')?.remove();
                    updateRelatedParentSubChoices();
                    return;
                }

                const oldRemoveBtn = e.target.closest('.remove-extra-field');
                if (oldRemoveBtn) {
                    oldRemoveBtn.closest('.input-group')?.remove();
                    updateRelatedParentSubChoices();
                }
            });

            categoryForm?.addEventListener('submit', function(e) {
                const hasInvalidMapping = getRelatedRows().some((row) => {
                    const nameInput = row.querySelector('input[name="new_related_names[]"]');
                    const mapSelect = row.querySelector('.related-parent-sub-select');
                    const hasName = !!nameInput?.value?.trim();
                    if (!hasName) return false;
                    return !mapSelect?.value;
                });

                if (hasInvalidMapping) {
                    e.preventDefault();
                    setWizardStep(4);
                    showWizardAlert('Please choose which sub category to place related sub category under.');
                }
            });

            setWizardMode(determineInitialMode());
            setWizardStep(hasErrors ? determineInitialStep() : 1);
            updateRelatedParentSubChoices();
        })();
    </script>
@endpush
