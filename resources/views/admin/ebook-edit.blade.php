@extends('admin.layout')

@section('title', 'Edit Ebook')

@section('content')
    <div class="page-heading mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h3 class="mb-1">Edit Ebook</h3>
            <p class="text-muted mb-0">Update ebook details and category mapping</p>
        </div>
        <a href="{{ route('admin.ebooks') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $subLevelCategories = $subcategories->whereIn('parent_id', $categories->pluck('id'));
        $relatedLevelCategories = $subcategories->whereIn('parent_id', $subLevelCategories->pluck('id'));
    @endphp

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('admin.ebooks.update', $ebook->id) }}" method="POST" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Ebook Title</label>
                    <input type="text" name="title" class="form-control" maxlength="255"
                        value="{{ old('title', $ebook->title) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Author Name</label>
                    <input type="text" name="author_name" class="form-control" maxlength="255"
                        value="{{ old('author_name', $ebook->author_name) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">File Title</label>
                    <input type="text" name="file_title" class="form-control" maxlength="255"
                        value="{{ old('file_title', $ebook->file_title) }}" required>
                </div>

                <div class="col-12 pt-1">
                    <div class="category-separator">
                        <span>Category Mapping</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Category</label>
                    <select name="category_id" id="categorySelect" class="form-select">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                @selected((int) old('category_id', $ebook->category_id) === (int) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="subcategoryField">
                    <label class="form-label fw-semibold">Subcategory</label>
                    <select name="subcategory_id" id="subcategorySelect" class="form-select">
                        <option value="">Select subcategory</option>
                        @foreach ($subLevelCategories as $subcategory)
                            <option value="{{ $subcategory->id }}" data-parent="{{ $subcategory->parent_id }}"
                                @selected((int) old('subcategory_id', $ebook->subcategory_id) === (int) $subcategory->id)>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6" id="relatedSubcategoryField">
                    <label class="form-label fw-semibold">Related Subcategory</label>
                    <select name="related_subcategory_id" id="relatedSubcategorySelect" class="form-select">
                        <option value="">Select related subcategory</option>
                        @foreach ($relatedLevelCategories as $relatedSubcategory)
                            <option value="{{ $relatedSubcategory->id }}" data-parent="{{ $relatedSubcategory->parent_id }}"
                                @selected((int) old('related_subcategory_id', $ebook->related_subcategory_id) === (int) $relatedSubcategory->id)>
                                {{ $relatedSubcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 d-flex gap-2 pt-2">
                    <button type="submit" class="btn btn-primary">Update Ebook</button>
                    <a href="{{ route('admin.ebooks') }}" class="btn btn-light border">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('categorySelect');
            const subcategorySelect = document.getElementById('subcategorySelect');
            const relatedSubcategorySelect = document.getElementById('relatedSubcategorySelect');
            const subcategoryField = document.getElementById('subcategoryField');
            const relatedSubcategoryField = document.getElementById('relatedSubcategoryField');

            const allSubcategoryOptions = Array.from(subcategorySelect.querySelectorAll('option[data-parent]'))
                .map((option) => option.cloneNode(true));
            const allRelatedSubcategoryOptions = Array.from(relatedSubcategorySelect.querySelectorAll('option[data-parent]'))
                .map((option) => option.cloneNode(true));

            const oldSubcategory = @json((string) old('subcategory_id', $ebook->subcategory_id));
            const oldRelatedSubcategory = @json((string) old('related_subcategory_id', $ebook->related_subcategory_id));

            function resetSelect(selectEl, placeholder) {
                selectEl.innerHTML = '';
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = placeholder;
                selectEl.appendChild(placeholderOption);
            }

            function toggleField(fieldEl, selectEl, show) {
                fieldEl.style.display = show ? '' : 'none';
                selectEl.disabled = !show;
                if (!show) {
                    selectEl.value = '';
                }
            }

            function populateSubcategories(selectedSubcategory) {
                const categoryId = categorySelect.value;
                resetSelect(subcategorySelect, 'Select subcategory');

                const matchedSubcategories = allSubcategoryOptions
                    .filter((option) => option.dataset.parent === categoryId);

                matchedSubcategories.forEach((option) => subcategorySelect.appendChild(option.cloneNode(true)));

                const hasSubcategories = matchedSubcategories.length > 0;
                toggleField(subcategoryField, subcategorySelect, hasSubcategories);

                if (!hasSubcategories) {
                    toggleField(relatedSubcategoryField, relatedSubcategorySelect, false);
                    return;
                }

                if (selectedSubcategory) {
                    subcategorySelect.value = selectedSubcategory;
                }
            }

            function populateRelatedSubcategories(selectedRelatedSubcategory) {
                const subcategoryId = subcategorySelect.value;
                resetSelect(relatedSubcategorySelect, 'Select related subcategory');

                const matchedRelatedSubcategories = allRelatedSubcategoryOptions
                    .filter((option) => option.dataset.parent === subcategoryId);

                matchedRelatedSubcategories.forEach((option) => relatedSubcategorySelect.appendChild(option.cloneNode(true)));

                const hasRelatedSubcategories = matchedRelatedSubcategories.length > 0;
                toggleField(relatedSubcategoryField, relatedSubcategorySelect, hasRelatedSubcategories);

                if (!hasRelatedSubcategories) {
                    return;
                }

                if (selectedRelatedSubcategory) {
                    relatedSubcategorySelect.value = selectedRelatedSubcategory;
                }
            }

            categorySelect.addEventListener('change', function() {
                populateSubcategories('');
                populateRelatedSubcategories('');
            });

            subcategorySelect.addEventListener('change', function() {
                populateRelatedSubcategories('');
            });

            populateSubcategories(oldSubcategory);
            populateRelatedSubcategories(oldRelatedSubcategory);
        });
    </script>

    <style>
        .category-separator {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #516578;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .category-separator::before,
        .category-separator::after {
            content: "";
            flex: 1;
            height: 1px;
            background: #d7e4ec;
        }
    </style>
@endpush
