<?php

namespace App\Support;

use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\Process;
use Throwable;

class WatermarkedPdfDownloader
{
    public function build(
        string $sourcePath,
        string $downloadName,
        ?string $watermarkImagePath = null,
        string $watermarkText = 'UNITI'
    ): array
    {
        try {
            return $this->buildWithFpdi(
                $sourcePath,
                $downloadName,
                $watermarkImagePath,
                $watermarkText
            );
        } catch (Throwable $exception) {
            return $this->buildWithRasterFallback(
                $sourcePath,
                $downloadName,
                $watermarkImagePath,
                $watermarkText
            );
        }
    }

    protected function buildWithFpdi(
        string $sourcePath,
        string $downloadName,
        ?string $watermarkImagePath,
        string $watermarkText
    ): array {
        $pdf = new class extends Fpdi
        {
            protected float $angle = 0.0;

            public function rotate(float $angle, float $x = -1, float $y = -1): void
            {
                if ($x === -1) {
                    $x = $this->x;
                }

                if ($y === -1) {
                    $y = $this->y;
                }

                if ($this->angle !== 0.0) {
                    $this->_out('Q');
                }

                $this->angle = $angle;

                if ($angle !== 0.0) {
                    $angle *= M_PI / 180;
                    $c = cos($angle);
                    $s = sin($angle);
                    $cx = $x * $this->k;
                    $cy = ($this->h - $y) * $this->k;

                    $this->_out(sprintf(
                        'q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',
                        $c,
                        $s,
                        -$s,
                        $c,
                        $cx,
                        $cy,
                        -$cx,
                        -$cy
                    ));
                }
            }

            public function _endpage(): void
            {
                if ($this->angle !== 0.0) {
                    $this->angle = 0.0;
                    $this->_out('Q');
                }

                parent::_endpage();
            }
        };

        $pageCount = $pdf->setSourceFile($sourcePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $templateSize = $pdf->getTemplateSize($templateId);
            $orientation = $templateSize['width'] > $templateSize['height'] ? 'L' : 'P';

            $pdf->AddPage($orientation, [$templateSize['width'], $templateSize['height']]);
            $pdf->useTemplate($templateId);

            $fontSize = max(14, min($templateSize['width'], $templateSize['height']) / 15);
            $pdf->SetFont('Helvetica', 'B', $fontSize);
            $pdf->SetTextColor(26, 186, 212);
            $textWidth = $pdf->GetStringWidth($watermarkText);
            $marginRight = max(10, $templateSize['width'] * 0.04);
            $marginBottom = max(12, $templateSize['height'] * 0.045);

            $logoWidth = 0.0;
            $logoHeight = 0.0;
            $logoGap = max(3, $templateSize['width'] * 0.006);

            if ($watermarkImagePath && is_file($watermarkImagePath)) {
                $maxWidth = $templateSize['width'] * 0.085;
                $maxHeight = $templateSize['height'] * 0.045;
                [$imgWidthPx, $imgHeightPx] = getimagesize($watermarkImagePath) ?: [0, 0];

                if ($imgWidthPx > 0 && $imgHeightPx > 0) {
                    $scale = min($maxWidth / $imgWidthPx, $maxHeight / $imgHeightPx);
                    $logoWidth = max(14, $imgWidthPx * $scale);
                    $logoHeight = max(10, $imgHeightPx * $scale);
                }
            }

            $groupWidth = $logoWidth > 0 ? $logoWidth + $logoGap + $textWidth : $textWidth;
            $textX = max(12, $templateSize['width'] - $groupWidth - $marginRight + ($logoWidth > 0 ? $logoWidth + $logoGap : 0));
            $textY = max($fontSize + 4, $templateSize['height'] - $marginBottom);

            if ($logoWidth > 0 && $logoHeight > 0) {
                $logoX = max(12, $templateSize['width'] - $groupWidth - $marginRight);
                $logoY = max(8, $textY - ($logoHeight * 0.72));

                $pdf->Image(
                    $watermarkImagePath,
                    $logoX,
                    $logoY,
                    $logoWidth,
                    $logoHeight
                );
            }

            $pdf->Text(
                $textX,
                $textY,
                $watermarkText
            );
        }

        return [
            'content' => $pdf->Output('S'),
            'name' => $downloadName,
        ];
    }

