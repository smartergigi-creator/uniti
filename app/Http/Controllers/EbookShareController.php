<?php

namespace App\Http\Controllers;

use App\Models\Ebook;
use App\Support\WatermarkedPdfDownloader;
use App\Models\User;
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

    // 🔥 IMPORTANT FIX — Check inside public_html
    // $pdfPath = base_path('../public_html/' . $ebook->pdf_path);

    $rootFolder = config('app.file_root');

    if ($rootFolder == 'public') {
        $pdfPath = base_path("public/" . $ebook->pdf_path);
    } else {
        $pdfPath = base_path("../public_html/" . $ebook->pdf_path);
    }



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

    $payload = $downloader->build(
        $pdfPath,
        Str::slug($ebook->title ?: 'ebook') . '.pdf',
        public_path('images/logo.png'),
        'UNITI'
    );

    return response($payload['content'], 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $payload['name'] . '"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ]);
}

protected function resolvePdfPath(string $pdfPath): string
{
    $rootFolder = config('app.file_root');

    if ($rootFolder === 'public') {
        return base_path('public/' . ltrim($pdfPath, '/'));
    }

    return base_path('../public_html/' . ltrim($pdfPath, '/'));
}
}
