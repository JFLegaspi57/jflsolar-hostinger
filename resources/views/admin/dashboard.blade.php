<x-admin.layout title="Overview">
    <x-admin.page-title title="Overview" :intro="'Signed in as '.auth()->user()->email.' ('.auth()->user()->role.').'" />

    @if ($newInquiries)
        <p class="mb-6 rounded-[var(--radius-panel)] border border-gold-200 bg-gold-50 px-5 py-4 text-steel-900">
            <strong>{{ $newInquiries }} new {{ $newInquiries === 1 ? 'inquiry' : 'inquiries' }}.</strong>
            <a href="/admin/inquiries" class="ml-1 font-semibold underline">Open inquiries</a>
        </p>
    @endif

    <section aria-labelledby="content-title">
        <h2 id="content-title" class="sr-only">Content</h2>
        <ul class="grid gap-4 sm:grid-cols-3">
            @foreach ($stats as $s)
                <li>
                    <a href="{{ $s['href'] }}" class="block rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 hover:border-steel-900">
                        <span class="block text-lg font-bold text-steel-950">{{ $s['label'] }}</span>
                        <span class="mt-2 block text-sm text-steel-700 tabular">{{ $s['published'] }} published · {{ $s['drafts'] }} drafts</span>
                        @if ($s['samples'])<span class="mt-1 block text-sm text-steel-600">{{ $s['samples'] }} sample</span>@endif
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    <section aria-labelledby="launch-title" class="mt-10 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
        <h2 id="launch-title" class="text-xl font-bold text-steel-950">Before launch</h2>
        @if ($todo)
            <ul class="mt-3 space-y-2">
                @foreach ($todo as $t)
                    <li class="flex items-start gap-2"><span aria-hidden="true" class="mt-2 size-2 shrink-0 rounded-full bg-gold-500"></span><a href="{{ $t['href'] }}" class="font-semibold text-steel-900 underline">{{ $t['text'] }}</a></li>
                @endforeach
            </ul>
        @else
            <p class="mt-2 text-ok-700">No placeholder content remaining.</p>
        @endif
    </section>

    <section aria-labelledby="config-title" class="mt-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
        <h2 id="config-title" class="text-xl font-bold text-steel-950">Configuration</h2>
        <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-[max-content_1fr]">
            <dt class="font-semibold">Site mode</dt>
            <dd>{{ \App\Support\Site::isProduction() ? 'Live — samples and unconfirmed claims hidden; '.(\App\Support\Site::allowsIndexing() ? 'indexed by search engines' : 'search engines blocked (SEARCH_INDEXING=false)') : 'Preview — not indexed; samples visible with labels' }}</dd>
            <dt class="font-semibold">Inquiry form</dt>
            <dd>
                Inquiries are always saved in <a href="/admin/inquiries" class="underline">Inquiries</a>.
                @if ($mail === 'on') Email alerts go to {{ $notify }}.
                @elseif ($mail === 'unconfigured') Email alerts are set to {{ $notify }}, but the mail server is not configured yet.
                @else Email alerts are off.
                @endif
            </dd>
        </dl>
    </section>
</x-admin.layout>
