<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Models\Product;
use App\Models\Project;
use App\Support\ImageProcessor;
use App\Support\MediaStore;
use App\Support\PublicContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Images (packages, products, projects, content blocks) and product datasheets.
 * These save separately from the main edit form and apply immediately.
 */
class MediaAdminController extends Controller
{
    private const OWNERS = [
        'package' => ['column' => 'package_id', 'folder' => 'packages', 'model' => Package::class, 'edit' => '/admin/packages/'],
        'product' => ['column' => 'product_id', 'folder' => 'products', 'model' => Product::class, 'edit' => '/admin/products/'],
        'project' => ['column' => 'project_id', 'folder' => 'projects', 'model' => Project::class, 'edit' => '/admin/projects/'],
        'block' => ['column' => 'content_block_key', 'folder' => 'blocks', 'model' => ContentBlock::class, 'edit' => '/admin/content/'],
    ];

    private function ownerOf(MediaAsset $m): array
    {
        foreach (self::OWNERS as $type => $cfg) {
            if ($m->{$cfg['column']} !== null) {
                return [$type, $cfg, $m->{$cfg['column']}];
            }
        }
        abort(404);
    }

    private function done(string $editUrl, string $message, bool $error = false): RedirectResponse
    {
        PublicContent::flush();

        return redirect($editUrl.'#images')->with($error ? 'media_error' : 'media_status', $message);
    }

    public function upload(Request $request, string $type, string $owner): RedirectResponse
    {
        $cfg = self::OWNERS[$type] ?? abort(404);
        $record = $cfg['model']::query()->find($owner) ?? abort(404);
        $edit = $cfg['edit'].$record->getKey();

        $alt = trim((string) $request->input('alt'));
        $caption = trim((string) $request->input('caption')) ?: null;
        $errors = [];
        if (mb_strlen($alt) < 3) {
            $errors['alt'] = 'Alternative text is required (at least 3 characters).';
        } elseif (mb_strlen($alt) > 300) {
            $errors['alt'] = 'Alternative text must be 300 characters or fewer.';
        }
        if ($caption && mb_strlen($caption) > 300) {
            $errors['caption'] = 'Caption must be 300 characters or fewer.';
        }
        if (! $request->hasFile('file')) {
            $errors['file'] = 'Choose an image to upload.';
        }
        if ($errors) {
            return redirect($edit.'#images')->withInput($request->only('alt', 'caption'))->withErrors($errors, 'upload')->with('media_error', 'Please fix the highlighted fields.');
        }

        $assetId = (string) Str::uuid();
        $folderId = $type === 'block' ? (string) Str::uuid() : $record->getKey();
        $key = "{$cfg['folder']}/{$folderId}/{$assetId}";
        $img = ImageProcessor::process($request->file('file'), MediaStore::path($key));
        if (isset($img['error'])) {
            return redirect($edit.'#images')->withInput($request->only('alt', 'caption'))->withErrors(['file' => $img['error']], 'upload')->with('media_error', $img['error']);
        }

        DB::transaction(function () use ($cfg, $type, $record, $assetId, $key, $alt, $caption, $img, $request) {
            $existing = MediaAsset::query()->where($cfg['column'], $record->getKey())->where('kind', 'image')->lockForUpdate()->get(['sort_order', 'is_cover']);
            MediaAsset::query()->create([
                'id' => $assetId, 'kind' => 'image', 'storage_key' => $key, $cfg['column'] => $record->getKey(),
                'alt' => $alt, 'caption' => $caption, 'file_name' => mb_substr((string) $request->file('file')->getClientOriginalName(), 0, 200),
                'widths' => $img['widths'], 'width' => $img['width'], 'height' => $img['height'], 'bytes' => $img['bytes'],
                'is_cover' => $type !== 'block' && ! $existing->contains('is_cover', true),
                'sort_order' => ((int) $existing->max('sort_order')) + 10,
            ]);
        });

        return $this->done($edit, 'Image uploaded.');
    }

