@extends('admin.layout')

@section('title', 'Category Management')

@section('content')
    <div class="admin-category-page">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h3 class="mb-1">Category Management</h3>
                <p class="text-muted mb-0">Create and organize main categories and sub-categories</p>
            </div>
            <button type="button" class="btn btn-primary category-add-btn" data-bs-toggle="modal"
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
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Parent Category</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($categories as $category)
                                        <tr>
                                            <td class="fw-semibold">{{ $category->id }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->parent?->name ?? '-' }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-category-btn"
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
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
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

                        <div class="category-steps-note mb-3">
                            <div class="note-title">Quick Flow</div>
                            <small>Step 1: Parent -> Step 2: Sub -> Step 3: Related Sub Category</small>
                        </div>

                        <form id="category-form" method="POST" action="{{ route('admin.categories.storeTree') }}">
                            @csrf
                            <div class="category-step mb-3">
                                <div class="step-title">Step 1 - Parent Category</div>
                                <small class="step-hint">Create a new parent or choose an existing parent below.</small>

                                <div class="mt-2">
                                    <label for="new_parent_name_modal" class="form-label">New Parent Category</label>
                                    <input id="new_parent_name_modal" name="new_parent_name" type="text"
                                        class="form-control" value="{{ old('new_parent_name') }}"
                                        placeholder="Ex: Department">
                                </div>

                                <div class="mt-2">
                                    <label for="parent_category_modal" class="form-label">Existing Parent Category</label>
                                    <select id="parent_category_modal" name="parent_category_id" class="form-select">
                                        <option value="">None</option>
                                        @foreach ($parentCategories as $parentCategory)
                                            <option value="{{ $parentCategory->id }}"
                                                @selected(old('parent_category_id') == $parentCategory->id)>{{ $parentCategory->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="category-step mb-3">
                                <div class="step-title">Step 2 - Sub Category</div>
                                <small class="step-hint">Pick the parent first, then add/select sub category.</small>

                                <div class="mt-2">
                                    <label for="new_sub_name_modal" class="form-label">New Sub Category</label>
                                    <input id="new_sub_name_modal" name="new_sub_name" type="text" class="form-control"
                                        value="{{ old('new_sub_name') }}" placeholder="Ex: Handbook">
                                </div>

                                <div class="mt-2">
                                    <label for="sub_category_modal" class="form-label">Existing Sub Category</label>
                                    <select id="sub_category_modal" name="sub_category_id" class="form-select" disabled>
                                        <option value="">Select parent category first</option>
                                    </select>
                                </div>
                            </div>

                            <div class="category-step mb-3">
                                <div class="step-title">Step 3 - Related Sub Category</div>
                                <small class="step-hint">Pick sub category first, then add related sub category.</small>

                                <div class="mt-2">
                                    <label for="new_related_name_modal" class="form-label">New Related Sub Category</label>
                                    <input id="new_related_name_modal" name="new_related_name" type="text"
                                        class="form-control" value="{{ old('new_related_name') }}"
                                        placeholder="Ex: Service Manuals">
                                </div>

                                <div class="mt-2">
                                    <label for="related_sub_category_modal" class="form-label">Existing Related Sub Category</label>
                                    <select id="related_sub_category_modal" class="form-select" disabled>
                                        <option value="">Select sub category first</option>
                                    </select>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-success px-4">
                                    Save Category
                                </button>
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
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Category</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const parentSelect = document.getElementById('parent_category_modal');
            const subSelect = document.getElementById('sub_category_modal');
            const relatedSelect = document.getElementById('related_sub_category_modal');
            const endpointBase = "{{ url('/get-subcategories') }}";
            const oldParentId = "{{ old('parent_category_id') }}";
            const oldSubId = "{{ old('sub_category_id') }}";
            const hasErrors = @json($errors->any());
            const openModal = @json(session('open_modal'));
            const editCategoryId = @json(session('edit_category_id'));
            const oldEditName = @json(old('name'));
            const oldEditParentId = @json(old('parent_id'));
            const addModalEl = document.getElementById('addCategoryModal');
            const editModalEl = document.getElementById('editCategoryModal');
            const addCategoryModal = (addModalEl && typeof bootstrap !== 'undefined') ? new bootstrap.Modal(addModalEl) :
                null;
            const editCategoryModal = (editModalEl && typeof bootstrap !== 'undefined') ? new bootstrap.Modal(editModalEl) :
                null;
            const editForm = document.getElementById('edit-category-form');
            const editNameInput = document.getElementById('edit_category_name');
            const editParentSelect = document.getElementById('edit_parent_id');

            function resetSelect(selectEl, placeholder, disable = true) {
                selectEl.innerHTML = `<option value="">${placeholder}</option>`;
                selectEl.disabled = disable;
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

            parentSelect.addEventListener('change', async function() {
                const parentId = this.value;
                resetSelect(relatedSelect, 'Select sub category first', true);

                if (!parentId) {
                    resetSelect(subSelect, 'Select parent category first', true);
                    return;
                }

                await loadChildren(parentId, subSelect, 'Choose sub category');
            });

            subSelect.addEventListener('change', async function() {
                const subId = this.value;
                if (!subId) {
                    resetSelect(relatedSelect, 'Select sub category first', true);
                    return;
                }

                await loadChildren(subId, relatedSelect, 'Choose related sub category');
            });

            if (oldParentId) {
                parentSelect.value = oldParentId;
                loadChildren(oldParentId, subSelect, 'Choose sub category').then(() => {
                    if (oldSubId) {
                        subSelect.value = oldSubId;
                        loadChildren(oldSubId, relatedSelect, 'Choose related sub category');
                    }
                });
            }

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
        })();
    </script>
@endpush
