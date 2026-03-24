<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use App\Models\User;
use App\Support\WatermarkedPdfDownloader;
use Illuminate\Support\Str;

class EbookShareController extends Controller
{
    public function generate($id)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $hasShareAccess = $user->role === 'admin' || (bool) $user->can_share;
            if (!$hasShareAccess) {
                return response()->json([
                    'status' => false,
                    'message' => 'You are not allowed to share',
                ], 403);
            }

            $ebook = Ebook::find($id);
            if (!$ebook) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ebook not found',
                ], 404);
            }

            // Same user re-sharing same ebook: regenerate token, don't consume extra slot.
            if ((int) $ebook->share_enabled === 1 && (int) $ebook->shared_by === (int) $user->id) {
                $ebook->share_token = Str::random(40);
                $ebook->share_expires_at = now()->addDays(7);
                $ebook->current_views = 0;
                $ebook->max_views = (int) $user->share_limit > 0 ? (int) $user->share_limit : null;
                $ebook->save();

                return response()->json([
                    'status' => true,
                    'publicLink' => url('/flip-book/' . $ebook->share_token),
                    'expires_at' => $ebook->share_expires_at,
                    'message' => 'New link generated',
                ]);
            }

            // Admin has unlimited share access; users follow their configured limits.
            $userShareLimit = $user->role === 'admin' ? 0 : (int) $user->share_limit;
            if ($userShareLimit > 0) {
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

                if ($activeShares >= $userShareLimit) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Share limit reached',
                    ], 403);
                }
            }

            $ebook->share_token = Str::random(40);
            $ebook->share_expires_at = now()->addDays(7);
            $ebook->share_enabled = 1;
            $ebook->shared_by = $user->id;
            $ebook->current_views = 0;
            $ebook->max_views = $userShareLimit > 0 ? $userShareLimit : null;
            $ebook->save();

            return response()->json([
                'status' => true,
                'publicLink' => url('/flip-book/' . $ebook->share_token),
                'expires_at' => $ebook->share_expires_at,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Share failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function view($token)
    {
        $ebook = Ebook::where('share_token', $token)
            ->where('share_enabled', 1)
            ->first();
        $reportRecipients = collect();

        if (!$ebook) {
            return view('ebook/errors.share-invalid');
        }

        if ($ebook->share_expires_at && now()->gt($ebook->share_expires_at)) {
            return view('ebook/errors.share-expired');
        }

        if (
            $ebook->max_views !== null &&
            (int) $ebook->max_views > 0 &&
            $ebook->current_views >= $ebook->max_views
        ) {
            return view('ebook/errors.limit-reached');
        }

        $ebook->increment('current_views');

        $pdfPath = $this->resolvePdfPath($ebook->pdf_path);

        if (!file_exists($pdfPath)) {
            return view('ebook/errors.share-invalid');
        }

        if (auth()->check()) {
            $reportRecipients = User::query()
                ->where('status', 'active')
                ->whereKeyNot(auth()->id())
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        $downloadUrl = route('ebook.share.download', $token);

        return view('ebook.flipbook', compact('ebook', 'reportRecipients', 'downloadUrl'));
    }

    public function download($token, WatermarkedPdfDownloader $downloader)
    {
        $ebook = Ebook::where('share_token', $token)
            ->where('share_enabled', 1)
            ->first();

        if (!$ebook) {
            abort(404, 'Shared ebook not found');
        }

        if ($ebook->share_expires_at && now()->gt($ebook->share_expires_at)) {
            abort(403, 'Share link expired');
        }

        if (
            $ebook->max_views !== null &&
            (int) $ebook->max_views > 0 &&
            $ebook->current_views >= $ebook->max_views
        ) {
            abort(403, 'Share limit reached');
        }

        $pdfPath = $this->resolvePdfPath($ebook->pdf_path);

        abort_unless(is_file($pdfPath), 404, 'PDF file not found');

        $ebook->increment('current_views');

        try {
            $payload = $downloader->build(
                $pdfPath,
                $this->downloadFileName($ebook),
                public_path('images/logo.png'),
                'UNITI'
            );

            return response($payload['content'], 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $payload['name'] . '"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
            ]);
        } catch (\Throwable $e) {
            return response()->download($pdfPath, $this->downloadFileName($ebook));
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
