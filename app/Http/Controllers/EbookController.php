<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Ebook;

use App\Models\Category;  


class EbookController extends Controller
{
    /* ======================================================
       1. LIST ALL EBOOKS
    ====================================================== */
//    public function index()
// {
//     $ebooks = Ebook::latest()
//         ->get()
//         ->groupBy('title'); // 👈 GROUP BY MANUAL EBOOK NAME

//     return view('ebook.index', compact('ebooks'));
// }



public function index()
{ 
    $user = auth()->user();

    $ebooks = Ebook::with(['uploader', 'sharedUser', 'category', 'subcategory', 'relatedSubcategory'])
        ->latest()
        ->paginate(10);

    $groupedEbooks = $ebooks->getCollection()->groupBy('title');

    // Parent categories for category dropdown
    $categories = Category::whereNull('parent_id')
        ->orderBy('name')
        ->get();

    return view('ebook.index', compact(
        'ebooks',
        'groupedEbooks',
        'user',
        'categories'   // 🔥 now passed to blade
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

            if (!$file->isValid()) {
                continue;
            }

            $originalName = pathinfo(
                $file->getClientOriginalName(),
                PATHINFO_FILENAME
            );

            $safeTitle = Str::title(str_replace('_', ' ', $originalName));

            $folder = Str::slug($originalName)
                . '_' . time()
                . '_' . Str::random(4);

            // 🔥 SAVE DIRECTLY INSIDE public_html (unchanged)
            $basePath = base_path('../public_html/ebooks/' . $folder);

            if (!File::exists($basePath)) {
                File::makeDirectory($basePath, 0755, true);
            }

            $pdfName = Str::slug($originalName) . '.pdf';

            $file->move($basePath, $pdfName);

            // ✅ Generate UNIQUE slug safely
            $baseSlug = Str::slug($manualTitle);
            $slug = $baseSlug;
            $counter = 1;

            while (Ebook::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            Ebook::create([
                'title'       => $manualTitle,
                'slug'        => $slug,
                'file_title'  => $safeTitle,
                'pdf_path'    => "ebooks/$folder/$pdfName", // DON'T CHANGE
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








// public function store(Request $request)
// {
//     $request->validate([
//         'title'   => 'required|string|max:255', // ✅ manual ebook name
//         'pdfs.*'  => 'required|mimes:pdf|max:20480',
//     ]);

//     if (!$request->hasFile('pdfs')) {
//         return back()->with('error', 'No PDF files uploaded.');
//     }

//     $files = $request->file('pdfs');
//     if (!is_array($files)) {
//         $files = [$files];
//     }

//     // Sort files (important for folder upload)
//     usort($files, fn ($a, $b) =>
//         strcmp($a->getClientOriginalName(), $b->getClientOriginalName())
//     );

//     $created = 0;

//     // ✅ Manual Ebook Name (same for all files)
//     $manualTitle = $request->title;

//     // ✅ Common file title (from FIRST file only)
//     $firstFileName = pathinfo($files[0]->getClientOriginalName(), PATHINFO_FILENAME);
//     $fileTitle = Str::slug($firstFileName); // flipbook

//     // ✅ ONE folder for all files
//     $folder = $fileTitle . '_' . time() . '_' . Str::random(4);
//     $basePath = public_path("ebooks/$folder");

//     File::makeDirectory($basePath, 0755, true);

//     foreach ($files as $file) {

//         if (!$file->isValid()) continue;

//         $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//         $pdfName = Str::slug($originalName) . '.pdf';

//         $file->move($basePath, $pdfName);

//         Ebook::create([
//             'title'       => $manualTitle,   // ✅ Gate Flipbook
//             'file_title'  => $fileTitle,     // ✅ flipbook (common)
//             'pdf_path'    => "ebooks/$folder/$pdfName",
//             'folder_path' => $folder,
//             'page_count'  => 0,
//             'uploaded_by' => auth()->id() ?? 1,
//         ]);

//         $created++;
//     }

//     return back()->with(
//         $created ? 'success' : 'error',
//         $created ? "$created file(s) added to ebook successfully." : 'No valid PDF files found.'
//     );
// }

    /* ======================================================
       3. CORE METHOD – ENSURE PAGES EXIST
       (USED BY VIEW + SHARE)
    ====================================================== */
    // public function ensurePagesExist(Ebook $ebook)
    // {
    //     $pagesPath = public_path("ebooks/{$ebook->folder_path}/pages");

    //     // ✅ Pages already exist
    //     if (is_dir($pagesPath) && count(glob($pagesPath . '/*.jpg')) > 0) {
    //         return true;
    //     }

    //     $pdfFile = public_path($ebook->pdf_path);
    //     if (!file_exists($pdfFile)) {
    //         return false;
    //     }

    //     if (!file_exists($pagesPath)) {
    //         mkdir($pagesPath, 0777, true);
    //     }

    //     $magick = "C:\\Program Files\\ImageMagick-7.1.2-Q16-HDRI\\magick.exe";
    //     if (!file_exists($magick)) {
    //         abort(500, 'ImageMagick not found');
    //     }

    //     $output = $pagesPath . "\\page_%03d.jpg";
    //     exec("\"$magick\" -density 200 \"$pdfFile\" -quality 92 \"$output\"");

    //     return count(glob($pagesPath . '/*.jpg')) > 0;
    // }



public function ensurePagesExist(Ebook $ebook)
{
    $pdfFile = base_path('../public_html/' . $ebook->pdf_path);

    if (!file_exists($pdfFile)) {
        return false;
    }

    return true;
}

    /* ======================================================
       4. VIEW FLIPBOOK (ADMIN / DASHBOARD)
    ====================================================== */
    public function view($id)
{
    $ebook = Ebook::findOrFail($id);

    // 🔥 Check inside public_html
    $pdfPath = base_path('../public_html/' . $ebook->pdf_path);

    if (!file_exists($pdfPath)) {
        abort(404, 'PDF file not found');
    }

    return view('ebook.flipbook', compact('ebook'));
}

    /* ======================================================
       5. PUBLIC VIEW (OPTIONAL)
    ====================================================== */
    public function publicView($id)
    {
        return $this->view($id);
    }

    /* ======================================================
       6. DELETE EBOOK
    ====================================================== */
public function delete($id)
{
    try {

        $ebook = Ebook::findOrFail($id);

        // Clean dependent rows explicitly (safe even if FK cascade exists).
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

        $folderPaths = array_unique([
            public_path("ebooks/{$ebook->folder_path}"),
            base_path("../public_html/ebooks/{$ebook->folder_path}"),
        ]);

        foreach ($folderPaths as $folderPath) {
            if (!File::exists($folderPath)) {
                continue;
            }

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec('rd /s /q "' . $folderPath . '"');
            } else {
                File::deleteDirectory($folderPath);
            }
        }

        if (Schema::hasTable('ebook') && Schema::hasColumn('ebook', 'id')) {
            $ebook->delete();
        }

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
   7. API VIEW (JSON)
====================================================== */
public function viewApi($id)
{
    $ebook = Ebook::findOrFail($id);

    return response()->json($ebook);
}

}

