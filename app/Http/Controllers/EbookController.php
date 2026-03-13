<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Ebook;
use App\Models\EbookIssueReport;
use App\Models\Category;
use App\Models\User;
use Illuminate\Validation\Rule;

class EbookController extends Controller
{
    /* ======================================================
       1. LIST ALL EBOOKS
    ====================================================== */
    public function index()
    {
        $user = auth()->user();

        $ebooks = Ebook::with([
                'uploader',
                'sharedUser',
                'category',
                'subcategory',
                'relatedSubcategory'
            ])
            ->latest()
            ->paginate(10);

        $groupedEbooks = $ebooks->getCollection()->groupBy('title');

        $categories = Category::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('ebook.index', compact(
            'ebooks',
            'groupedEbooks',
            'user',
            'categories'
        ));
    }

    /* ======================================================
       2. UPLOAD PDF(s)
    ====================================================== */
    public function store(Request $request)
    {
        try {

            $user = auth()->user();

            if (!$user || (!$user->can_upload && $user->role !== 'admin')) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to upload'
                ], 403);
            }

            $request->validate([
                'ebook_name' => 'required|string|max:255',
                'author_name' => 'required|string|max:255',
                'year' => 'nullable|integer|digits:4|min:1900|max:2100',
                'category_id' => ['nullable', Rule::exists('categories', 'id')->where('is_deleted', 0)],
                'subcategory_id' => ['nullable', Rule::exists('categories', 'id')->where('is_deleted', 0)],
                'related_subcategory_id' => ['nullable', Rule::exists('categories', 'id')->where('is_deleted', 0)],
                'pdfs'       => 'required|array|min:1',
                'pdfs.*'     => 'required|file|mimes:pdf|max:51200',
            ]);

            $files = $request->file('pdfs');
            if (!is_array($files)) {
                $files = [$files];
            }

            $created = 0;
            $manualTitle = $request->ebook_name;
            $authorName = $request->author_name;
            $publishYear = $request->filled('year') ? (int) $request->year : null;

            foreach ($files as $file) {

                if (!$file->isValid()) continue;

                $originalName = pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                );

                $safeTitle = Str::title(str_replace('_', ' ', $originalName));

                $folder = Str::slug($originalName)
                    . '_' . time()
                    . '_' . Str::random(4);

                // $rootFolder = config('app.file_root');
                // $basePath = base_path("../{$rootFolder}/ebooks/$folder");//live
                // $basePath = base_path("{$rootFolder}/ebooks/$folder");//local
                $rootFolder = config('app.file_root');

                if ($rootFolder == 'public') {
                    $basePath = base_path("public/ebooks/$folder");
                } else {
                    $basePath = base_path("../public_html/ebooks/$folder");
                }

                if (!File::exists($basePath)) {
                    File::makeDirectory($basePath, 0755, true);
                }

                $pdfName = Str::slug($originalName) . '.pdf';

                $file->move($basePath, $pdfName);

                // Generate unique slug
                $baseSlug = Str::slug($manualTitle);
                $slug = $baseSlug;
                $counter = 1;

                while (Ebook::where('slug', $slug)->exists()) {
                    $slug = $baseSlug . '-' . $counter++;
                }

                Ebook::create([
                    'title'       => $manualTitle,
                    'year'        => $publishYear,
                    'author_name' => $authorName,
                    'slug'        => $slug,
                    'file_title'  => $safeTitle,
                    'pdf_path'    => "ebooks/$folder/$pdfName",
                    'folder_path' => $folder,
                    'page_count'  => 0,
                    'category_id' => $request->category_id,
                    'subcategory_id' => $request->subcategory_id,
                    'related_subcategory_id' => $request->related_subcategory_id,
                    'user_id'     => $user->id,
                    'uploaded_by' => $user->id,
                ]);

                $created++;
            }

            return response()->json([
                'status' => true,
                'message' => "$created ebook(s) uploaded successfully."
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => 'Upload failed',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /* ======================================================
       3. VIEW FLIPBOOK (Slug Based)
    ====================================================== */
    public function view($slug)
    {
        $ebook = Ebook::where('slug', $slug)->firstOrFail();
        $reportRecipients = collect();

        if (auth()->check()) {
            $reportRecipients = User::query()
                ->where('status', 'active')
                ->whereKeyNot(auth()->id())
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        $rootFolder = config('app.file_root');
        // $pdfPath = base_path("../{$rootFolder}/" . $ebook->pdf_path);

        if ($rootFolder == 'public') {
        $pdfPath = base_path("public/" . $ebook->pdf_path);
        } else {
        $pdfPath = base_path("../public_html/" . $ebook->pdf_path);
        }
        if (!file_exists($pdfPath)) {
            abort(404, 'PDF file not found');
        }

        return view('ebook.flipbook', compact('ebook', 'reportRecipients'));
    }

    public function reportIssue(Request $request, $id)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $ebook = Ebook::find($id);
        if (!$ebook) {
            return response()->json([
                'status' => false,
                'message' => 'Ebook not found.',
            ], 404);
        }

        $validated = $request->validate([
            'recipient_id' => ['required', 'integer', Rule::exists('users', 'id')->where('status', 'active')],
            'page' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        if ((int) $validated['recipient_id'] === (int) $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot assign the report to yourself.',
            ], 422);
        }

        EbookIssueReport::create([
            'ebook_id' => $ebook->id,
            'reported_by' => $user->id,
            'recipient_id' => $validated['recipient_id'],
            'page' => $validated['page'],
            'description' => trim($validated['description']),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Issue report saved successfully.',
        ]);
    }

    /* ======================================================
       4. DELETE EBOOK
    ====================================================== */
    public function delete($id)
    {
        try {

            $ebook = Ebook::findOrFail($id);

            if (Schema::hasTable('ebook_pages')) {
                DB::table('ebook_pages')
                    ->where('ebook_id', $ebook->id)
                    ->delete();
            }

            if (Schema::hasTable('ebook_shares')) {
                DB::table('ebook_shares')
                    ->where('ebook_id', $ebook->id)
                    ->delete();
            }

            $rootFolder = config('app.file_root');
            $folderPath = base_path("../{$rootFolder}/ebooks/{$ebook->folder_path}");

            if (File::exists($folderPath)) {
                File::deleteDirectory($folderPath);
            }

            $ebook->delete();

            return response()->json([
                'status' => true,
                'message' => 'Ebook deleted successfully'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /* ======================================================
       5. API VIEW
    ====================================================== */
    public function viewApi($id)
    {
        return Ebook::findOrFail($id);
    }
}
