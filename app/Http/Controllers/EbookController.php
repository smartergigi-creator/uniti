<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Ebook;
use App\Models\Category;

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
                'pdfs'       => 'required|array|min:1',
                'pdfs.*'     => 'required|file|mimes:pdf|max:51200',
            ]);

            $files = $request->file('pdfs');
            if (!is_array($files)) {
                $files = [$files];
            }

            $created = 0;
            $manualTitle = $request->ebook_name;

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

                // ✅ Works in local + production automatically
                $basePath = public_path("ebooks/$folder");

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

        $pdfPath = public_path($ebook->pdf_path);

        if (!file_exists($pdfPath)) {
            abort(404, 'PDF file not found');
        }

        return view('ebook.flipbook', compact('ebook'));
    }

    /* ======================================================
       4. DELETE EBOOK
    ====================================================== */
    public function delete($id)
    {
        try {

            $ebook = Ebook::findOrFail($id);

            // Remove related rows safely
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

            // Delete folder
            $folderPath = public_path("ebooks/{$ebook->folder_path}");

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