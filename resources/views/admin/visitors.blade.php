@php
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5';
    $max = max(1, ...array_column($daily, 'unique'));
    $fmt = fn ($n) => number_format($n);
@endphp
<x-admin.layout title="Visitors">
    <x-admin.page-title title="Visitors"
        intro="How many people visit the public website. Each browser is counted once per day. Robots, admin pages and signed-in staff are not counted. Times are Philippine time; weeks run Monday to Sunday." />

    <section aria-label="Visitor totals" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($periods as $p)
            <div class="{{ $card }}">
                <h2 class="text-sm font-semibold tracking-wide text-steel-600 uppercase">{{ $p['label'] }}</h2>
                <p class="text-xs text-steel-500">{{ $p['range'] }}</p>
                <p class="mt-3 text-4xl font-extrabold text-steel-950 tabular">{{ $fmt($p['unique']) }}</p>
                <p class="text-sm text-steel-600">unique visitors</p>
                <dl class="mt-4 grid grid-cols-2 gap-2 border-t border-steel-200 pt-3 text-sm">
                    <div><dt class="text-steel-600">New</dt><dd class="text-lg font-bold text-steel-950 tabular">{{ $fmt($p['new']) }}</dd></div>
                    <div><dt class="text-steel-600">Returning</dt><dd class="text-lg font-bold text-gold-800 tabular">{{ $fmt($p['returning']) }}</dd></div>
                </dl>
            </div>
        @endforeach
    </section>

    <p class="mt-4 text-sm text-steel-600"><strong class="text-steel-900">New</strong> = first visit ever falls in that period. <strong class="text-steel-900">Returning</strong> = visited in that period and had already visited before it. All-time visitors so far: <strong class="text-steel-900 tabular">{{ $fmt($allTime) }}</strong>.</p>

    <section class="{{ $card }} mt-8" aria-labelledby="places-title">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 id="places-title" class="text-lg font-bold text-steel-950">Where visitors come from</h2>
            <nav aria-label="Period" class="flex flex-wrap gap-2">
                @foreach ($periods as $p)
                    <a href="/admin/visitors?period={{ $p['key'] }}#places-title" @if ($p['key'] === $placesPeriod['key']) aria-current="page" @endif
                        class="inline-flex min-h-9 items-center rounded-full border px-3 text-sm font-semibold {{ $p['key'] === $placesPeriod['key'] ? 'border-steel-900 bg-steel-900 text-white' : 'border-steel-300 text-steel-800 hover:border-steel-900' }}">{{ $p['label'] }}</a>
                @endforeach
            </nav>
        </div>
        <p class="mt-1 text-sm text-steel-600">{{ $placesPeriod['range'] }} · unique visitors · approximate, from each visitor's internet address</p>
        <div class="mt-5 grid gap-8 md:grid-cols-2">
            @foreach ([['Countries', $places['countries'], fn ($r) => $r['name']], ['Cities', $places['cities'], fn ($r) => $r['city'].($r['country'] && $r['city'] !== 'Unknown' ? ', '.$r['country'] : ($r['country'] ? ' ('.$r['country'].')' : ''))]] as [$title, $rows, $label])
                @php $top = max(1, ...array_column($rows, 'visitors') ?: [1]); @endphp
                <div>
                    <h3 class="text-sm font-semibold tracking-wide text-steel-600 uppercase">{{ $title }}</h3>
                    @if ($rows)
                        <ul class="mt-3 space-y-2">
                            @foreach ($rows as $r)
                                <li class="text-sm">
                                    <div class="flex justify-between gap-3"><span class="text-steel-900">{{ $label($r) }}</span><span class="font-semibold text-steel-950 tabular">{{ $fmt($r['visitors']) }}</span></div>
                                    <div class="mt-1 overflow-hidden rounded bg-steel-100" style="height: 6px"><div class="bg-gold-500" style="height: 100%; width: {{ round($r['visitors'] / $top * 100, 1) }}%"></div></div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-steel-600">No visits in this period yet.</p>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="mt-5 text-xs text-steel-500">
            @if ($geo['ready'])
                Location data: {{ $geo['edition'] ?? '' }} edition, updated monthly.
            @else
                Location data is being downloaded; places appear for visits counted after it is ready. Earlier visits show as “Unknown”.
            @endif
            <a href="{{ \App\Support\GeoIp::ATTRIBUTION_URL }}" target="_blank" rel="noopener" class="underline">IP Geolocation by DB-IP</a> (CC BY 4.0).
        </p>
    </section>

    <section class="{{ $card }} mt-8" aria-labelledby="daily-title">
        <h2 id="daily-title" class="text-lg font-bold text-steel-950">Last 30 days</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-steel-600">
                    <tr><th scope="col" class="py-2 pr-4 font-semibold">Day</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Unique</th><th scope="col" class="py-2 pr-4 text-right font-semibold">New</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Returning</th><th scope="col" class="w-1/2 py-2"><span class="sr-only">Chart</span></th></tr>
                </thead>
                <tbody class="divide-y divide-steel-200">
                    @foreach ($daily as $d)
                        <tr>
                            <th scope="row" class="py-2 pr-4 text-left font-normal whitespace-nowrap text-steel-800">{{ $d['day']->format('D, M j') }}</th>
                            <td class="py-2 pr-4 text-right font-semibold text-steel-950 tabular">{{ $fmt($d['unique']) }}</td>
                            <td class="py-2 pr-4 text-right text-steel-700 tabular">{{ $fmt($d['new']) }}</td>
                            <td class="py-2 pr-4 text-right text-steel-700 tabular">{{ $fmt($d['returning']) }}</td>
                            <td class="py-2" aria-hidden="true">
                                <div class="flex overflow-hidden rounded bg-steel-100" style="height: 12px; width: {{ round($d['unique'] / $max * 100, 1) }}%; min-width: {{ $d['unique'] ? '4px' : '0' }}">
                                    <span class="bg-steel-900" style="height: 100%; width: {{ $d['unique'] ? round($d['new'] / $d['unique'] * 100, 1) : 0 }}%"></span>
                                    <span class="bg-gold-500" style="height: 100%; flex: 1"></span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 flex flex-wrap gap-4 text-xs text-steel-600">
            <span class="inline-flex items-center gap-1.5"><span class="inline-block rounded bg-steel-900" style="width: 12px; height: 12px"></span> New</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block rounded bg-gold-500" style="width: 12px; height: 12px"></span> Returning</span>
        </p>
    </section>
</x-admin.layout>
