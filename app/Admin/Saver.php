<?php

namespace App\Admin;

use App\Support\PublicContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Shared save flow for packages, products, projects and categories:
 * Save / Save and publish / Save and unpublish, new records start as drafts,
 * and updates use optimistic concurrency (lock_version) so two people editing
 * the same item never silently overwrite each other.
 */
final class Saver
{
    public static function intent(Request $request): string
    {
        $v = $request->input('intent');

        return in_array($v, ['publish', 'unpublish'], true) ? $v : 'save';
    }

    public static function invalid(FormParser $p, string $message = 'Please fix the highlighted fields. Nothing was saved.'): RedirectResponse
    {
        return back()->withInput()->withErrors($p->errors)->with('form_error', $message);
    }

    public static function failed(string $message, array $errors = []): RedirectResponse
    {
        return back()->withInput()->withErrors($errors)->with('form_error', $message);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function save(Request $request, string $modelClass, string $path, ?Model $record, array $row): RedirectResponse
    {
        $intent = self::intent($request);
        $status = match ($intent) {
            'publish' => 'published',
            'unpublish' => 'draft',
            default => $record ? null : 'draft', // new records start as drafts
        };
        if ($status) {
            $row['status'] = $status;
        }

        try {
            if (! $record) {
                $created = DB::transaction(function () use ($modelClass, $row) {
                    $next = ((int) $modelClass::query()->lockForUpdate()->max('sort_order')) + 10;

                    return $modelClass::query()->create($row + ['sort_order' => $next]);
                });
                PublicContent::flush();

                return redirect("/admin/{$path}/{$created->getKey()}")->with('status', $intent === 'publish'
                    ? 'Saved and published. It is now visible on the public website.'
                    : 'Draft created. Add images below, then publish when ready.');
            }

            $version = (int) $request->input('lock_version', -1);
            $updated = $modelClass::query()->whereKey($record->getKey())->where('lock_version', $version)
                ->update(array_merge($row, self::encodeJson($row), ['lock_version' => $version + 1, 'updated_at' => now()]));
            if (! $updated) {
                return self::failed($modelClass::query()->whereKey($record->getKey())->exists()
                    ? 'Someone else saved this item after you opened it. Copy any changes you need, reload the page, and apply them again.'
                    : 'This item no longer exists.');
            }
        } catch (QueryException $e) {
            return self::failed(...self::dbError($e));
        }

        PublicContent::flush();

        return redirect("/admin/{$path}/{$record->getKey()}".self::backQuery($request))->with('status', match ($intent) {
            'publish' => 'Saved and published. The public page is updated.',
            'unpublish' => 'Saved and unpublished. It is now a draft and hidden from the public site.',
            default => 'Changes saved.',
        });
    }

    /** Query-builder updates skip model casts: encode array columns as JSON. */
    private static function encodeJson(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            if (is_array($v)) {
                $out[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }

        return $out;
    }

    /** Keep a validated "back to your search" link across saves. */
    public static function backQuery(Request $request): string
    {
        $back = $request->input('back');

        return is_string($back) && self::safeBack($back) ? '?back='.rawurlencode($back) : '';
    }

    /** Only ever return to an admin list page (never an arbitrary URL). */
    public static function safeBack(?string $back): bool
    {
        return is_string($back) && (bool) preg_match('#^/admin/(packages|products|projects|categories)(\?[\w=&%.+-]*)?$#', $back);
    }

    /** Turn a database error into a staff-friendly message (and field, if known). */
    public static function dbError(QueryException $e): array
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'UNIQUE') || str_contains($msg, 'Duplicate') || ($e->errorInfo[1] ?? null) === 1062) {
            if (str_contains($msg, 'slug')) {
                return ['This URL slug is already used by another item. Choose a different one.', ['slug' => 'This URL slug is already used by another item.']];
            }
            if (str_contains($msg, 'sku')) {
                return ['This SKU is already used by another item.', ['sku' => 'This SKU is already used by another item.']];
            }
            if (str_contains($msg, 'email')) {
                return ['That email address is already on the staff list.', ['email' => 'Already on the staff list.']];
            }

            return ['This value must be unique and is already in use.', []];
        }
        if (str_contains($msg, 'FOREIGN KEY') || ($e->errorInfo[1] ?? null) === 1451) {
            return ['This item is still in use by other content (for example, products in this category).', []];
        }
        report($e);

        return ['The database rejected the change. Please try again.', []];
    }
}
