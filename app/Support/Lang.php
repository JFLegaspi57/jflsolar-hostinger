<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * English / Filipino choice from the flags in the header (cookie "lang", one year).
 * Pages with a Filipino version (FAQ, the three guides, translated blog posts and projects) open in
 * Filipino when it is chosen; every other page stays in English. Buckny replies in the
 * chosen language.
 */
final class Lang
{
    public const COOKIE = 'lang';

    /** English pages that have a Filipino version at "<path>/tagalog". */
    public const TAGALOG_PAGES = ['/faq', '/net-metering', '/hybrid-pv-system-guide', '/grid-tied-pv-system-guide'];

    public static function current(?Request $request = null): string
    {
        $request ??= request();

        return $request->cookie(self::COOKIE) === 'fil' ? 'fil' : 'en';
    }

    /** The Filipino address of an English page, or null if it has none. */
    public static function tagalogPath(string $path): ?string
    {
        $path = '/'.trim($path, '/');
        if (in_array($path, self::TAGALOG_PAGES, true)) {
            return $path.'/tagalog';
        }
        if (preg_match('#^/courses/'.Course::SLUG.'/([a-z0-9-]+)$#', $path, $m)) {
            try {
                return Course::hasTagalog(Course::lesson($m[1], true)) ? $path.'/tagalog' : null;
            } catch (\Throwable) {
                return null;
            }
        }
        if (preg_match('#^/projects/([a-z0-9-]+)$#', $path, $m)) {
            try {
                ProjectTl::ensureColumns();
                $project = PublicContent::project($m[1]);
            } catch (\Throwable) {
                return null;
            }

            return $project && ProjectTl::tagalog($project) ? $path.'/tagalog' : null;
        }
        if (preg_match('#^/blog/([a-z0-9-]+)$#', $path, $m)) {
            try {
                $post = Blog::find($m[1]);
            } catch (\Throwable) {
                return null;
            }

            return $post && Blog::tagalog($post) ? $path.'/tagalog' : null;
        }

        return null;
    }

    public static function englishPath(string $path): string
    {
        return preg_replace('#/tagalog$#', '', '/'.trim($path, '/')) ?: '/';
    }

    /** Where to go after choosing a language on $back (a local path, query string kept). */
    public static function target(string $lang, string $back): string
    {
        $back = (str_starts_with($back, '/') && ! str_starts_with($back, '//')) ? $back : '/';
        $path = (string) parse_url($back, PHP_URL_PATH);
        $query = parse_url($back, PHP_URL_QUERY);
        $to = $lang === 'fil' ? (self::tagalogPath($path) ?? $path) : self::englishPath($path);

        return $to.($query ? '?'.$query : '');
    }

    /** Link that switches to $lang and returns to the current page. */
    public static function switchUrl(string $lang): string
    {
        return '/lang/'.$lang.'?back='.rawurlencode(request()->getRequestUri());
    }
}
