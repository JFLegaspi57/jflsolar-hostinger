<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Entities;
use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Support\MediaStore;
use App\Support\PublicContent;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Status, featured, order and permanent delete — shared by packages, products, projects and categories. */
class RecordController extends Controller
{
    /** Return to the page the action came from (admin pages only), else the list. */
    private function back(Request $request, string $entity, ?string $fallback = null): RedirectResponse
    {
        $back = (string) $request->input('back', '');
        $path = parse_url($back, PHP_URL_PATH);
        $host = parse_url($back, PHP_URL_HOST);
        $safe = $path && str_starts_with($path, '/admin/') && (! $host || $host === $request->getHost());

        return redirect($safe ? $back : ($fallback ?? "/admin/{$entity}"));
    }

    public function status(Request $request, string $entity, string $id): RedirectResponse
    {
        $record = Entities::find($entity, $id);
        $status = $request->input('status');
        abort_unless(in_array($status, ['draft', 'published', 'archived'], true), 400);

        $record->forceFill(['status' => $status, 'lock_version' => $record->lock_version + 1])->save();
        PublicContent::flush();

        $messages = [
            'published' => 'Published. It is now visible on the public website.',
            'draft' => 'Unpublished. It is now a draft and hidden from the public website.',
            'archived' => 'Archived. It is hidden from the public website and can be restored or permanently deleted.',
        ];
        // Archiving from the edit page returns to the list (the item left the current view).
        $fallback = $request->input('return') === 'edit' && $status !== 'archived' ? "/admin/{$entity}/{$id}" : "/admin/{$entity}";

        return ($status === 'archived' ? redirect("/admin/{$entity}") : $this->back($request, $entity, $fallback))
            ->with('status', $messages[$status]);
    }

    public function featured(Request $request, string $entity, string $id): RedirectResponse
    {
        abort_unless(Entities::get($entity)['featured'], 404);
        $record = Entities::find($entity, $id);
        $featured = $request->boolean('featured');
        $record->forceFill(['featured' => $featured, 'lock_version' => $record->lock_version + 1])->save();
        PublicContent::flush();

        return $this->back($request, $entity)->with('status', $featured ? 'Marked as featured.' : 'Removed from featured.');
    }

    /** Move one position up/down among non-archived records, in one transaction. */
    public function move(Request $request, string $entity, string $id): RedirectResponse
    {
        $model = Entities::get($entity)['model'];
        $direction = $request->input('direction') === 'up' ? -1 : 1;

        DB::transaction(function () use ($model, $id, $direction) {
            $ids = $model::query()->where('status', '!=', 'archived')->orderBy('sort_order')->orderBy('created_at')->lockForUpdate()->pluck('id')->all();
            $i = array_search($id, $ids, true);
            abort_if($i === false, 404);
            $j = $i + $direction;
            if ($j < 0 || $j >= count($ids)) {
                return;
            }
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $pos => $rowId) {
                $model::query()->whereKey($rowId)->update(['sort_order' => ($pos + 1) * 10]);
            }
        });
        PublicContent::flush();

        return $this->back($request, $entity)->with('status', 'Order updated.');
    }

    /** Permanent deletion — only for archived records. Removes stored files too. */
    public function destroy(string $entity, string $id): RedirectResponse
    {
        $cfg = Entities::get($entity);
        $record = Entities::find($entity, $id);
        if ($record->status !== 'archived') {
            return redirect("/admin/{$entity}/{$id}")->with('status_error', 'Archive an item before deleting it permanently.');
        }
        $media = $cfg['media'] ? MediaAsset::query()->where($cfg['media'], $id)->get() : collect();

        try {
            // Atomic: only deletes if the record is still archived at this moment.
            $deleted = $cfg['model']::query()->whereKey($id)->where('status', 'archived')->delete();
        } catch (QueryException) {
            return redirect("/admin/{$entity}/{$id}")->with('status_error', 'This item is still in use (for example, a category that still has products). Move or delete those first.');
        }
        if (! $deleted) {
            return redirect("/admin/{$entity}/{$id}")->with('status_error', 'Archive an item before deleting it permanently.');
        }
        foreach ($media as $m) {
            MediaStore::deleteFiles($m); // rows are removed by ON DELETE CASCADE
        }
        PublicContent::flush();

        return redirect("/admin/{$entity}?view=archived")->with('status', 'Permanently deleted.');
    }
}
