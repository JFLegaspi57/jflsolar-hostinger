<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * One-time move of the original site's content (Supabase) into this Laravel
 * site. Reads with the Supabase secret key (from the environment, never
 * printed). Two ways to use it:
 *
 *   php artisan jfl:copy-from-supabase --export=storage/app/export
 *       Writes data.sql (MySQL INSERTs for phpMyAdmin) and media.zip (photos
 *       and datasheets, to extract into jflsolar-app/storage/app/) — for hosts
 *       where only phpMyAdmin and File Manager are available.
 *
 *   php artisan jfl:copy-from-supabase --import
 *       Inserts straight into the configured database and copies files into
 *       storage/app/media (local verification, or hosts with SSH).
 *
 * Also copies page text edits and website inquiries when the original has them.
 * Staff come across with email, name, role and active state but no password:
 * an owner sets theirs with the recovery page, then resets the others.
 */
class ExportSupabase extends Command
{
    protected $signature = 'jfl:copy-from-supabase {--export= : Folder to write data.sql and media.zip} {--import : Insert into the configured database instead} {--force : Import even if the database already has content}';

    protected $description = 'Copy content, staff list and media from the original Supabase project';

    /** Columns carried over per table (order = insert order; parents first). */
    private const TABLES = [
        'site_settings' => ['id', 'phone_display', 'phone_e164', 'messaging_display', 'whatsapp_e164', 'whatsapp_enabled', 'viber_e164', 'viber_enabled', 'facebook_url', 'facebook_enabled', 'messenger_url', 'messenger_enabled', 'public_email', 'office_address', 'warehouse_address', 'service_area', 'service_area_note', 'business_hours', 'general_warranty', 'updated_at'],
        'content_blocks' => ['key', 'section', 'title', 'subtitle', 'body', 'claim_status', 'review_note', 'published', 'sort_order', 'updated_at'],
        'product_categories' => ['id', 'slug', 'name', 'description', 'status', 'sort_order', 'created_at', 'updated_at'],
        'packages' => ['id', 'slug', 'sku', 'name', 'summary', 'description', 'package_type', 'system_type', 'inverter_kw', 'array_kwp', 'battery_kwh', 'components', 'inclusions', 'exclusions', 'pricing_mode', 'price_php', 'price_basis', 'price_is_sample', 'installation_terms', 'fulfillment', 'fulfillment_notes', 'promo_label', 'promo_start', 'promo_end', 'hide_when_expired', 'warranty_terms', 'admin_notes', 'status', 'featured', 'sort_order', 'is_sample', 'created_at', 'updated_at'],
        'products' => ['id', 'slug', 'sku', 'name', 'brand', 'model', 'category_id', 'summary', 'description', 'specs', 'pricing_mode', 'price_php', 'price_basis', 'price_is_sample', 'availability', 'warranty_terms', 'admin_notes', 'status', 'featured', 'sort_order', 'is_sample', 'created_at', 'updated_at'],
        'projects' => ['id', 'slug', 'title', 'location', 'project_type', 'system_size_kwp', 'system_size_confirmed', 'scope', 'summary', 'description', 'admin_notes', 'status', 'featured', 'sort_order', 'is_sample', 'created_at', 'updated_at'],
        'media_assets' => ['id', 'kind', 'storage_key', 'package_id', 'product_id', 'project_id', 'content_block_key', 'alt', 'caption', 'file_name', 'widths', 'width', 'height', 'bytes', 'is_cover', 'sort_order', 'created_at', 'updated_at'],
        // Added on the original site in Oct 2026; older Supabase projects may not have them.
        'page_texts' => ['key', 'value', 'updated_at'],
        'inquiries' => ['name', 'phone', 'email', 'inquiry_type', 'location', 'bill_range', 'item_label', 'message', 'status', 'staff_notes', 'created_at', 'updated_at'],
    ];

    /** Tables that may be missing on the original project: read as empty instead of failing. */
    private const OPTIONAL = ['page_texts', 'inquiries'];

