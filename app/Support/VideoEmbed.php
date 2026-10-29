<?php

namespace App\Support;

/**
 * Turns a YouTube or Facebook video link (as pasted by staff) into a safe embed address.
 * Only these two services are accepted; anything else is rejected when the project is saved.
 */
final class VideoEmbed
{
    public const MAX_LINKS = 5;

    /** One link per line → trimmed, non-empty lines. */
    public static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text)), fn ($l) => $l !== ''));
    }

    /** @return array{src: string, vertical: bool, service: string}|null */
    public static function parse(string $url): ?array
    {
        $url = trim($url);
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }
        $parts = parse_url($url);
        $host = strtolower(preg_replace('/^(www\.|m\.|web\.)/', '', $parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        // YouTube
        $id = null;
        $vertical = false;
        if ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            if (preg_match('#^/(embed|shorts|live|v)/([^/?]+)#', $path, $m)) {
                $id = $m[2];
                $vertical = $m[1] === 'shorts';
            } else {
                $id = $query['v'] ?? null;
            }
        }
        if ($id !== null) {
            return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)
                ? ['src' => 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0', 'vertical' => $vertical, 'service' => 'YouTube']
                : null;
        }

        // Facebook (videos, reels, watch links and fb.watch short links)
        if ($host === 'fb.watch' || ($host === 'facebook.com' && preg_match('#/(videos|reel|reels|watch|share/v|share/r)\b#', $path))) {
            $clean = 'https://'.($host === 'fb.watch' ? 'fb.watch' : 'www.facebook.com').$path.(isset($query['v']) ? '?v='.rawurlencode($query['v']) : '');
            $vertical = (bool) preg_match('#/(reel|reels|share/r)\b#', $path);

            return ['src' => 'https://www.facebook.com/plugins/video.php?href='.rawurlencode($clean).'&show_text=false', 'vertical' => $vertical, 'service' => 'Facebook'];
        }

        return null;
    }

    /** @return list<array{src: string, vertical: bool, service: string}> */
    public static function all(?string $text): array
    {
        return array_values(array_filter(array_map([self::class, 'parse'], self::lines($text))));
    }
}
