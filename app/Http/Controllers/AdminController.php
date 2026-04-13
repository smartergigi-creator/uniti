<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Ebook;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class AdminController extends Controller
{
 
public function dashboard()
{
    $users = User::where('role', 'user')
                 ->latest()
                 ->paginate(10); // per page 10 users

    $userIds = $users->getCollection()->pluck('id')->all();

    $uploadCounts = [];
    $activeShareCounts = [];
    $reachedShareCounts = [];

    if (!empty($userIds)) {
        $uploadRows = Ebook::select(['id', 'user_id', 'uploaded_by', 'created_at'])
            ->whereIn('user_id', $userIds)
            ->orWhereIn('uploaded_by', $userIds)
            ->get();

        foreach ($uploadRows as $ebook) {
            $ownerId = in_array($ebook->user_id, $userIds, true)
                ? $ebook->user_id
                : $ebook->uploaded_by;

            if (!$ownerId) {
                continue;
            }

            $resetAt = optional($users->getCollection()->firstWhere('id', $ownerId))->upload_reset_at;
            if ($resetAt && $ebook->created_at && $ebook->created_at->lte($resetAt)) {
                continue;
            }

            $uploadCounts[$ownerId] = ($uploadCounts[$ownerId] ?? 0) + 1;
        }

        $activeShareCounts = Ebook::whereIn('shared_by', $userIds)
            ->where('share_enabled', 1)
            ->where(function ($q) {
                $q->whereNull('share_expires_at')
                    ->orWhere('share_expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_views')
                    ->orWhere('max_views', 0)
                    ->orWhereColumn('current_views', '<', 'max_views');
            })
            ->selectRaw('shared_by, COUNT(*) as total')
            ->groupBy('shared_by')
            ->pluck('total', 'shared_by')
            ->toArray();

        $reachedShareCounts = Ebook::whereIn('shared_by', $userIds)
            ->where('share_enabled', 1)
            ->where(function ($q) {
                $q->whereNull('share_expires_at')
                    ->orWhere('share_expires_at', '>', now());
            })
            ->whereNotNull('max_views')
            ->where('max_views', '>', 0)
            ->whereColumn('current_views', '>=', 'max_views')
            ->selectRaw('shared_by, COUNT(*) as total')
            ->groupBy('shared_by')
            ->pluck('total', 'shared_by')
            ->toArray();
    }

    $users->setCollection(
        $users->getCollection()->map(function ($user) use ($uploadCounts, $activeShareCounts, $reachedShareCounts) {
            $uploadUsed = (int) ($uploadCounts[$user->id] ?? 0);
            $shareUsed = (int) ($activeShareCounts[$user->id] ?? 0);
            $shareReached = (int) ($reachedShareCounts[$user->id] ?? 0);
            $uploadLimit = (int) $user->upload_limit;
            $shareLimit = (int) $user->share_limit;

            $user->upload_used = $uploadUsed;
            $user->share_used = $shareUsed;
            $user->share_reached = $shareReached;
            $user->upload_limit_reached = $user->can_upload && $uploadLimit > 0 && $uploadUsed >= $uploadLimit;
            $user->share_limit_reached = $user->can_share && $shareLimit > 0 && ($shareUsed >= $shareLimit || $shareReached > 0);

            return $user;
        })
    );

    $totalUsers = User::where('role', 'user')->count();
    $totalEbooks = Ebook::count();
    $todayUploads = Ebook::whereDate('created_at', Carbon::today())->count();
    $expiredShares = Ebook::where('share_enabled', 1)
        ->whereNotNull('share_expires_at')
        ->where('share_expires_at', '<', now())
        ->count();

    return view('admin.dashboard', compact(
        'users',
        'totalUsers',
        'totalEbooks',
        'todayUploads',
        'expiredShares'
    ));
}

public function resetUserUploads($id)
{
    $user = User::findOrFail($id);
    $user->upload_reset_at = now();
    $user->can_upload = false;
    $user->upload_limit = 0;
    $user->save();

    return back()->with('success', 'User upload usage reset successfully.');
}

public function resetUserShares($id)
{
    $user = User::findOrFail($id);

    Ebook::where('shared_by', $user->id)->update([
        'share_enabled' => 0,
        'share_token' => null,
        'share_expires_at' => null,
        'shared_by' => null,
        'current_views' => 0,
        'max_views' => null,
    ]);

    $user->can_share = false;
    $user->share_limit = 0;
    $user->save();

    return back()->with('success', 'User shares reset successfully.');
}

public function categories()
{
    $categories = Category::with('parent')
        ->latest()
        ->paginate(10);

    $parentCategories = Category::whereNull('parent_id')
        ->orderBy('name')
        ->get();

    $allCategories = Category::orderBy('name')->get(['id', 'name', 'parent_id']);

    return view('admin.categories', compact('categories', 'parentCategories', 'allCategories'));
}

public function ebooks()
{
    $ebooks = Ebook::with(['uploader', 'uploadedByUser', 'category'])
        ->latest()
        ->paginate(10);

    return view('admin.ebooks', compact('ebooks'));
}

public function editEbook($id)
{
    $ebook = Ebook::with(['category', 'subcategory', 'relatedSubcategory'])->findOrFail($id);

    $categories = Category::whereNull('parent_id')
        ->orderBy('name')
        ->get(['id', 'name']);

    $subcategories = Category::whereNotNull('parent_id')
        ->orderBy('name')
        ->get(['id', 'name', 'parent_id']);

    return view('admin.ebook-edit', compact('ebook', 'categories', 'subcategories'));
}

public function updateEbook(Request $request, $id)
{
    $ebook = Ebook::findOrFail($id);

    $validated = $request->validate([
        'title' => 'nullable|string|max:255',
        'year' => 'nullable|integer|digits:4|min:1900|max:2100',
        'author_name' => 'nullable|string|max:255',
        'file_title' => 'nullable|string|max:255',
        'ebook_file' => 'nullable|file|mimes:pdf|max:20480',
        'category_id' => ['nullable', Rule::exists('categories', 'id')->where('is_deleted', 0)],
        'subcategory_id' => ['nullable', Rule::exists('categories', 'id')->where('is_deleted', 0)],
        'related_subcategory_id' => ['nullable', Rule::exists('categories', 'id')->where('is_deleted', 0)],
    ]);

    $categoryId = isset($validated['category_id']) ? (int) $validated['category_id'] : null;
    $subcategoryId = isset($validated['subcategory_id']) ? (int) $validated['subcategory_id'] : null;
    $relatedSubcategoryId = isset($validated['related_subcategory_id']) ? (int) $validated['related_subcategory_id'] : null;

    $categoryId = $categoryId > 0 ? $categoryId : null;
    $subcategoryId = $subcategoryId > 0 ? $subcategoryId : null;
    $relatedSubcategoryId = $relatedSubcategoryId > 0 ? $relatedSubcategoryId : null;

    if ($categoryId !== null && !Category::find($categoryId)) {
        return back()->withErrors([
            'category_id' => 'Invalid category selected.'
        ])->withInput();
    }

    if ($subcategoryId !== null) {
        $subcategory = Category::find($subcategoryId);
        if (!$subcategory || $subcategory->parent_id === null) {
            return back()->withErrors([
                'subcategory_id' => 'Invalid subcategory selected.'
            ])->withInput();
        }

        if ($categoryId === null || (int) $subcategory->parent_id !== $categoryId) {
            return back()->withErrors([
                'subcategory_id' => 'Selected subcategory does not belong to selected category.'
            ])->withInput();
        }
    } else {
        $relatedSubcategoryId = null;
    }

    if ($relatedSubcategoryId !== null) {
        $relatedSubcategory = Category::find($relatedSubcategoryId);
        if (!$relatedSubcategory || $relatedSubcategory->parent_id === null) {
            return back()->withErrors([
                'related_subcategory_id' => 'Invalid related subcategory selected.'
            ])->withInput();
        }

        if ($subcategoryId === null || (int) $relatedSubcategory->parent_id !== $subcategoryId) {
            return back()->withErrors([
                'related_subcategory_id' => 'Selected related subcategory does not belong to selected subcategory.'
            ])->withInput();
        }
    }

    $title = trim((string) ($validated['title'] ?? ''));
    $authorName = trim((string) ($validated['author_name'] ?? ''));
    $fileTitle = trim((string) ($validated['file_title'] ?? ''));

    if ($title !== '') {
        $ebook->title = $title;
    }
    if ($request->filled('year')) {
        $ebook->year = (int) $validated['year'];
    }
    if ($authorName !== '') {
        $ebook->author_name = $authorName;
    }
    if ($fileTitle !== '') {
        $ebook->file_title = $fileTitle;
    }

    //  FILE UPLOAD LOGIC (FIXED)
    if ($request->hasFile('ebook_file')) {

        // delete old file
        if ($ebook->pdf_path && file_exists(public_path($ebook->pdf_path))) {
            unlink(public_path($ebook->pdf_path));
        }

        // upload new file
        $file = $request->file('ebook_file');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/ebooks'), $filename);

        // save new path (IMPORTANT FIX)
        $ebook->pdf_path = 'uploads/ebooks/' . $filename;
    }

    // no upload → old file remains

    $ebook->category_id = $categoryId;
    $ebook->subcategory_id = $subcategoryId;
    $ebook->related_subcategory_id = $relatedSubcategoryId;

    $ebook->save();

    return redirect()
        ->route('admin.ebooks')
        ->with('success', 'Ebook updated successfully.');
}

public function todayUploads()
{
    $todayDate = Carbon::today();

    $ebooks = Ebook::with(['uploader', 'uploadedByUser', 'category'])
        ->whereDate('created_at', $todayDate)
        ->latest()
        ->paginate(10);

    return view('admin.today-uploads', compact('ebooks', 'todayDate'));
}

public function storeCategoryTree(Request $request)
{
    $validator = Validator::make($request->all(), [
        'new_parent_name' => 'nullable|string|max:120',
        'new_sub_name' => 'nullable|string|max:120',
        'new_related_name' => 'nullable|string|max:120',
        'new_sub_names' => 'nullable|array',
        'new_sub_names.*' => 'nullable|string|max:120',
        'new_related_names' => 'nullable|array',
        'new_related_names.*' => 'nullable|string|max:120',
        'parent_category_id' => 'nullable|exists:categories,id',
        'sub_category_id' => 'nullable|exists:categories,id',
        'related_parent_sub' => 'nullable|string|max:191',
        'related_parent_subs' => 'nullable|array',
        'related_parent_subs.*' => 'nullable|string|max:191',
    ]);
    if ($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('open_modal', 'add');
    }

    $validated = $validator->validated();

    $newParentName = trim((string) ($validated['new_parent_name'] ?? ''));
    $newSubNames = collect($validated['new_sub_names'] ?? []);
    $rawRelatedNames = collect($request->input('new_related_names', []));
    $newRelatedNames = collect($validated['new_related_names'] ?? []);

    if (!empty($validated['new_sub_name'])) {
        $newSubNames->prepend($validated['new_sub_name']);
    }
    if (!empty($validated['new_related_name'])) {
        $newRelatedNames->prepend($validated['new_related_name']);
    }

    $newSubNames = $newSubNames
        ->map(fn ($name) => trim((string) $name))
        ->filter(fn ($name) => $name !== '')
        ->values();

    $newRelatedNames = $newRelatedNames
        ->map(fn ($name) => trim((string) $name))
        ->filter(fn ($name) => $name !== '')
        ->values();

    $selectedParentId = (int) ($validated['parent_category_id'] ?? 0);
    $selectedSubId = (int) ($validated['sub_category_id'] ?? 0);
    $relatedParentSub = trim((string) ($validated['related_parent_sub'] ?? ''));
    $relatedParentSubs = collect($request->input('related_parent_subs', []));

    if (
        $newParentName === '' &&
        $newSubNames->isEmpty() &&
        $newRelatedNames->isEmpty() &&
        $selectedParentId <= 0 &&
        $selectedSubId <= 0
    ) {
        return back()->withErrors([
            'new_parent_name' => 'Enter at least one category name or select an existing category.'
        ])->withInput()->with('open_modal', 'add');
    }

    $createdParent = null;
    $createdSub = null;
    $created = [];
    $createdSubMap = [];

    if ($newParentName !== '') {
        $createdParent = $this->createCategoryWithUniqueSlug($newParentName, null);
        $created[] = "Parent: {$createdParent->name}";
    }

    if ($selectedParentId <= 0 && $createdParent) {
        $selectedParentId = $createdParent->id;
    }

    if ($newSubNames->isNotEmpty()) {
        if ($selectedParentId <= 0) {
            return back()->withErrors([
                'parent_category_id' => 'Select a parent category or create a new parent first.'
            ])->withInput()->with('open_modal', 'add');
        }

        $selectedParent = Category::find($selectedParentId);
        if (!$selectedParent || $selectedParent->parent_id !== null) {
            return back()->withErrors([
                'parent_category_id' => 'Please choose a valid parent category.'
            ])->withInput()->with('open_modal', 'add');
        }

        foreach ($newSubNames as $newSubName) {
            $createdSub = $this->createCategoryWithUniqueSlug($newSubName, $selectedParentId);
            $created[] = "Sub: {$createdSub->name}";
            $createdSubMap[Str::lower($newSubName)] = $createdSub->id;
        }
    }

    if ($relatedParentSub !== '') {
        if (Str::startsWith($relatedParentSub, 'existing:')) {
            $pickedSubId = (int) Str::after($relatedParentSub, 'existing:');
            if ($pickedSubId > 0) {
                $selectedSubId = $pickedSubId;
            }
        } elseif (Str::startsWith($relatedParentSub, 'new:')) {
            $pickedSubName = Str::lower(trim(Str::after($relatedParentSub, 'new:')));
            if ($pickedSubName !== '' && isset($createdSubMap[$pickedSubName])) {
                $selectedSubId = (int) $createdSubMap[$pickedSubName];
            }
        }
    }

    if ($relatedParentSub !== '' && $relatedParentSubs->isEmpty()) {
        $relatedParentSubs->push($relatedParentSub);
    }

    if ($selectedSubId <= 0 && $createdSub) {
        $selectedSubId = $createdSub->id;
    }

    $relatedEntries = $rawRelatedNames->map(function ($rawName, $idx) use ($relatedParentSubs) {
        return [
            'name' => trim((string) $rawName),
            'mapping' => trim((string) $relatedParentSubs->get($idx, '')),
        ];
    })->filter(fn ($entry) => $entry['name'] !== '')->values();

    if ($relatedEntries->isNotEmpty()) {
        $resolveSubIdFromMapping = function (?string $mappingValue) use ($createdSubMap, $selectedSubId) {
            $mappingValue = trim((string) $mappingValue);
            if ($mappingValue === '') {
                return $selectedSubId > 0 ? $selectedSubId : 0;
            }

            if (Str::startsWith($mappingValue, 'existing:')) {
                return (int) Str::after($mappingValue, 'existing:');
            }

            if (Str::startsWith($mappingValue, 'new:')) {
                $mappedSubName = Str::lower(trim(Str::after($mappingValue, 'new:')));
                if ($mappedSubName !== '' && isset($createdSubMap[$mappedSubName])) {
                    return (int) $createdSubMap[$mappedSubName];
                }
            }

            return 0;
        };

        foreach ($relatedEntries as $entry) {
            $newRelatedName = $entry['name'];
            $targetSubId = $resolveSubIdFromMapping($entry['mapping']);
            if ($targetSubId <= 0) {
                return back()->withErrors([
                    'related_parent_subs' => 'Choose valid sub category mapping for each related sub category.'
                ])->withInput()->with('open_modal', 'add');
            }

            $selectedSub = Category::find($targetSubId);
            if (!$selectedSub || $selectedSub->parent_id === null) {
                return back()->withErrors([
                    'related_parent_subs' => 'Please choose a valid sub category for related mapping.'
                ])->withInput()->with('open_modal', 'add');
            }

            $createdRelated = $this->createCategoryWithUniqueSlug($newRelatedName, $targetSubId);
            $created[] = "Related: {$createdRelated->name}";
        }
    }

    if (empty($created)) {
        return back()->with('success', 'Saved successfully.');
    }

    return back()->with('success', 'Saved successfully - ' . implode(' | ', $created));
}

public function updateCategory(Request $request, $id)
{
    $category = Category::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:120',
        'parent_id' => 'nullable|exists:categories,id',
    ]);
    if ($validator->fails()) {
        return back()
            ->withErrors($validator)
            ->withInput()
            ->with('open_modal', 'edit')
            ->with('edit_category_id', $category->id);
    }

    $validated = $validator->validated();
    $newName = trim((string) $validated['name']);
    $newParentId = (int) ($validated['parent_id'] ?? 0);
    $newParentId = $newParentId > 0 ? $newParentId : null;

    if ($newParentId === $category->id) {
        return back()->withErrors([
            'name' => 'Category cannot be its own parent.'
        ])->withInput()->with('open_modal', 'edit')->with('edit_category_id', $category->id);
    }

    if ($newParentId !== null && $this->createsParentCycle($category->id, $newParentId)) {
        return back()->withErrors([
            'name' => 'Invalid parent selected. It creates a circular hierarchy.'
        ])->withInput()->with('open_modal', 'edit')->with('edit_category_id', $category->id);
    }

    $category->name = $newName;
    $category->slug = $this->generateUniqueSlug($newName, $category->id);
    $category->parent_id = $newParentId;
    $category->save();

    return back()->with('success', "Category '{$category->name}' updated successfully.");
}

public function deleteCategory($id)
{
    $category = Category::findOrFail($id);
    $name = $category->name;
    $category->delete();

    return back()->with('success', "Category '{$name}' deleted successfully.");
}

private function createCategoryWithUniqueSlug(string $name, ?int $parentId): Category
{
    $slug = $this->generateUniqueSlug($name);

    return Category::create([
        'name' => $name,
        'slug' => $slug,
        'parent_id' => $parentId,
    ]);
}

private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
{
    $baseSlug = Str::slug($name);
    if ($baseSlug === '') {
        $baseSlug = 'category';
    }

    $slug = $baseSlug;
    $counter = 1;

    while (Category::when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
        ->where('slug', $slug)
        ->exists()) {
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }

    return $slug;
}

private function createsParentCycle(int $categoryId, int $parentId): bool
{
    $current = Category::find($parentId);
    while ($current) {
        if ($current->id === $categoryId) {
            return true;
        }
        if ($current->parent_id === null) {
            return false;
        }
        $current = Category::find($current->parent_id);
    }

    return false;
}

    
}