    private const JSON = ['components', 'inclusions', 'exclusions', 'specs', 'widths'];

    private string $url;

    private string $key;

    public function handle(): int
    {
        $this->url = rtrim((string) env('SUPABASE_URL'), '/');
        $this->key = (string) env('SUPABASE_SECRET_KEY');
        if (! $this->url || ! $this->key) {
            $this->error('Set SUPABASE_URL and SUPABASE_SECRET_KEY in .env (developer machine only; never on the server).');

            return self::FAILURE;
        }
        $export = $this->option('export');
        if (! $export && ! $this->option('import')) {
            $this->error('Choose --export=<folder> or --import.');

            return self::FAILURE;
        }

        // 1. Read everything.
        $data = [];
        foreach (array_keys(self::TABLES) as $table) {
            $data[$table] = in_array($table, self::OPTIONAL, true) ? $this->readOptional($table) : $this->readAll($table);
            $this->line(sprintf('  %-20s %d rows', $table, count($data[$table])));
        }
        $staff = $this->readAll('staff_members');
        $this->line(sprintf('  %-20s %d people', 'staff', count($staff)));

        $rows = [];
        foreach (self::TABLES as $table => $cols) {
            $rows[$table] = array_map(fn ($r) => $this->convert($table, $r, $cols), $data[$table]);
        }
        $rows['users'] = array_map(fn ($s) => [
            'name' => $s['display_name'] ?: Str::before($s['email'], '@'),
            'email' => mb_strtolower($s['email']),
            'password' => bcrypt(Str::random(40)), // unusable until an owner resets it
            'role' => $s['role'] === 'owner' ? 'owner' : 'editor',
            'active' => $s['active'] ? 1 : 0,
            'must_change_password' => 1,
            'created_at' => $this->ts($s['created_at'] ?? null),
            'updated_at' => $this->ts($s['updated_at'] ?? null),
        ], $staff);

        // 2. Every referenced file.
        $files = [];
        foreach ($data['media_assets'] as $m) {
            foreach (in_array($m['kind'], ['datasheet', 'manual'], true) ? [$m['kind'].'.pdf'] : array_map(fn ($w) => "{$w}.webp", $m['widths']) as $f) {
                $files[] = $m['storage_key'].'/'.$f;
            }
        }
        $this->line(sprintf('  %-20s %d files', 'media files', count($files)));

        return $export ? $this->export($export, $rows, $files) : $this->import($rows, $files);
    }

    private function http()
    {
        return Http::withHeaders(['apikey' => $this->key, 'Authorization' => 'Bearer '.$this->key])->timeout(60)->retry(2, 500);
    }

    private function readAll(string $table): array
    {
        $out = [];
        for ($offset = 0; ; $offset += count($page)) {
            $page = $this->http()->withHeaders(['Range-Unit' => 'items', 'Range' => $offset.'-'.($offset + 999)])
                ->get("{$this->url}/rest/v1/{$table}", ['select' => '*'])->throw()->json();
            array_push($out, ...$page);
            if (count($page) < 1000) {
                return $out;
            }
        }
    }

    private function readOptional(string $table): array
    {
        try {
            return $this->readAll($table);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if (in_array($e->response->status(), [404, 400], true)) {
                return []; // table not created on the original project
            }
            throw $e;
        }
    }

    private function ts(?string $iso): ?string
    {
        return $iso ? \Carbon\Carbon::parse($iso)->utc()->format('Y-m-d H:i:s') : null;
    }

