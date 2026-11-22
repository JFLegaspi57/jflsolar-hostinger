@php
    $fmt = fn ($n) => number_format((int) $n);
    $dur = fn ($s) => \App\Support\Visitors::duration($s);
    $top = max(1, ...($rows->pluck('views')->all() ?: [1]));
    $total = $rows->sum('views');
    $sinceLabel = $since ? \Carbon\CarbonImmutable::parse($since)->format('j F Y') : null;
@endphp
<x-admin.layout title="Blog views">
    <x-admin.page-title title="Blog views" intro="How many times each blog post was opened, most read first. English and Filipino pages are counted together. Your own visits while signed in to Admin, robots, and browsers that ask not to be tracked are not counted.">
        <x-slot:action>
            <a href="/admin/blog" class="{{ \App\Support\Ui::button('secondary') }}">Back to Blog</a>
        </x-slot:action>
    </x-admin.page-title>

    <dl class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-3" aria-label="Blog totals">
        <div class="rounded-[var(--radius-panel)] border border-gold-500 bg-gold-50 p-4"><dt class="text-sm font-semibold text-steel-700">Total blog views</dt><dd class="mt-1 text-3xl font-extrabold text-steel-950 tabular">{{ $fmt($total) }}</dd></div>
        <div class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4"><dt class="text-sm font-semibold text-steel-700">Last 30 days</dt><dd class="mt-1 text-3xl font-extrabold text-steel-950 tabular">{{ $fmt($rows->sum('last30')) }}</dd></div>
        <div class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4"><dt class="text-sm font-semibold text-steel-700">Posts</dt><dd class="mt-1 text-3xl font-extrabold text-steel-950 tabular">{{ $fmt($rows->count()) }}</dd></div>
    </dl>

    <div class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4 sm:p-6">
        <p class="text-sm text-steel-600">@if ($sinceLabel) Counted since {{ $sinceLabel }}, when the website started counting views per page. @else No views counted yet. @endif “Readers” = different people (browsers). “Avg. time” = how long the post was on screen.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-steel-600">
                    <tr>
                        <th scope="col" class="py-2 pr-4 font-semibold">Post</th>
                        <th scope="col" class="py-2 pr-4 text-right font-semibold">Views</th>
                        <th scope="col" class="py-2 pr-4 text-right font-semibold">Readers</th>
                        <th scope="col" class="py-2 pr-4 text-right font-semibold whitespace-nowrap">Last 30 days</th>
                        <th scope="col" class="py-2 pr-4 text-right font-semibold">Filipino</th>
                        <th scope="col" class="py-2 pr-4 text-right font-semibold whitespace-nowrap">Avg. time</th>
                        <th scope="col" class="py-2" style="width: 20%"><span class="sr-only">Chart</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-steel-200">
                    @forelse ($rows as $r)
                        @php $p = $r['post']; @endphp
                        <tr>
                            <th scope="row" class="py-2 pr-4 text-left font-normal">
                                <a href="/admin/blog/{{ $p->id }}" class="font-semibold text-steel-900 hover:underline">{{ $p->title }}</a>
                                <span class="block text-xs text-steel-500">{{ $p->published_at ? 'Published '.\App\Support\Blog::date($p) : 'Not yet published' }} · <a href="/blog/{{ $p->slug }}" target="_blank" rel="noopener" class="underline">open</a></span>
                            </th>
                            <td class="py-2 pr-4 text-right font-semibold text-steel-950 tabular">{{ $fmt($r['views']) }}</td>
                            <td class="py-2 pr-4 text-right text-steel-700 tabular">{{ $fmt($r['readers']) }}</td>
                            <td class="py-2 pr-4 text-right text-steel-700 tabular">{{ $fmt($r['last30']) }}</td>
                            <td class="py-2 pr-4 text-right text-steel-700 tabular">{{ $fmt($r['tl']) }}</td>
                            <td class="py-2 pr-4 text-right text-steel-700 tabular whitespace-nowrap">{{ $dur($r['avg']) }}</td>
                            <td class="py-2" aria-hidden="true"><div class="overflow-hidden rounded bg-steel-100" style="height: 8px"><div class="bg-steel-900" style="height: 100%; width: {{ round($r['views'] / $top * 100, 1) }}%"></div></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-3 text-steel-600">No posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin.layout>
