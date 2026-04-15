<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use App\Models\User;
use App\Support\WatermarkedPdfDownloader;
use Illuminate\Support\Str;

class EbookShareController extends Controller
{
public function generate($slug)
{
    try {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $hasShareAccess = $user->hasUnlimitedPdfAccess() || (bool) $user->can_share;

        if (!$hasShareAccess) {
            return response()->json([
                'status' => false,
                'message' => 'You are not allowed to share',
            ], 403);
        }

        $ebook = Ebook::where('slug', $slug)->first();

        if (!$ebook) {
            return response()->json([
                'status' => false,
                'message' => 'Ebook not found',
            ], 404);
        }

        if ((int) $ebook->share_enabled === 1 && (int) $ebook->shared_by === (int) $user->id) {
            return response()->json([
                'status' => true,
                'publicLink' => url('/flip-book/' . $ebook->slug),
                'message' => 'Link already active',
            ]);
        }

        $userShareLimit = $user->hasUnlimitedPdfAccess() ? 0 : (int) $user->share_limit;

        if ($userShareLimit > 0) {
            $activeShares = Ebook::where('shared_by', $user->id)
                ->where('share_enabled', 1)
                ->count();

            if ($activeShares >= $userShareLimit) {
                return response()->json([
                    'status' => false,
                    'message' => 'Share limit reached',
                ], 403);
            }
        }

        $ebook->share_enabled = 1;
        $ebook->shared_by = $user->id;
        $ebook->save();

        return response()->json([
            'status' => true,
            'publicLink' => url('/flip-book/' . $ebook->slug),
            'message' => 'Share link generated successfully',
        ]);

    } catch (\Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => 'Share failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}

  public function view($slug)
{
    $ebook = Ebook::where('slug', $slug)->first();

    if (!$ebook) {
        return view('ebook/errors.share-invalid');
    }

    // ✅ share enabled check (optional but recommended)
    if ((int) $ebook->share_enabled !== 1) {
        return view('ebook/errors.share-invalid');
    }

    // ✅ expiry check
    if ($ebook->share_expires_at && now()->gt($ebook->share_expires_at)) {
        return view('ebook/errors.share-expired');
    }

    // ✅ view limit check
    if (
        $ebook->max_views !== null &&
        (int) $ebook->max_views > 0 &&
        $ebook->current_views >= $ebook->max_views
    ) {
        return view('ebook/errors.limit-reached');
    }

    // increment views
    $ebook->increment('current_views');

    $pdfPath = $this->resolvePdfPath($ebook->pdf_path);

    if (!file_exists($pdfPath)) {
        return view('ebook/errors.share-invalid');
    }

    $reportRecipients = collect();

    if (auth()->check()) {
        $reportRecipients = User::query()
            ->where('status', 'active')
            ->whereKeyNot(auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    // ✅ slug based download
    $downloadUrl = route('ebook.share.download', $slug);

    return view('ebook.flipbook', compact('ebook', 'reportRecipients', 'downloadUrl'));
}

    public function download($slug, WatermarkedPdfDownloader $downloader)
{
    // 🔍 find ebook by slug
    $ebook = Ebook::where('slug', $slug)
        ->where('share_enabled', 1)
        ->first();

    if (!$ebook) {
        abort(404, 'Shared ebook not found');
    }

    // ⏳ expiry check
    if ($ebook->share_expires_at && now()->gt($ebook->share_expires_at)) {
        abort(403, 'Share link expired');
    }

    // 👁️ view limit check
    if (
        $ebook->max_views !== null &&
        (int) $ebook->max_views > 0 &&
        $ebook->current_views >= $ebook->max_views
    ) {
        abort(403, 'Share limit reached');
    }

    // 📂 resolve file path
    $pdfPath = $this->resolvePdfPath($ebook->pdf_path);

    if (!is_file($pdfPath)) {
        abort(404, 'PDF file not found');
    }

    // 🔄 increment view count
    $ebook->increment('current_views');

    try {
        // 🧾 watermark download
        $payload = $downloader->build(
            $pdfPath,
            $this->downloadFileName($ebook),
            $this->resolveWatermarkLogoPath(),
            'UNITI'
        );

        return response($payload['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $payload['name'] . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

    } catch (\Throwable $e) {

        // 🔁 fallback normal download
        return response()->download(
            $pdfPath,
            $this->downloadFileName($ebook),
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }
}

    protected function resolvePdfPath(string $pdfPath): string
    {
        $relativePath = ltrim($pdfPath, '/\\');

        $livePath = dirname(base_path()) . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . $relativePath;

        if (is_file($livePath)) {
            return $livePath;
        }

        foreach ($this->fileRootCandidates() as $rootPath) {
            $candidate = $rootPath . DIRECTORY_SEPARATOR . $relativePath;

            if (is_file($candidate)) {
                return $candidate;
            }
        }

        abort(404, 'File not found: ' . $relativePath);
    }

    protected function downloadFileName(Ebook $ebook): string
    {
        $name = $ebook->file_title
            ?: $ebook->title
            ?: pathinfo($ebook->pdf_path, PATHINFO_FILENAME)
            ?: 'ebook';

        $name = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', ' ', $name) ?? '');
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return ($name !== '' ? $name : 'ebook') . '.pdf';
    }

    protected function resolveWatermarkLogoPath(): ?string
    {
        $candidates = [
            dirname(base_path()) . DIRECTORY_SEPARATOR . 'public_html' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logo.png',
            public_path('images/logo.png'),
            base_path('public/images/logo.png'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function resolveManagedPath(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/\\');

        foreach ($this->fileRootCandidates() as $rootPath) {
            if (is_dir($rootPath)) {
                return $rootPath . DIRECTORY_SEPARATOR . $relativePath;
            }
        }

        return $this->fileRootCandidates()[0] . DIRECTORY_SEPARATOR . $relativePath;
    }

    protected function fileRootCandidates(): array
    {
        $rootFolder = trim((string) config('app.file_root', 'public'));

        if ($rootFolder === '') {
            $rootFolder = 'public';
        }

        $normalizedRoot = trim($rootFolder, '/\\');
        $candidates = [];

        if ($this->isAbsolutePath($rootFolder)) {
            $candidates[] = rtrim($rootFolder, '/\\');
        } else {
            if ($normalizedRoot === 'public') {
                $candidates[] = dirname(base_path()) . DIRECTORY_SEPARATOR . 'public_html';
                $candidates[] = public_path();
            }

            $candidates[] = base_path($normalizedRoot);
            $candidates[] = dirname(base_path()) . DIRECTORY_SEPARATOR . $normalizedRoot;
        }

        return array_values(array_unique(array_map(
            fn ($path) => rtrim($path, '/\\'),
            array_filter($candidates)
        )));
    }

    protected function isAbsolutePath(string $path): bool
    {
        return preg_match('/^(?:[A-Za-z]:[\\\\\\/]|[\\\\\\/]{2}|\\/)/', $path) === 1;
    }
}
