<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;

/**
 * Validates an uploaded image by decoding it (the browser-supplied type is not
 * trusted), applies the camera's EXIF orientation, and writes WebP variants at
 * fixed widths without upscaling. Re-encoding drops all metadata (GPS etc.).
 * Uses GD, which every PHP host including Hostinger provides.
 */
final class ImageProcessor
{
    public const MAX_BYTES = 8 * 1024 * 1024;

    private const MAX_PIXELS = 60_000_000;

    private const QUALITY = 82;

    /** @return array{width:int,height:int,widths:list<int>,bytes:int}|array{error:string} */
    public static function process(?UploadedFile $file, string $targetDir): array
    {
        if (! $file || ! $file->isValid() || $file->getSize() === 0) {
            return ['error' => 'Choose an image file.'];
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return ['error' => 'This image is larger than 8 MB. Please choose a smaller file.'];
        }
        $path = $file->getRealPath();
        $info = @getimagesize($path);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_AVIF], true)) {
            return ['error' => 'This file is not a supported image. Use JPEG, PNG, WebP or AVIF.'];
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return ['error' => 'This image is too large to process. Export it at a smaller size and try again.'];
        }

        $img = @imagecreatefromstring((string) file_get_contents($path));
        if (! $img) {
            return ['error' => 'This image could not be processed. It may be damaged — try exporting it again as JPEG or PNG.'];
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $img = self::orient($img, $path);
        }
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        $w = imagesx($img);
        $h = imagesy($img);
        $widths = array_values(array_filter(MediaAsset::IMAGE_WIDTHS, fn ($t) => $t <= $w));
        if (! $widths) {
            $widths = [$w]; // smaller than the smallest variant: keep its own size
        }

        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true)) {
            imagedestroy($img);

            return ['error' => 'The image could not be stored. Please try again.'];
        }
        $bytes = 0;
        $written = [];
        foreach ($widths as $target) {
            $resized = $target === $w ? $img : imagescale($img, $target, (int) round($h * $target / $w), IMG_BICUBIC);
            imagesavealpha($resized, true);
            $out = "{$targetDir}/{$target}.webp";
            $ok = imagewebp($resized, $out, self::QUALITY);
            if ($resized !== $img) {
                imagedestroy($resized);
            }
            if (! $ok) {
                foreach ($written as $f) {
                    @unlink($f);
                }
                imagedestroy($img);

                return ['error' => 'The image could not be stored. Please try again.'];
            }
            $written[] = $out;
            $bytes = max($bytes, (int) filesize($out));
        }
        imagedestroy($img);

        return ['width' => $w, 'height' => $h, 'widths' => $widths, 'bytes' => $bytes];
    }

    /** Rotate/flip a JPEG according to its EXIF Orientation tag. */
    private static function orient(\GdImage $img, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }
        $o = (int) (@exif_read_data($path)['Orientation'] ?? 1);
        $rotated = match ($o) {
            3, 4 => imagerotate($img, 180, 0),
            5, 6 => imagerotate($img, -90, 0),
            7, 8 => imagerotate($img, 90, 0),
            default => $img,
        };
        if (in_array($o, [2, 4, 5, 7], true)) {
            imageflip($rotated, IMG_FLIP_HORIZONTAL);
        }

        return $rotated;
    }

    /** Validates a PDF by its signature. Returns bytes or an error. */
    public static function checkPdf(?UploadedFile $file): array
    {
        if (! $file || ! $file->isValid() || $file->getSize() === 0) {
            return ['error' => 'Choose a PDF file.'];
        }
        if ($file->getSize() > 4 * 1024 * 1024) {
            return ['error' => 'This PDF is larger than 4 MB. Please choose a smaller file.'];
        }
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 5);
        if ($head !== '%PDF-') {
            return ['error' => 'This file is not a PDF.'];
        }

        return ['bytes' => (int) $file->getSize()];
    }
}