    /** Replace the file, keeping alt text, caption, order and cover. New key, so caches of the old file are not reused. */
    public function replace(Request $request, string $asset): RedirectResponse
    {
        $m = MediaAsset::query()->where('kind', 'image')->find($asset) ?? abort(404);
        [, $cfg, $ownerKey] = $this->ownerOf($m);
        $edit = $cfg['edit'].$ownerKey;
        [$folder, $owner] = explode('/', $m->storage_key);
        $key = "{$folder}/{$owner}/".Str::uuid();
        $img = ImageProcessor::process($request->file('file'), MediaStore::path($key));
        if (isset($img['error'])) {
            return redirect($edit.'#img-'.$m->id)->withErrors(['file' => $img['error']], 'rep'.$m->id);
        }
        $old = clone $m;
        $updated = MediaAsset::query()->whereKey($m->id)->where('storage_key', $m->storage_key)->update([
            'storage_key' => $key, 'widths' => json_encode($img['widths']), 'width' => $img['width'], 'height' => $img['height'], 'bytes' => $img['bytes'],
            'file_name' => mb_substr((string) $request->file('file')->getClientOriginalName(), 0, 200), 'updated_at' => now(),
        ]);
        if (! $updated) {
            MediaStore::deleteFiles(new MediaAsset(['storage_key' => $key, 'kind' => 'image', 'widths' => $img['widths']]));

            return $this->done($edit, 'This image was changed by someone else. Reload the page and try again.', true);
        }
        MediaStore::deleteFiles($old);

        return $this->done($edit, 'Image replaced.');
    }

    public function text(Request $request, string $asset): RedirectResponse
    {
        $m = MediaAsset::query()->find($asset) ?? abort(404);
        [, $cfg, $ownerKey] = $this->ownerOf($m);
        $alt = trim((string) $request->input('alt'));
        $caption = trim((string) $request->input('caption')) ?: null;
        if (mb_strlen($alt) < 3 || mb_strlen($alt) > 300 || ($caption && mb_strlen($caption) > 300)) {
            return redirect($cfg['edit'].$ownerKey.'#img-'.$m->id)->withInput(['asset' => $m->id, 'alt' => $alt, 'caption' => $caption])
                ->withErrors(['alt' => mb_strlen($alt) < 3 ? 'Alternative text must be at least 3 characters.' : 'Alternative text and caption must be 300 characters or fewer.'], 'img'.$m->id);
        }
        $m->forceFill(['alt' => $alt, 'caption' => $caption])->save();
        PublicContent::flush();

        return redirect($cfg['edit'].$ownerKey.'#img-'.$m->id)->with('media_status', 'Image details saved.');
    }

    /** Cover / earlier / later / delete. */
    public function op(Request $request, string $asset): RedirectResponse
    {
        $m = MediaAsset::query()->find($asset) ?? abort(404);
        [, $cfg, $ownerKey] = $this->ownerOf($m);
        $edit = $cfg['edit'].$ownerKey;
        $op = $request->input('op');
        $siblings = fn () => MediaAsset::query()->where($cfg['column'], $ownerKey)->where('kind', $m->kind)->orderBy('sort_order')->orderBy('created_at');

        if ($op === 'cover') {
            DB::transaction(function () use ($siblings, $m) {
                $siblings()->lockForUpdate()->get();
                $siblings()->where('id', '!=', $m->id)->update(['is_cover' => false]);
                MediaAsset::query()->whereKey($m->id)->update(['is_cover' => true]);
            });

            return $this->done($edit, 'Cover image updated.');
        }
        if ($op === 'up' || $op === 'down') {
            DB::transaction(function () use ($siblings, $m, $op) {
                $ids = $siblings()->lockForUpdate()->pluck('id')->all();
                $i = array_search($m->id, $ids, true);
                $j = $i + ($op === 'up' ? -1 : 1);
                if ($i === false || $j < 0 || $j >= count($ids)) {
                    return;
                }
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                foreach ($ids as $pos => $id) {
                    MediaAsset::query()->whereKey($id)->update(['sort_order' => ($pos + 1) * 10]);
                }
            });

            return $this->done($edit, 'Order updated.');
        }
        if ($op === 'delete') {
            $wasCover = $m->is_cover;
            $deleted = MediaAsset::query()->whereKey($m->id)->delete();
            if (! $deleted) {
                return $this->done($edit, 'This image was already removed.', true);
            }
            MediaStore::deleteFiles($m);
            if ($wasCover && ($next = $siblings()->first())) {
                $next->forceFill(['is_cover' => true])->save();
            }

            return $this->done($edit, 'Image deleted.');
        }
        abort(400);
    }

