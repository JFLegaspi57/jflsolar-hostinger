@php
    use App\Support\Pricing;
    use App\Support\Quotations;
    use App\Support\Ui;
    $input = 'min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base text-steel-950 placeholder:text-steel-600 focus:border-steel-900';
    $card = 'rounded-[var(--radius-panel)] border border-steel-200 bg-white p-5 sm:p-8';
    $L = Quotations::LIMITS;
    $focus = session('quote_focus');
@endphp
<x-site-layout :title="$q->title.' · '.Quotations::NAME" :path="'/quotation/'.$q->id" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-8 sm:py-14">
        <p><a href="/quotation" class="font-semibold text-steel-800 underline-offset-2 hover:underline"><span aria-hidden="true">←</span> All quotations</a></p>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="text-sm font-semibold tracking-wide text-steel-600 uppercase">{{ Quotations::NAME }} · {{ $q->reference() }}</p>
                <div class="mq-titlebar">
                    <h1 class="mt-1 text-3xl font-extrabold text-steel-950 sm:text-4xl">{{ $q->title }}</h1>
                    {{-- Rename: the field belongs to the main form below (form="mq-form"). --}}
                    <div class="mq-tools">
                    <details class="mq-rename" name="mq-tools" @if (session('quote_focus') === 'title') open @endif>
                        <summary class="mq-rename-btn"><span aria-hidden="true" class="mq-aa">Aa</span> Rename</summary>
                        <div class="mq-rename-box">
                            <label for="q-title" class="block text-sm font-semibold text-steel-900">Quotation name</label>
                            <div class="mq-rename-row">
                                <input id="q-title" name="title" form="mq-form" value="{{ $q->title }}" maxlength="{{ $L['title'] }}" required class="{{ $input }}" placeholder="e.g. 10 kW hybrid for our house">
                                <button type="submit" form="mq-form" name="intent" value="rename" class="{{ Ui::button('dark') }}">Save name</button>
                            </div>
                        </div>
                    </details>
                    <details class="mq-rename mq-del" name="mq-tools">
                        <summary class="mq-rename-btn mq-del-btn"><span aria-hidden="true">🗑</span> Delete</summary>
                        <form method="post" action="/quotation/{{ $q->id }}/delete" class="mq-rename-box mq-del-box">@csrf
                            <p class="text-sm text-steel-900">Delete “{{ $q->title }}”? This cannot be undone.@if ($q->sent_at) A request you already sent stays with our team.@endif</p>
                            <button type="submit" class="{{ Ui::button('danger', 'sm') }} mt-2">Yes, delete it</button>
                        </form>
                    </details>
                    </div>
                </div>
                <p class="mt-2 text-sm text-steel-700">
                    @if ($q->sent_at)
                        <span class="mq-badge mq-sent">Sent {{ $q->sent_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}</span>
                        @if ($q->edited_after_send)<span class="mq-badge mq-changed">Changed since sending</span>@endif
                    @else
                        <span class="mq-badge">Draft — not sent yet</span>
                    @endif
                </p>
            </div>
        </div>
        <div class="mt-6">@include('pages.quotation.flash')</div>

        <form id="mq-form" method="post" action="/quotation/{{ $q->id }}" novalidate>
            @csrf
            {{-- Enter in a text field saves (instead of pressing the first move/remove button). --}}
            <button type="submit" name="intent" value="save" class="mq-default" tabindex="-1" aria-hidden="true">Save</button>

            <div class="mq-grid">
                <div class="mq-main">
                    <section class="{{ $card }}" aria-labelledby="mq-items-h">
                        <h2 id="mq-items-h" class="text-xl font-bold text-steel-950">Items</h2>
                        @if (! $sum['lines'])
                            <p class="mt-3 text-steel-700">No items yet. Add products from our catalogue or items of your own below. They are arranged by category automatically.</p>
                        @else
                            <table class="mq-table mt-4">
                                <thead><tr><th scope="col">Item</th><th scope="col" class="mq-num">Qty</th><th scope="col" class="mq-num">Website price</th><th scope="col" class="mq-num">Total</th><th scope="col"><span class="sr-only">Remove</span></th></tr></thead>
                                <tbody>
                                    @foreach ($sum['lines'] as $l)
                                        @php $i = $l['i']; @endphp
                                        @if ($loop->first || $sum['lines'][$loop->index - 1]['category'] !== $l['category'])
                                            <tr class="mq-cat"><th colspan="5" scope="colgroup">{{ $l['category'] }}</th></tr>
                                        @endif
                                        <tr class="mq-line" data-price="{{ $l['price'] ?? '' }}">
                                            <td class="mq-name" data-label="Item">
                                                @if ($l['kind'] === 'product')
                                                    @if ($l['slug'])<a href="/products/{{ $l['slug'] }}" class="font-semibold text-steel-950 underline-offset-2 hover:underline" target="_blank" rel="noopener">{{ $l['name'] }}</a>@else<span class="font-semibold text-steel-950">{{ $l['name'] }}</span>@endif
                                                    @if ($l['missing'])<span class="mq-badge mq-changed">No longer listed</span>@endif
                                                    @if ($l['unavailable'])<span class="mq-badge mq-changed">Currently not available</span>@endif
                                                    @if ($l['brand'] !== '' || $l['model'] !== '')
                                                        <dl class="mq-bm">
                                                            @if ($l['brand'] !== '')<div><dt>Brand</dt><dd>{{ $l['brand'] }}</dd></div>@endif
                                                            @if ($l['model'] !== '')<div><dt>Model</dt><dd>{{ $l['model'] }}</dd></div>@endif
                                                        </dl>
                                                    @endif
                                                @else
                                                    <label class="sr-only" for="it-{{ $i }}-name">Item name</label>
                                                    <input id="it-{{ $i }}-name" name="items[{{ $i }}][name]" value="{{ $l['name'] }}" maxlength="{{ $L['name'] }}" class="{{ $input }} font-semibold">
                                                    <span class="mq-own">Your own item · priced by JFL Solar</span>
                                                @endif
                                            </td>
                                            <td class="mq-num" data-label="Qty">
                                                <label class="sr-only" for="it-{{ $i }}-qty">Quantity of {{ $l['name'] }}</label>
                                                <div class="mq-qty">
                                                    <input id="it-{{ $i }}-qty" name="items[{{ $i }}][qty]" type="number" inputmode="numeric" min="1" max="{{ Quotations::MAX_QTY }}" step="1" value="{{ $l['qty'] }}" class="{{ $input }} tabular" data-qty>
                                                    @if ($l['kind'] === 'custom')
                                                        <label class="sr-only" for="it-{{ $i }}-unit">Unit</label>
                                                        <input id="it-{{ $i }}-unit" name="items[{{ $i }}][unit]" value="{{ $l['unit'] }}" maxlength="{{ $L['unit'] }}" placeholder="unit" class="{{ $input }} mq-unit">
                                                    @else
                                                        <span class="mq-unit-text">{{ $l['unit'] }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="mq-num tabular" data-label="Website price">{{ $l['price'] !== null ? Pricing::formatPhp($l['price']) : ($l['kind'] === 'custom' ? 'To be quoted' : 'To be confirmed') }}</td>
                                            <td class="mq-num tabular font-semibold" data-label="Total" data-total>{{ $l['total'] !== null ? Pricing::formatPhp($l['total']) : '—' }}</td>
                                            <td class="mq-actions">
                                                <button type="submit" name="intent" value="remove:{{ $i }}" class="mq-remove"><span aria-hidden="true">✕</span> Remove<span class="sr-only"> {{ $l['name'] }} from this quotation</span></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        <div class="mq-add mt-6">
                            <div class="mq-add-box">
                                <h3 class="font-bold text-steel-950">Add a product</h3>
                                <label for="add-product" class="mt-2 block text-sm font-semibold text-steel-800">Product <span class="font-normal text-steel-600">(available to order)</span></label>
                                <select id="add-product" name="add_product_id" class="{{ $input }}" @if ($focus === 'add-product') autofocus @endif>
                                    <option value="">Choose a product…</option>
                                    @foreach ($groups as $category => $products)
                                        <optgroup label="{{ $category }}">
                                            @foreach ($products as $p)
                                                @php $price = Quotations::refPrice($p); @endphp
                                                <option value="{{ $p->id }}">{{ $p->name }} — {{ $price !== null ? Pricing::formatPhp($price) : 'price to be confirmed' }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <div class="mq-add-row mt-2">
                                    <div><label for="add-qty" class="block text-sm font-semibold text-steel-800">Qty</label>
                                        <input id="add-qty" name="add_qty" type="number" inputmode="numeric" min="1" max="{{ Quotations::MAX_QTY }}" value="1" class="{{ $input }} tabular"></div>
                                    <button type="submit" name="intent" value="add_product" class="{{ Ui::button('dark') }}">Add product</button>
                                </div>
                            </div>
                            <div class="mq-add-box">
                                <h3 class="font-bold text-steel-950">Add your own item</h3>
                                <label for="custom-name" class="mt-2 block text-sm font-semibold text-steel-800">What do you need?</label>
                                <input id="custom-name" name="custom_name" list="mq-suggest" autocomplete="off" maxlength="{{ $L['name'] }}" placeholder="Start typing, e.g. inverter, cable, labour" class="{{ $input }}" @if ($focus === 'custom-name') autofocus @endif>
                                <datalist id="mq-suggest">
                                    @foreach ($suggestions as $p)
                                        <option value="{{ $p->name }}">{{ implode(' · ', array_filter([trim((string) $p->brand), trim((string) $p->model)])) }}</option>
                                    @endforeach
                                </datalist>
                                <p class="mt-1 text-xs text-steel-600">Suggestions come from our product list; choose one and it's added with its website price. Anything else is priced by JFL Solar.</p>
                                <div class="mq-add-row mt-2">
                                    <div><label for="custom-qty" class="block text-sm font-semibold text-steel-800">Qty</label>
                                        <input id="custom-qty" name="custom_qty" type="number" inputmode="numeric" min="1" max="{{ Quotations::MAX_QTY }}" value="1" class="{{ $input }} tabular"></div>
                                    <div><label for="custom-unit" class="block text-sm font-semibold text-steel-800">Unit</label>
                                        <input id="custom-unit" name="custom_unit" maxlength="{{ $L['unit'] }}" placeholder="set, lot, m…" class="{{ $input }}"></div>
                                    <button type="submit" name="intent" value="add_custom" class="{{ Ui::button('dark') }}">Add item</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="mq-side">
                    <section class="{{ $card }}" aria-labelledby="mq-total-h">
                        <h2 id="mq-total-h" class="text-sm font-semibold tracking-wide text-steel-600 uppercase">Estimated total</h2>
                        <p class="mt-1 text-3xl font-extrabold text-steel-950 tabular" data-grand>{{ Pricing::formatPhp($sum['total']) }}</p>
                        @if ($sum['unpriced'])<p class="mt-1 text-sm text-steel-700">+ {{ $sum['unpriced'] }} {{ $sum['unpriced'] === 1 ? 'item' : 'items' }} to be quoted by JFL Solar</p>@endif
                        <p class="mt-3 text-xs text-steel-600">Based on today's website prices, for reference only. Final prices, availability and delivery are confirmed in JFL Solar's formal quotation.</p>
                        <p class="mq-dirty mt-3" data-dirty hidden>You have unsaved changes.</p>
                        <div class="mt-4 grid gap-2">
                            <button type="submit" name="intent" value="save" class="{{ Ui::button('dark', 'lg') }}">Save changes</button>
                            <button type="submit" name="intent" value="print" class="{{ Ui::button('secondary') }}">Save and print / PDF</button>
                        </div>
                    </section>

                    <section class="{{ $card }} mt-6" aria-labelledby="mq-details-h">
                        <h2 id="mq-details-h" class="text-xl font-bold text-steel-950">Details</h2>
                        <div class="mt-4 space-y-4">
                            <div><label for="q-location" class="block font-semibold text-steel-900">City or province</label>
                                <input id="q-location" name="location" value="{{ $q->location }}" maxlength="{{ $L['location'] }}" autocomplete="address-level2" class="{{ $input }} mt-1" placeholder="Where the items are needed"></div>
                            <div><label for="q-phone" class="block font-semibold text-steel-900">Mobile or phone</label>
                                <input id="q-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" value="{{ $q->phone }}" maxlength="{{ $L['phone'] }}" class="{{ $input }} mt-1 tabular" placeholder="0917 123 4567"></div>
                            <div><label for="q-notes" class="block font-semibold text-steel-900">Notes for JFL Solar <span class="font-normal text-steel-600">(optional)</span></label>
                                <textarea id="q-notes" name="notes" rows="5" maxlength="{{ $L['notes'] }}" class="{{ $input }} mt-1 py-2" placeholder="Anything we should know: your project, timeline, delivery or installation needs.">{{ $q->notes }}</textarea></div>
                        </div>
                    </section>

                    <section id="send" class="{{ $card }} mt-6 mq-send" aria-labelledby="mq-send-h">
                        <h2 id="mq-send-h" class="text-xl font-bold text-steel-950">Send to JFL Solar</h2>
                        <p class="mt-2 text-steel-800">We'll reply to <strong>{{ $member->email }}</strong> or call you with a formal quotation.</p>
                        @if ($q->sent_at)<p class="mt-2 text-sm text-steel-700">Last sent {{ $q->sent_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}.{{ $q->edited_after_send ? ' You have changed it since; send it again so we see the latest version.' : '' }}</p>@endif
                        <button type="submit" name="intent" value="send" class="{{ Ui::button('primary', 'lg') }} mt-4 w-full">{{ $q->sent_at ? 'Send again' : 'Send quotation request' }}</button>
                    </section>
                </aside>
            </div>
        </form>

        <div class="mq-more mt-8">
            <form method="post" action="/quotation/{{ $q->id }}/duplicate">@csrf<button type="submit" class="{{ Ui::button('secondary', 'sm') }}">Make a copy</button></form>
        </div>
    </x-container>

    @include('pages.quotation.confirm-delete')
    <x-slot:head>
        <style>
            .mq-titlebar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; }
            .mq-rename { position: relative; }
            .mq-tools { position: relative; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
            .mq-tools .mq-rename { position: static; }
            .mq-tools .mq-rename-box { position: absolute; left: 0; top: 100%; z-index: 30; margin-top: .5rem; box-shadow: 0 12px 28px -10px rgb(15 20 28 / .35); }
            .mq-del-btn:hover, .mq-del[open] .mq-del-btn { border-color: var(--color-danger-700) !important; color: var(--color-danger-700); }
            .mq-del-box { border-color: #f3c3be !important; background: #fff7f6 !important; }
            .mq-aa { font-family: Georgia, serif; font-size: .85em; font-weight: 700; }
            .mq-rename summary { list-style: none; }
            .mq-rename summary::-webkit-details-marker { display: none; }
            .mq-rename-btn { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.75rem; cursor: pointer; border: 1px solid var(--color-steel-300); border-radius: var(--radius-control); background: #fff; padding: 0 .9rem; font-size: .9rem; font-weight: 700; color: var(--color-steel-900); }
            .mq-rename-btn:hover, .mq-rename[open] .mq-rename-btn { border-color: var(--color-steel-900); }
            .mq-rename-box { margin-top: .5rem; width: min(34rem, calc(100vw - 2rem)); border: 1px solid var(--color-steel-200); border-radius: var(--radius-control); background: #fff; padding: .9rem; }
            .mq-rename-row { margin-top: .35rem; display: flex; flex-wrap: wrap; gap: .5rem; }
            .mq-rename-row input { flex: 1 1 14rem; }
            .mq-default { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
            .mq-grid { margin-top: 1.5rem; display: grid; gap: 1.5rem; align-items: start; }
            @media (min-width: 64rem) { .mq-grid { grid-template-columns: minmax(0, 1fr) 22rem; } .mq-side { position: sticky; top: 6rem; } }
            .mq-badge { display: inline-block; margin-right: .4rem; border: 1px solid var(--color-steel-300); border-radius: 999px; padding: 0 .55rem; font-size: .75rem; font-weight: 700; color: var(--color-steel-800); background: var(--color-steel-50); }
            .mq-sent { border-color: #b7e2c6; background: var(--color-ok-50); color: var(--color-ok-700); }
            .mq-changed { border-color: #f0d39a; background: var(--color-warn-50); color: var(--color-warn-800); }
            .mq-table { width: 100%; border-collapse: collapse; }
            .mq-table th { border-bottom: 2px solid var(--color-steel-800); padding: .5rem; text-align: left; font-size: .8rem; color: var(--color-steel-700); }
            .mq-table td { border-bottom: 1px solid var(--color-steel-200); padding: .75rem .5rem; vertical-align: top; }
            .mq-table .mq-num { text-align: right; white-space: nowrap; }
            .mq-name { min-width: 14rem; }
            .mq-bm { margin-top: .3rem; display: flex; flex-wrap: wrap; gap: .1rem 1rem; font-size: .85rem; color: var(--color-steel-700); }
            .mq-bm div { display: flex; gap: .3rem; }
            .mq-bm dt { font-weight: 600; color: var(--color-steel-600); }
            .mq-bm dt::after { content: ":"; }
            .mq-bm dd { margin: 0; color: var(--color-steel-900); }
            .mq-own { display: block; margin-top: .25rem; font-size: .75rem; color: var(--color-steel-600); }
            .mq-qty { display: flex; gap: .35rem; align-items: center; justify-content: flex-end; }
            .mq-qty input[data-qty] { width: 5.5rem; text-align: right; }
            .mq-unit { width: 5.5rem; }
            .mq-unit-text { min-width: 3.2rem; text-align: left; font-size: .85rem; color: var(--color-steel-700); }
            .mq-actions { white-space: nowrap; text-align: right; }
            .mq-remove { display: inline-flex; align-items: center; gap: .35rem; min-height: 2.75rem; border: 1px solid var(--color-steel-300); border-radius: var(--radius-control); background: #fff; padding: 0 .8rem; font-size: .875rem; font-weight: 700; color: var(--color-steel-800); }
            .mq-remove:hover, .mq-remove:focus-visible { border-color: var(--color-danger-700); background: var(--color-danger-50); color: var(--color-danger-700); }
            .mq-table tr.mq-cat th { border-bottom: 1px solid var(--color-steel-300); padding: 1.25rem .5rem .4rem; font-size: .8rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; color: var(--color-steel-950); }
            .mq-table tbody tr.mq-cat:first-child th { padding-top: .6rem; }
            .mq-add { display: grid; gap: 1rem; }
            @media (min-width: 48rem) { .mq-add { grid-template-columns: 1fr 1fr; } }
            .mq-add-box { border: 1px dashed var(--color-steel-300); border-radius: var(--radius-control); background: var(--color-steel-50); padding: 1rem; }
            .mq-add-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .5rem; }
            .mq-add-row > div { width: 6rem; }
            .mq-dirty { border-radius: var(--radius-control); background: var(--color-warn-50); padding: .5rem .75rem; font-size: .875rem; font-weight: 700; color: var(--color-warn-800); }
            .mq-send { border-top: 4px solid var(--color-gold-500); }
            .mq-more { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 1rem; }
            .mq-delete summary { list-style: none; cursor: pointer; }
            .mq-delete summary::-webkit-details-marker { display: none; }
            /* Phones: each line becomes a small card. */
            @media (max-width: 47.99rem) {
                .mq-table thead { position: absolute; left: -9999px; }
                .mq-table, .mq-table tbody, .mq-table tr, .mq-table td { display: block; width: 100%; }
                .mq-table tr.mq-line { border: 1px solid var(--color-steel-200); border-radius: var(--radius-control); padding: .75rem; margin-bottom: .75rem; }
                .mq-table tr.mq-cat th { display: block; padding: .75rem 0 .4rem; border: 0; }
                .mq-table td { border: 0; padding: .3rem 0; }
                .mq-table td.mq-num { display: flex; justify-content: space-between; align-items: center; text-align: right; }
                .mq-table td.mq-num::before { content: attr(data-label); font-size: .85rem; font-weight: 600; color: var(--color-steel-700); }
                .mq-actions { text-align: left; padding-top: .5rem !important; }
                .mq-name { min-width: 0; }
            }
        </style>
    </x-slot:head>
    <script>
        (() => {
            const form = document.getElementById('mq-form');
            if (!form) return;
            const php = n => '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: Number.isInteger(n) ? 0 : 2, maximumFractionDigits: 2 });
            const grand = form.querySelector('[data-grand]');
            const dirty = form.querySelector('[data-dirty]');
            const recalc = () => {
                let sum = 0;
                form.querySelectorAll('tr.mq-line[data-price]').forEach(tr => {
                    const price = parseFloat(tr.dataset.price);
                    if (Number.isNaN(price)) return;
                    const qty = Math.max(1, Math.min({{ Quotations::MAX_QTY }}, parseInt(tr.querySelector('[data-qty]').value, 10) || 1));
                    const total = Math.round(price * qty * 100) / 100;
                    tr.querySelector('[data-total]').textContent = php(total);
                    sum += total;
                });
                if (grand) grand.textContent = php(Math.round(sum * 100) / 100);
            };
            let changed = false;
            form.addEventListener('input', e => {
                if (e.target.matches('[data-qty]')) recalc();
                if (!e.target.closest('.mq-add-box')) { changed = true; if (dirty) dirty.hidden = false; }
            });
            form.addEventListener('submit', () => { changed = false; });
            window.addEventListener('beforeunload', e => { if (changed) { e.preventDefault(); e.returnValue = ''; } });
        })();
    </script>
</x-site-layout>
