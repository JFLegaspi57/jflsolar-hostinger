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
        } elseif ($w - end($widths) >= 200) {
            $widths[] = $w; // well above the largest fixed size: also keep full resolution for HD screens
        }

        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0775, true)) {
            imagedestroy($img);

            return ['error' => 'The image could not be stored. Please try again.'];
        }
        $bytes = 0;
        $written = [];
        foreach ($widths as $target) {
            $resized = $target === $w ? $img : self::resize($img, $target, max(1, (int) round($h * $target / $w)));
            $out = "{$targetDir}/{$target}.webp";
            $ok = $resized && imagewebp($resized, $out, self::QUALITY);
            if ($resized && $resized !== $img) {
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

    /**
     * Profile photo: the same checks as process(), then a centred square crop saved as one
     * WebP of $size × $size pixels at $outFile. Metadata (GPS etc.) is dropped.
     *
     * @return array{bytes:int}|array{error:string}
     */
    public static function avatar(?UploadedFile $file, string $outFile, int $size = 320): array
    {
        if (! $file || ! $file->isValid() || $file->getSize() === 0) {
            return ['error' => 'Choose a photo.'];
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return ['error' => 'This photo is larger than 8 MB. Please choose a smaller one.'];
        }
        $path = $file->getRealPath();
        $info = @getimagesize($path);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_AVIF], true)) {
            return ['error' => 'This file is not a supported photo. Use JPEG, PNG or WebP.'];
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            return ['error' => 'This photo is too large to process. Please choose a smaller one.'];
        }
        $img = @imagecreatefromstring((string) file_get_contents($path));
        if (! $img) {
            return ['error' => 'This photo could not be processed. Try another one.'];
        }
        if ($info[2] === IMAGETYPE_JPEG) {
            $img = self::orient($img, $path);
        }
        imagepalettetotruecolor($img);
        $w = imagesx($img);
        $h = imagesy($img);
        $side = min($w, $h);
        $out = @imagecreatetruecolor($size, $size);
        $ok = $out && imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255))
            && imagecopyresampled($out, $img, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), $size, $size, $side, $side);
        imagedestroy($img);
        $dir = dirname($outFile);
        if (! $ok || (! is_dir($dir) && ! @mkdir($dir, 0775, true)) || ! imagewebp($out, $outFile, self::QUALITY)) {
            if ($out) {
                imagedestroy($out);
            }

            return ['error' => 'The photo could not be saved. Please try again.'];
        }
        imagedestroy($out);

        return ['bytes' => (int) filesize($outFile)];
    }

    /**
     * Downscale with imagecopyresampled. imagescale() returns false on some hosts'
     * GD builds (seen on Hostinger), which broke uploads; this works everywhere.
     */
    private static function resize(\GdImage $img, int $width, int $height): \GdImage|false
    {
        $out = @imagecreatetruecolor($width, $height);
        if (! $out) {
            return false;
        }
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagealphablending($out, true);
        if (! imagecopyresampled($out, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img))) {
            imagedestroy($out);

            return false;
        }
        imagesavealpha($out, true);

        return $out;
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
