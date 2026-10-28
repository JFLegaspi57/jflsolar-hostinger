@php
    use App\Support\Compare;
    use App\Support\Ui;
    $askHref = fn ($s) => '/contact?package='.rawurlencode($s->slug).'#quote';
@endphp
<x-site-layout title="Compare packages" path="/packages/compare" description="Compare JFL Solar packages side by side." noindex>
    <x-page-header eyebrow="Packages" title="Compare packages" />
    @if ($shown->count() < 2)
        <x-container class="py-12">
            <p class="text-lg text-steel-800">Select at least two packages to compare.</p>
            @if ($missing > 0)<p class="mt-2 text-steel-600">Some selected packages are no longer available.</p>@endif
            <a href="/packages" class="{{ Ui::button('primary', 'md', 'mt-6') }}">Choose packages</a>
        </x-container>
    @else
        <x-container class="py-10">
            @if ($truncated || $missing > 0)
                <p role="status" class="mb-6 rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-warn-800">
                    @if ($truncated)Only the first {{ Compare::MAX }} packages are compared. @endif
                    @if ($missing > 0)Some selected packages are no longer available.@endif
                </p>
            @endif

            {{-- ≥768px: one semantic table --}}
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full table-fixed border-collapse text-left text-[0.95rem]">
                    <caption class="sr-only">Comparison of {{ $shown->pluck('name')->implode(', ') }}</caption>
                    <colgroup><col class="w-48">@foreach ($shown as $s)<col>@endforeach</colgroup>
                    <thead>
                        <tr class="border-b-2 border-steel-900 align-bottom">
                            <td></td>
                            @foreach ($shown as $s)
                                <th scope="col" class="px-4 pb-4">
                                    <a href="/packages/{{ $s->slug }}" class="text-lg font-bold text-steel-950 hover:underline">{{ $s->name }}</a>
                                    @if ($s->is_sample)<span class="mt-1 block text-xs font-semibold text-steel-600">Sample package</span>@endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-steel-200">
                        @foreach (Compare::rows() as $row)
                            <tr class="align-top">
                                <th scope="row" class="bg-steel-50 px-4 py-3 text-sm font-semibold text-steel-800">{{ $row['label'] }}</th>
                                @foreach ($shown as $s)
                                    <td class="px-4 py-3 text-steel-800">{{ $row['render']($s) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            @foreach ($shown as $s)
                                <td class="px-4 py-5"><a href="{{ $askHref($s) }}" class="{{ Ui::button('primary', 'sm') }}">Ask about this package<span class="sr-only">: {{ $s->name }}</span></a></td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- <768px: attribute-first groups, each value labelled with its package; no horizontal scrolling --}}
            <div class="space-y-5 md:hidden">
                <ul class="space-y-2">
                    @foreach ($shown as $s)
                        <li class="flex items-center justify-between gap-3 rounded-[var(--radius-control)] border border-steel-200 px-3 py-2">
                            <span class="min-w-0">
                                <a href="/packages/{{ $s->slug }}" class="block font-semibold text-steel-950 underline">{{ $s->name }}</a>
                                @if ($s->is_sample)<span class="text-xs font-semibold text-steel-600">Sample package</span>@endif
                            </span>
                            <a href="{{ $askHref($s) }}" class="inline-flex min-h-11 shrink-0 items-center rounded-[var(--radius-control)] bg-gold-500 px-3 text-sm font-semibold text-steel-950 hover:bg-gold-400">Ask<span class="sr-only"> about {{ $s->name }}</span></a>
                        </li>
                    @endforeach
                </ul>
                @foreach (Compare::mobileRows() as $row)
                    <section aria-label="{{ $row['label'] }}" class="rounded-[var(--radius-panel)] border border-steel-200">
                        <h2 class="border-b border-steel-200 bg-steel-50 px-4 py-2 text-sm font-bold text-steel-900">{{ $row['label'] }}</h2>
                        <dl class="divide-y divide-steel-200">
                            @foreach ($shown as $s)
                                <div class="px-4 py-2.5">
                                    <dt class="truncate text-xs font-semibold tracking-wide text-steel-600 uppercase">{{ Compare::shortName($s->name) }}</dt>
                                    <dd class="mt-0.5 min-w-0 text-steel-800">{{ $row['render']($s) }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                @endforeach
            </div>
        </x-container>
    @endif
</x-site-layout>
