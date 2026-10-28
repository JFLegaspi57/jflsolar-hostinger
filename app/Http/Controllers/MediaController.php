<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Support\MediaStore;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /** Public media: only while the owning item is public. Short cache so unpublished files disappear quickly. */
    public function public(string $path): BinaryFileResponse
    {
        [$asset, $file, $type] = $this->resolve($path);
        abort_unless(MediaStore::isPublic($asset), 404);

        return $this->send($asset, $file, $type, 'public, max-age=60, s-maxage=300');
    }

    /** Staff media (drafts included). Route is behind the admin middleware. */
    public function admin(string $path): BinaryFileResponse
    {
        [$asset, $file, $type] = $this->resolve($path);

        return $this->send($asset, $file, $type, 'private, no-store');
    }

    private function resolve(string $path): array
    {
        $parsed = MediaStore::parse($path) ?? abort(404);
        [$key, $file, $type] = $parsed;
        $asset = MediaAsset::query()->where('storage_key', $key)->first() ?? abort(404);
        abort_unless(in_array($file, $asset->files(), true), 404);

        return [$asset, $file, $type];
    }

    private function send(MediaAsset $asset, string $file, string $type, string $cache): BinaryFileResponse
    {
        $full = MediaStore::path($asset->storage_key, $file);
        abort_unless(is_file($full), 404);
        $headers = ['Content-Type' => $type, 'Cache-Control' => $cache, 'X-Content-Type-Options' => 'nosniff'];
        if ($type === 'application/pdf') {
            $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $asset->file_name ?: 'datasheet.pdf');
            $headers['Content-Disposition'] = 'inline; filename="'.$name.'"';
        }

        return response()->file($full, $headers);
    }
}