    protected function buildWithRasterFallback(
        string $sourcePath,
        string $downloadName,
        ?string $watermarkImagePath,
        string $watermarkText
    ): array {
        $tempDir = storage_path('app/watermarked-pdf/' . Str::random(20));
        $pagePattern = $tempDir . DIRECTORY_SEPARATOR . 'page-%04d.png';
        $outputPdf = $tempDir . DIRECTORY_SEPARATOR . 'watermarked.pdf';
        $watermarkAsset = $tempDir . DIRECTORY_SEPARATOR . 'watermark.png';
        $gsBinary = env('GS_BINARY', 'gswin64c');
        $magickBinary = env('MAGICK_BINARY', 'magick');

        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new \RuntimeException('Unable to create temporary watermark directory.');
        }

        try {
            $this->runProcess([
                $gsBinary,
                '-dSAFER',
                '-dBATCH',
                '-dNOPAUSE',
                '-sDEVICE=pngalpha',
                '-r144',
                '-dTextAlphaBits=4',
                '-dGraphicsAlphaBits=4',
                '-sOutputFile=' . $pagePattern,
                $sourcePath,
            ], $tempDir);

            $pageFiles = glob($tempDir . DIRECTORY_SEPARATOR . 'page-*.png') ?: [];
            sort($pageFiles);

            if ($pageFiles === []) {
                throw new \RuntimeException('No PDF pages were rendered for watermarking.');
            }

            $this->createWatermarkAsset(
                $magickBinary,
                $watermarkAsset,
                $watermarkImagePath,
                $watermarkText,
                $tempDir
            );

            $watermarkedPages = [];

            foreach ($pageFiles as $pageFile) {
                $pageOutput = $tempDir . DIRECTORY_SEPARATOR . 'wm-' . basename($pageFile);
                $this->runProcess([
                    $magickBinary,
                    $pageFile,
                    $watermarkAsset,
                    '-gravity',
                    'southeast',
                    '-geometry',
                    '+24+24',
                    '-composite',
                    $pageOutput,
                ], $tempDir);

                $watermarkedPages[] = $pageOutput;
            }

            $this->runProcess(array_merge([
                $magickBinary,
                '-density',
                '144',
            ], $watermarkedPages, [
                $outputPdf,
            ]), $tempDir);

            return [
                'content' => file_get_contents($outputPdf),
                'name' => $downloadName,
            ];
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    protected function createWatermarkAsset(
        string $magickBinary,
        string $watermarkAsset,
        ?string $watermarkImagePath,
        string $watermarkText,
        string $workingDirectory
    ): void {
        $command = [
            $magickBinary,
            '-background',
            'none',
        ];

        if ($watermarkImagePath && is_file($watermarkImagePath)) {
            $command = array_merge($command, [
                '(',
                $watermarkImagePath,
                '-resize',
                'x34',
                ')',
                '(',
                '-background',
                'none',
                '-fill',
                '#1abad4',
                '-font',
                'Arial',
                '-pointsize',
                '20',
                'label:' . $watermarkText,
                ')',
                '+append',
                $watermarkAsset,
            ]);
        } else {
            $command = array_merge($command, [
                '-fill',
                '#1abad4',
                '-font',
                'Arial',
                '-pointsize',
                '20',
                'label:' . $watermarkText,
                $watermarkAsset,
            ]);
        }

        $this->runProcess($command, $workingDirectory);
    }

    protected function runProcess(array $command, string $workingDirectory): void
    {
        $process = new Process($command, $workingDirectory, null, null, 300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    protected function deleteDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } elseif (is_file($path)) {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
