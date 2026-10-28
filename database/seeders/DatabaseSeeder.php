<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Starting content for a fresh site: contact settings, About-page blocks,
 * categories and SAMPLE packages/products/projects (is_sample = true, hidden
 * on the live site). Exported from the original site's demo content
 * (database/seeders/demo-content.json). The real site is filled by the
 * migration from the original database instead.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/demo-content.json'), true, flags: JSON_THROW_ON_ERROR);
        $now = now();
        $stamp = fn (array $row) => $row + ['created_at' => $now, 'updated_at' => $now];
        $json = fn (array $row, array $cols) => array_merge($row, array_map(fn ($c) => json_encode($row[$c] ?? []), array_combine($cols, $cols)));
        $only = fn (array $row, array $drop) => array_diff_key($row, array_flip($drop));

        DB::transaction(function () use ($data, $stamp, $json, $only) {
            DB::table('site_settings')->insertOrIgnore($stamp(['id' => 1] + $only($data['settings'], ['updated_at'])));

            foreach ($data['blocks'] as $b) {
                DB::table('content_blocks')->insertOrIgnore($stamp($only($b, ['updated_at'])));
            }
            foreach ($data['categories'] as $c) {
                DB::table('product_categories')->insertOrIgnore($stamp($c));
            }
            foreach ($data['products'] as $p) {
                DB::table('products')->insertOrIgnore($stamp($json($only($p, ['updated_at', 'currency']), ['specs'])));
            }
            foreach ($data['packages'] as $p) {
                DB::table('packages')->insertOrIgnore($stamp($json($only($p, ['updated_at', 'currency']), ['components', 'inclusions', 'exclusions'])));
            }
            foreach ($data['projects'] as $p) {
                DB::table('projects')->insertOrIgnore($stamp($only($p, ['updated_at'])));
            }
        });
    }
}
