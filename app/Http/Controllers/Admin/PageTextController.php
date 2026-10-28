<?php

namespace App\Http\Controllers\Admin;

use App\Admin\FormState;
use App\Admin\Saver;
use App\Http\Controllers\Controller;
use App\Models\PageText as PageTextRow;
use App\Support\PageText;
use App\Support\PageTextData;
use App\Support\PublicContent;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin → Page text: the wording on Home, Solar Services and About. Unchanged or
 * emptied fields fall back to the original copy (no row stored).
 */
class PageTextController extends Controller
{
    private const STALE = 'Someone else changed this page’s text after you opened it. Copy any changes you need, reload the page, and apply them again.';

    private const NOT_MIGRATED = 'Page text editing is not switched on yet: import upgrade-2026-10-28.sql in phpMyAdmin first. The website shows the original wording meanwhile.';

    /** PHP turns dots in submitted field names into underscores, so fields use "__". */
    public static function fieldName(string $key): string
    {
        return str_replace('.', '__', $key);
    }

    public function edit(Request $request, string $page): View
    {
        $def = PageText::page($page) ?? abort(404);
        try {
            $loaded = PageText::load($def);
        } catch (QueryException) {
            return view('admin.page-text', ['page' => $def, 'missing' => true]);
        }
        $values = [];
        foreach (PageText::effective($def, $loaded['rows']) as $k => $v) {
            $values[self::fieldName($k)] = $v;
        }

        // After a failed save the form shows the submitted values again, so it must keep
        // the version those values were based on — never the newest one, or a second
        // click on Save would silently overwrite someone else's changes.
        $old = $request->session()->getOldInput('version');

        return view('admin.page-text', [
            'page' => $def,
            'missing' => false,
            'version' => is_string($old) ? $old : $loaded['version'],
            'form' => new FormState($values, $request->session()->get('errors')),
        ]);
    }

    public function update(Request $request, string $page): RedirectResponse
    {
        $def = PageText::page($page) ?? abort(404);

        $errors = [];
        $wanted = []; // key => value, or null to use the original wording
        foreach (PageText::fields($def) as $field) {
            $raw = $request->input(self::fieldName($field['key']));
            $res = PageText::clean($field, is_string($raw) ? $raw : '');
            if (isset($res['error'])) {
                $errors[self::fieldName($field['key'])] = $res['error'];

                continue;
            }
            $wanted[$field['key']] = $res['value'] === '' || $res['value'] === PageTextData::DEFAULTS[$field['key']] ? null : $res['value'];
        }
        if ($errors) {
            return Saver::failed('Please fix the highlighted fields. Nothing was saved.', $errors);
        }

        $submittedVersion = (string) $request->input('version');
        try {
            // One save at a time; the version is re-checked inside the lock, so two
            // people saving together cannot silently overwrite each other.
            $changed = Cache::lock('admin:page-text-save', 15)->block(10, fn () => DB::transaction(function () use ($def, $wanted, $submittedVersion, $request) {
                $current = PageText::load($def);
                if ($current['version'] !== $submittedVersion) {
                    return 'stale';
                }
                $remove = array_keys(array_filter($wanted, fn ($v, $k) => $v === null && array_key_exists($k, $current['rows']), ARRAY_FILTER_USE_BOTH));
                $set = array_filter($wanted, fn ($v, $k) => $v !== null && ($current['rows'][$k] ?? null) !== $v, ARRAY_FILTER_USE_BOTH);
                if ($remove) {
                    PageTextRow::query()->whereIn('key', $remove)->delete();
                }
                foreach ($set as $key => $value) {
                    PageTextRow::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $request->user()->id, 'updated_at' => now()]);
                }

                return ($remove || $set) ? 'saved' : 'unchanged';
            }));
        } catch (LockTimeoutException) {
            return Saver::failed('Someone else is saving page text right now. Please try again in a moment.');
        } catch (QueryException $e) {
            return Saver::failed(str_contains($e->getMessage(), 'page_texts') ? self::NOT_MIGRATED : 'The text could not be saved. Please try again.');
        }

        if ($changed === 'stale') {
            return Saver::failed(self::STALE);
        }
        if ($changed === 'saved') {
            PublicContent::flush();
        }

        return redirect("/admin/text/{$def['slug']}")->with('status', $changed === 'saved' ? "Saved. The {$def['name']} page shows the new wording." : 'No changes to save.');
    }
}
