<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ebook;
use App\Models\Category;

class HomeController extends Controller
{
    /**
     * USER HOME PAGE
     */
    public function userHome(Request $request)
    {
        $user = auth()->user()?->fresh();
        $canUploadNow = false;
        $canShareNow = false;

        if ($user) {
            $canUploadPermission = $user->role === 'admin' || (bool) $user->can_upload;
            if ($canUploadPermission) {
                $uploadLimit = (int) $user->upload_limit;
                if ($user->role === 'admin' || $uploadLimit === 0) {
                    $canUploadNow = true;
                } else {
                    $uploadedCount = Ebook::where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->orWhere('uploaded_by', $user->id);
                    })
                        ->when($user->upload_reset_at, function ($q, $resetAt) {
                            $q->where('created_at', '>', $resetAt);
                        })
                        ->count();
                    $canUploadNow = $uploadedCount < $uploadLimit;
                }
            }

            $canSharePermission = $user->role === 'admin' || (bool) $user->can_share;
            if ($canSharePermission) {
                $shareLimit = (int) $user->share_limit;
                if ($user->role === 'admin' || $shareLimit === 0) {
                    $canShareNow = true;
                } else {
                    $activeShares = Ebook::where('shared_by', $user->id)
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
                        ->count();
                    $canShareNow = $activeShares < $shareLimit;
                }
            }
        }

        $selectedCategoryId = $request->integer('category') ?: null;
        $selectedSubcategoryId = $request->integer('subcategory') ?: null;
        $selectedRelatedSubcategoryId = $request->integer('related_subcategory') ?: null;

        if ($selectedCategoryId) {
            $isParentCategory = Category::where('id', $selectedCategoryId)
                ->whereNull('parent_id')
                ->exists();

            if (!$isParentCategory) {
                $selectedCategoryId = null;
                $selectedSubcategoryId = null;
                $selectedRelatedSubcategoryId = null;
            }
        }

        if ($selectedSubcategoryId) {
            $isValidSubcategory = Category::where('id', $selectedSubcategoryId)
                ->where('parent_id', $selectedCategoryId)
                ->exists();

            if (!$isValidSubcategory) {
                $selectedSubcategoryId = null;
                $selectedRelatedSubcategoryId = null;
            }
        }

        if ($selectedRelatedSubcategoryId) {
            $isValidRelatedSubcategory = Category::where('id', $selectedRelatedSubcategoryId)
                ->where('parent_id', $selectedSubcategoryId)
                ->exists();

            if (!$isValidRelatedSubcategory) {
                $selectedRelatedSubcategoryId = null;
            }
        }

        $query = Ebook::query()->with([
            'coverPage' => function ($query) {
                $query->select([
                    'ebook_pages.id',
                    'ebook_pages.ebook_id',
                    'ebook_pages.page_no',
                    'ebook_pages.image_path',
                ]);
            },
        ]);

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($selectedCategoryId) {
            $query->where('category_id', $selectedCategoryId);
        }

        if ($selectedSubcategoryId) {
            $query->where('subcategory_id', $selectedSubcategoryId);
        }

        if ($selectedRelatedSubcategoryId) {
            $query->where('related_subcategory_id', $selectedRelatedSubcategoryId);
        }

        $ebooks = $query->latest()->paginate(10);
        $categories = Category::whereNull('parent_id')
            ->with([
                'children' => function ($query) {
                    $query->orderBy('name');
                },
                'children.children' => function ($query) {
                    $query->orderBy('name');
                },
            ])
            ->orderBy('name')
            ->get();

        $subcategories = collect();
        if ($selectedCategoryId) {
            $subcategories = Category::where('parent_id', $selectedCategoryId)
                ->orderBy('name')
                ->get();
        }

        $relatedSubcategories = collect();
        if ($selectedSubcategoryId) {
            $relatedSubcategories = Category::where('parent_id', $selectedSubcategoryId)
                ->orderBy('name')
                ->get();
        }

        return view('ebook.home', compact(
            'ebooks',
            'categories',
            'subcategories',
            'relatedSubcategories',
            'canUploadNow',
            'canShareNow',
            'selectedCategoryId',
            'selectedSubcategoryId',
            'selectedRelatedSubcategoryId'
        ));
    }
}