    /** Upload or replace a product datasheet (PDF). The working datasheet is kept until the new one is stored. */
    public function datasheet(Request $request, string $product): RedirectResponse
    {
        return $this->storePdf($request, $product, 'datasheet');
    }

    public function deleteDatasheet(string $product): RedirectResponse
    {
        return $this->removePdf($product, 'datasheet');
    }

    /** Upload or replace a product user manual (PDF). Works the same way as the datasheet. */
    public function manual(Request $request, string $product): RedirectResponse
    {
        return $this->storePdf($request, $product, 'manual');
    }

    public function deleteManual(string $product): RedirectResponse
    {
        return $this->removePdf($product, 'manual');
    }

    /** Stores a product PDF of the given kind (datasheet | manual); the working file is kept until the new one is stored. */
    private function storePdf(Request $request, string $product, string $kind): RedirectResponse
    {
        $label = self::PDF_LABELS[$kind];
        $p = Product::query()->find($product) ?? abort(404);
        $edit = '/admin/products/'.$p->id;
        $check = ImageProcessor::checkPdf($request->file('file'));
        if (isset($check['error'])) {
            return $this->sheetDone($edit, $kind, $check['error'], true);
        }
        $old = MediaAsset::query()->where('product_id', $p->id)->where('kind', $kind)->first();
        $key = "products/{$p->id}/".Str::uuid();
        $dir = MediaStore::path($key);
        if (! @mkdir($dir, 0775, true) || ! $request->file('file')->move($dir, MediaAsset::PDF_FILES[$kind])) {
            return $this->sheetDone($edit, $kind, "The {$label} could not be stored. Please try again.", true);
        }
        $name = mb_substr((string) $request->file('file')->getClientOriginalName(), 0, 200);
        if ($old) {
            $ok = MediaAsset::query()->whereKey($old->id)->where('storage_key', $old->storage_key)->update(['storage_key' => $key, 'file_name' => $name, 'bytes' => $check['bytes'], 'updated_at' => now()]);
            if (! $ok) {
                MediaStore::deleteFiles(new MediaAsset(['storage_key' => $key, 'kind' => $kind]));

                return $this->sheetDone($edit, $kind, "The {$label} was changed by someone else. Reload and try again.", true);
            }
            MediaStore::deleteFiles($old);
        } else {
            MediaAsset::query()->create(['kind' => $kind, 'storage_key' => $key, 'product_id' => $p->id, 'alt' => '', 'file_name' => $name, 'bytes' => $check['bytes'], 'widths' => []]);
        }

        PublicContent::flush();

        return $this->sheetDone($edit, $kind, ucfirst($label).($old ? ' replaced.' : ' uploaded.'));
    }

    private function removePdf(string $product, string $kind): RedirectResponse
    {
        $m = MediaAsset::query()->where('product_id', $product)->where('kind', $kind)->first() ?? abort(404);
        $m->delete();
        MediaStore::deleteFiles($m);
        PublicContent::flush();

        return $this->sheetDone('/admin/products/'.$product, $kind, ucfirst(self::PDF_LABELS[$kind]).' removed.');
    }

    private const PDF_LABELS = ['datasheet' => 'datasheet', 'manual' => 'user manual'];

    /** PDF feedback is shown in that PDF's section (#datasheet or #manual), next to its controls. */
    private function sheetDone(string $edit, string $kind, string $message, bool $error = false): RedirectResponse
    {
        $to = redirect($edit.'#'.$kind);

        return $error ? $to->withErrors(['file' => $message], $kind) : $to->with($kind.'_status', $message);
    }
}
