@php
    use App\Support\Pricing;
    use App\Support\Quotations;
    use App\Support\Ui;
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6 sm:p-8';
@endphp
<x-site-layout :title="Quotations::NAME" path="/quotation" :noindex="(bool) $member" :reviews-above-footer="false"
    description="Make a Quotation: registered JFL Solar members list the solar PV products they need, add their own items, and send the list to JFL Solar for a quotation.">
    <x-page-header eyebrow="For members" :title="Quotations::NAME"
        intro="Build your own list of solar PV equipment: pick products from our catalogue with today's website prices, add anything else you need, then send it to JFL Solar for a formal quotation." />

    <x-container class="py-10 sm:py-14">
        @include('pages.quotation.flash')

        @if (! $member)
            <section class="{{ $card }} mq-intro" aria-labelledby="mq-how">
                <h2 id="mq-how" class="text-xl font-bold text-steel-950">How it works</h2>
                <ol class="mq-steps">
                    <li><strong>Pick products</strong> from our catalogue. Current website prices are filled in for you.</li>
                    <li><strong>Add your own items</strong>, like labour, mounting or a brand you prefer. We price those for you.</li>
                    <li><strong>Adjust, save and print</strong> as often as you like. Keep up to {{ Quotations::MAX_QUOTES }} quotations for different projects.</li>
                    <li><strong>Send it to JFL Solar</strong> and our team replies with a formal quotation.</li>
                </ol>
                <p class="mt-6 text-steel-800">{{ Quotations::NAME }} is free for registered members.</p>
                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <a href="/signup?next=%2Fquotation" class="{{ Ui::button('primary', 'lg') }}">Sign up free</a>
                    <span class="text-steel-700">Already a member? <a href="/login?next=%2Fquotation" class="font-semibold text-steel-900 underline">Sign in</a></span>
                </div>
            </section>
        @else
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 class="text-2xl font-bold text-steel-950">Your quotations</h2>
                @if ($quotes->count() < Quotations::MAX_QUOTES)
                    <form method="post" action="/quotation">@csrf<button type="submit" class="{{ Ui::button('primary', 'lg') }}"><span aria-hidden="true">＋</span> New quotation</button></form>
                @endif
            </div>
            @if ($quotes->isEmpty())
                <div class="{{ $card }} mt-6">
                    <p class="text-lg text-steel-800">You have no quotations yet. Start one with <strong>New quotation</strong>, then add products from our catalogue and items of your own.</p>
                </div>
            @else
                <ul class="mq-list mt-6">
                    @foreach ($quotes as $q)
                        @php $sum = Quotations::lines($q); $n = count($sum['lines']); @endphp
                        <li id="q-{{ $q->id }}" class="{{ $card }} mq-item">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-steel-600">{{ $q->reference() }} · {{ $n }} {{ $n === 1 ? 'line' : 'lines' }}</p>
                                <h3 class="mt-1 text-lg font-bold text-steel-950"><a href="/quotation/{{ $q->id }}" class="underline-offset-2 hover:underline">{{ $q->title }}</a></h3>
                                <p class="mt-1 text-sm text-steel-700">
                                    @if ($q->sent_at)
                                        <span class="mq-badge mq-sent">Sent {{ $q->sent_at->timezone('Asia/Manila')->format('j M Y') }}</span>
                                        @if ($q->edited_after_send)<span class="mq-badge mq-changed">Changed since sending</span>@endif
                                    @else
                                        <span class="mq-badge">Draft</span>
                                    @endif
                                    Last changed {{ $q->updated_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}
                                </p>
                                <div class="mq-tools mt-3">
                                <a href="/quotation/{{ $q->id }}" class="mq-rename-btn mq-edit-btn"><span aria-hidden="true">✎</span> Edit<span class="sr-only"> “{{ $q->title }}”</span></a>
                                <details class="mq-rename" name="mq-tools-{{ $q->id }}">
                                    <summary class="mq-rename-btn"><span aria-hidden="true" class="mq-aa">Aa</span> Rename</summary>
                                    <form method="post" action="/quotation/{{ $q->id }}/rename" class="mq-rename-box">@csrf
                                        <label for="rename-{{ $q->id }}" class="block text-sm font-semibold text-steel-900">Quotation name</label>
                                        <div class="mq-rename-row">
                                            <input id="rename-{{ $q->id }}" name="title" value="{{ $q->title }}" maxlength="{{ Quotations::LIMITS['title'] }}" required
                                                class="min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base text-steel-950 focus:border-steel-900">
                                            <button type="submit" class="{{ Ui::button('dark') }}">Save name</button>
                                        </div>
                                    </form>
                                </details>
                                <details class="mq-rename mq-del" name="mq-tools-{{ $q->id }}">
                                    <summary class="mq-rename-btn mq-del-btn"><span aria-hidden="true">🗑</span> Delete</summary>
                                    <form method="post" action="/quotation/{{ $q->id }}/delete" class="mq-rename-box mq-del-box">@csrf
                                        <p class="text-sm text-steel-900">Delete “{{ $q->title }}”? This cannot be undone.@if ($q->sent_at) A request you already sent stays with our team.@endif</p>
                                        <button type="submit" class="{{ Ui::button('danger', 'sm') }} mt-2">Yes, delete it</button>
                                    </form>
                                </details>
                                </div>
                            </div>
                            <div class="mq-item-side">
                                <p class="text-sm text-steel-600">Estimated</p>
                                <p class="text-xl font-bold text-steel-950 tabular">{{ Pricing::formatPhp($sum['total']) }}</p>
                                @if ($sum['unpriced'])<p class="text-xs text-steel-600">+ {{ $sum['unpriced'] }} to be quoted</p>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($quotes->count() >= Quotations::MAX_QUOTES)
                    <p class="mt-4 text-steel-700">You have {{ Quotations::MAX_QUOTES }} quotations, the most you can keep. Delete one you no longer need to start a new one.</p>
                @endif
            @endif
        @endif
    </x-container>
    <x-slot:head>
        <style>
            .mq-steps { margin-top: 1rem; display: grid; gap: .75rem; counter-reset: mq; }
            .mq-steps li { position: relative; padding-left: 2.75rem; color: var(--color-steel-800); line-height: 1.55; counter-increment: mq; }
            .mq-steps li::before { content: counter(mq); position: absolute; left: 0; top: -.1rem; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 999px; background: var(--color-gold-500); font-weight: 800; color: var(--color-steel-950); }
            .mq-list { display: grid; gap: 1rem; }
            .mq-item { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 1rem; }
            .mq-item-side { text-align: right; }
            @media (max-width: 39.99rem) { .mq-item-side { text-align: left; } }
            .mq-tools { position: relative; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
            .mq-tools .mq-rename { position: static; }
            .mq-tools .mq-rename-box { position: absolute; left: 0; top: 100%; z-index: 30; margin-top: .5rem; box-shadow: 0 12px 28px -10px rgb(15 20 28 / .35); }
            .mq-edit-btn { text-decoration: none; }
            .mq-edit-btn:hover { border-color: var(--color-steel-900); }
            .mq-del-btn:hover, .mq-del[open] .mq-del-btn { border-color: var(--color-danger-700) !important; color: var(--color-danger-700); }
            .mq-del-box { border-color: #f3c3be !important; background: #fff7f6 !important; }
            .mq-aa { font-family: Georgia, serif; font-size: .85em; font-weight: 700; }
            .mq-rename summary { list-style: none; }
            .mq-rename summary::-webkit-details-marker { display: none; }
            .mq-rename-btn { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.75rem; cursor: pointer; border: 1px solid var(--color-steel-300); border-radius: var(--radius-control); background: #fff; padding: 0 .9rem; font-size: .9rem; font-weight: 700; color: var(--color-steel-900); }
            .mq-rename-btn:hover, .mq-rename[open] .mq-rename-btn { border-color: var(--color-steel-900); }
            .mq-rename-box { margin-top: .5rem; width: min(30rem, calc(100vw - 5rem)); border: 1px solid var(--color-steel-200); border-radius: var(--radius-control); background: #fff; padding: .9rem; }
            .mq-rename-row { margin-top: .35rem; display: flex; flex-wrap: wrap; gap: .5rem; }
            .mq-rename-row input { flex: 1 1 12rem; }
            .mq-badge { display: inline-block; margin-right: .4rem; border: 1px solid var(--color-steel-300); border-radius: 999px; padding: 0 .55rem; font-size: .75rem; font-weight: 700; color: var(--color-steel-800); background: var(--color-steel-50); }
            .mq-sent { border-color: #b7e2c6; background: var(--color-ok-50); color: var(--color-ok-700); }
            .mq-changed { border-color: #f0d39a; background: var(--color-warn-50); color: var(--color-warn-800); }
        </style>
    </x-slot:head>
</x-site-layout>
