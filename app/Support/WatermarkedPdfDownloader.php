<?php

namespace App\Support;

use setasign\Fpdi\Fpdi;

class WatermarkedPdfDownloader
{
    public function build(
        string $sourcePath,
        string $downloadName,
        ?string $watermarkImagePath = null,
        string $watermarkText = 'UNITI'
    ): array
    {
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
}