    private function convert(string $table, array $r, array $cols): array
    {
        $out = [];
        foreach ($cols as $c) {
            $v = $r[$c] ?? null;
            if (in_array($c, self::JSON, true)) {
                $v = json_encode($v ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (in_array($c, ['created_at', 'updated_at'], true)) {
                $v = $this->ts($v);
            } elseif (is_bool($v)) {
                $v = $v ? 1 : 0;
            }
            $out[$c] = $v;
        }
        if ($table === 'inquiries') {
            // Same limit as the inquiries table here (the original allows 300 characters).
            $out['item_label'] = $out['item_label'] === null ? null : mb_substr($out['item_label'], 0, 200);
        }
        if ($table !== 'page_texts') { // page_texts has no created_at column
            $out['created_at'] ??= $out['updated_at'] ?? now()->format('Y-m-d H:i:s');
        }
        if (in_array($table, ['site_settings', 'content_blocks', 'product_categories', 'packages', 'products', 'projects'], true)) {
            $out['lock_version'] = 0;
        }

        return $out;
    }

    private function download(string $path): string
    {
        return $this->http()->get("{$this->url}/storage/v1/object/media/{$path}")->throw()->body();
    }

    private function sqlValue(mixed $v): string
    {
        if ($v === null) {
            return 'NULL';
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }

        $s = (string) $v;

        // Hex literals need no escaping, so the import is safe under any SQL mode
        // (including NO_BACKSLASH_ESCAPES) and for any text.
        return $s === '' ? "''" : "_utf8mb4 X'".bin2hex($s)."'";
    }

    private function export(string $folder, array $rows, array $files): int
    {
        $dir = base_path($folder);
        @mkdir($dir, 0775, true);

        $sql = "-- JFL Solar content copied from the original site on ".now()->toDateTimeString()." UTC\n";
        $sql .= "-- Import in phpMyAdmin AFTER database-schema.sql, into an empty database.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\nSTART TRANSACTION;\n\n";
        foreach (array_merge(array_keys(self::TABLES), ['users']) as $table) {
            foreach (array_chunk($rows[$table], 50) as $chunk) {
                $cols = array_keys($chunk[0]);
                $values = array_map(fn ($r) => '('.implode(', ', array_map(fn ($c) => $this->sqlValue($r[$c]), $cols)).')', $chunk);
                $sql .= 'INSERT INTO `'.$table.'` (`'.implode('`, `', $cols)."`) VALUES\n".implode(",\n", $values).";\n";
            }
        }
        $sql .= "\nCOMMIT;\nSET FOREIGN_KEY_CHECKS = 1;\n";
        file_put_contents("{$dir}/data.sql", $sql);

        $zip = new \ZipArchive;
        $zip->open("{$dir}/media.zip", \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $i => $path) {
            $zip->addFromString("media/{$path}", $this->download($path));
            if (($i + 1) % 20 === 0) {
                $this->line('  files: '.($i + 1).'/'.count($files));
            }
        }
        $zip->close();
        $this->info("Wrote {$dir}/data.sql and {$dir}/media.zip (".count($files).' files).');

        return self::SUCCESS;
    }

    private function import(array $rows, array $files): int
    {
        $hasContent = DB::table('site_settings')->exists() || DB::table('packages')->exists() || DB::table('users')->exists();
        if ($hasContent && ! $this->option('force')) {
            $this->error('This database already has content or staff. Use an empty database (migrate:fresh without --seed), or --force.');

            return self::FAILURE;
        }
        foreach ($files as $path) {
            $target = storage_path("app/media/{$path}");
            @mkdir(dirname($target), 0775, true);
            file_put_contents($target, $this->download($path));
        }
        DB::transaction(function () use ($rows) {
            foreach (array_merge(array_keys(self::TABLES), ['users']) as $table) {
                foreach (array_chunk($rows[$table], 100) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        });
        \App\Support\PublicContent::flush();
        foreach (array_merge(array_keys(self::TABLES), ['users']) as $table) {
            $count = DB::table($table)->count();
            $this->line(sprintf('  %-20s %s', $table, $count === count($rows[$table]) ? "{$count} ✓" : "{$count} ≠ ".count($rows[$table])));
        }
        $this->info('Imported. Owners: use /admin/recover to set a password, then reset the others from the Staff page.');

        return self::SUCCESS;
    }
}
