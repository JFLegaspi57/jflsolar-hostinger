<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Entities;
use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\View\View;

/** Staff-only preview of any package/product/project (drafts included) using the public templates. */
class PreviewController extends Controller
{
    public function show(string $entity, string $id): View
    {
        $record = Entities::find($entity, $id);
        $media = $record->media()->get();
        $images = $media->where('kind', 'image')->values();
        if ($entity === 'packages') {
            $record->promo_state = $record->promoState();
            $record->images = $images;
        } elseif ($entity === 'products') {
            $record->load('category');
            $record->images = $images;
            $record->datasheet = $media->firstWhere('kind', 'datasheet');
        } else {
            $record->photos = $images;
            $record->cover = $images->firstWhere('is_cover', true) ?? $images->first();
        }

        return view('admin.preview', ['entity' => $entity, 'record' => $record, 'settings' => SiteSetting::query()->find(1)]);
    }
}
