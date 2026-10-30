<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * An image or datasheet. Files: storage/app/media/<storage_key>/<width>.webp
 * or …/datasheet.pdf. Public visibility follows the owning item.
 */
class MediaAsset extends Model
{
    use HasUuids;

    public const IMAGE_WIDTHS = [640, 1280, 2000];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'widths' => 'array',
            'is_cover' => 'boolean',
            'sort_order' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'bytes' => 'integer',
        ];
    }

    /** Product PDFs: kind => stored file name. */
    public const PDF_FILES = ['datasheet' => 'datasheet.pdf', 'manual' => 'manual.pdf'];

    /** File names stored for this asset. */
    public function files(): array
    {
        return isset(self::PDF_FILES[$this->kind])
            ? [self::PDF_FILES[$this->kind]]
            : array_map(fn ($w) => "{$w}.webp", $this->widths ?? []);
    }

    /** URL of one variant: /media/… for the public, /admin/media/… for staff (drafts). */
    public function url(?int $width = null, bool $admin = false): string
    {
        $file = self::PDF_FILES[$this->kind] ?? ($width ?? $this->bestWidth(1280)).'.webp';

        return ($admin ? '/admin/media/' : '/media/').$this->storage_key.'/'.$file;
    }

    /** Smallest stored width at least $target, else the largest. */
    public function bestWidth(int $target): int
    {
        $widths = $this->widths ?: [640];
        sort($widths);
        foreach ($widths as $w) {
            if ($w >= $target) {
                return $w;
            }
        }

        return end($widths);
    }

    public function srcset(bool $admin = false): string
    {
        return collect($this->widths ?? [])->sort()->map(fn ($w) => $this->url($w, $admin)." {$w}w")->implode(', ');
    }
}
