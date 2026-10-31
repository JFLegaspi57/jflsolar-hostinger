{{-- "Build your package": main parts with their own prices + optional add-ons, live total, Add to Basket. Used on package pages and /packages/build-your-own. --}}
@props(['lines', 'heading' => 'Build your package', 'intro' => 'Each part has its own price. Choose the options you want, change the quantities (set 0 to leave a part out), add any mounting, cables, protection devices or accessories you need, then add everything to your basket.', 'action'])
@php
    use App\Support\Pricing;
    use App\Support\Ui;
    $buildTotal = \App\Support\PackageBuilder::defaultTotal($lines);
    $addons = \App\Support\PackageBuilder::addons($lines);
    $addonTotal = collect($addons)->flatMap(fn ($g) => $g['products'])->sum(fn ($p) => (float) $p->price_php * max(0, (int) old('addon.'.$p->id, 0)));
    $maxQty = (int) config('shop.max_quantity', 500);
    $panelIdx = \App\Support\PackageBuilder::panelLine($lines);
    $mount = $panelIdx !== null ? \App\Support\PackageBuilder::mountingProducts($addons) : [];
    $panelQty = $panelIdx !== null ? (int) old('quantity.'.$panelIdx, collect($lines)->firstWhere('index', $panelIdx)['quantity'] ?? 0) : 0;
@endphp
<section id="build" aria-labelledby="build-title" style="scroll-margin-top: 7rem" class="rounded-[var(--radius-panel)] border border-steel-300 bg-white p-5 sm:p-6">
    <h2 id="build-title" class="text-xl font-bold text-steel-950">{{ $heading }}</h2>
    <p class="mt-1 text-steel-700">{{ $intro }}</p>
    @if (session('pkg_added'))
        <div role="status" class="mt-4 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-3 text-ok-700">
            <p class="flex items-center gap-2 font-semibold"><x-icon name="check" class="size-5" /> Added to your basket:</p>
            <ul class="mt-1 text-sm">@foreach ((array) session('pkg_added') as $line)<li>{{ $line }}</li>@endforeach</ul>
            <p class="mt-1 text-sm"><a href="/cart" class="font-semibold underline">View basket and check out</a></p>
        </div>
    @endif
    @if (session('pkg_error'))
        <p role="alert" class="mt-4 rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-3 font-semibold text-danger-700">{{ session('pkg_error') }}</p>
    @endif
    <form method="post" action="{{ $action }}" class="mt-4" data-pkg-builder>
        @csrf
        <div class="overflow-x-auto">
            <table class="w-full text-left text-[0.95rem]">
                <caption class="sr-only">{{ $heading }}: parts and prices</caption>
                <thead class="border-b border-steel-300 text-xs tracking-wide text-steel-600 uppercase">
                    <tr><th scope="col" class="py-2 pr-4 font-semibold">Part</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Unit price</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Qty</th><th scope="col" class="py-2 text-right font-semibold">Amount</th></tr>
                </thead>
                <tbody class="divide-y divide-steel-200">
                    @foreach ($lines as $line)
                        @php $first = $line['options'][0]; $i = $line['index']; @endphp
                        <tr data-line>
                            <td class="py-3 pr-4 align-top" style="min-width: 14rem">
                                <span class="block font-semibold text-steel-950">{{ $line['label'] }}</span>
                                @if (count($line['options']) > 1)
                                    <label for="bp-{{ $i }}" class="sr-only">Choose the {{ mb_strtolower($line['label']) }}</label>
                                    <select id="bp-{{ $i }}" name="product[{{ $i }}]" data-product class="mt-1 min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-2.5 text-[0.95rem] text-steel-950">
                                        @foreach ($line['options'] as $o)
                                            <option value="{{ $o->id }}" data-price="{{ (float) $o->price_php }}" @selected(old('product.'.$i, $first->id) === $o->id)>{{ trim(($o->brand ? $o->brand.' ' : '').$o->name) }} — {{ Pricing::formatPhp($o->price_php) }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="product[{{ $i }}]" value="{{ $first->id }}" data-product data-price="{{ (float) $first->price_php }}">
                                    <a href="/products/{{ $first->slug }}" class="text-steel-700 underline">{{ trim(($first->brand ? $first->brand.' ' : '').$first->name) }}</a>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right align-top whitespace-nowrap tabular" data-unit>{{ Pricing::formatPhp($first->price_php) }}@if (\App\Support\Shop::perMetre($first))<span class="text-sm text-steel-600"> /m</span>@endif</td>
                            <td class="py-3 pr-4 text-right align-top">
                                <label for="bq-{{ $i }}" class="sr-only">Quantity of {{ mb_strtolower($line['label']) }}</label>
                                <input id="bq-{{ $i }}" name="quantity[{{ $i }}]" type="number" inputmode="numeric" min="0" max="{{ $maxQty }}" step="1" value="{{ old('quantity.'.$i, $line['quantity']) }}" data-qty
                                    style="width: 5rem" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 bg-white px-2.5 text-right text-base text-steel-950 tabular">
                            </td>
                            <td class="py-3 text-right align-top font-semibold whitespace-nowrap text-steel-950 tabular" data-amount>{{ Pricing::formatPhp((float) $first->price_php * $line['quantity']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-steel-900" style="border-top-width: 2px">
                        <th scope="row" colspan="3" class="py-3 pr-4 text-right font-bold text-steel-950">Main parts</th>
                        <td class="py-3 text-right font-bold whitespace-nowrap text-steel-950 tabular" data-main-total>{{ Pricing::formatPhp($buildTotal) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($mount)
            @php $mountTotal = collect($mount)->sum(fn ($m) => (float) $m['product']->price_php * ($m['per'] * $panelQty + $m['fixed'])); @endphp
            <div data-mount data-panel-line="{{ $panelIdx }}" class="mt-5 rounded-[var(--radius-panel)] border border-gold-500 bg-gold-50 p-4">
                <h3 class="text-lg font-bold text-steel-950">Recommended mounting for <span data-mount-n>{{ $panelQty }}</span> <span data-mount-word>{{ $panelQty === 1 ? 'panel' : 'panels' }}</span></h3>
                <p class="mt-1 text-sm text-steel-700">A suggestion for a standard roof mount — not in your total until you add it. Your installer may adjust it for your roof.</p>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full text-left text-[0.95rem]">
                        <caption class="sr-only">Recommended mounting parts</caption>
                        <tbody class="divide-y divide-steel-200">
                            @foreach ($mount as $m)
                                <tr data-mount-row data-id="{{ $m['product']->id }}" data-per="{{ $m['per'] }}" data-fixed="{{ $m['fixed'] }}" data-price="{{ (float) $m['product']->price_php }}">
                                    <td class="py-2 pr-4"><a href="/products/{{ $m['product']->slug }}" class="font-semibold text-steel-900 hover:underline">{{ $m['product']->name }}</a><span class="block text-sm text-steel-600">{{ $m['rule'] }}</span></td>
                                    <td class="py-2 pr-4 text-right whitespace-nowrap tabular">{{ Pricing::formatPhp($m['product']->price_php) }}</td>
                                    <td class="py-2 pr-4 text-right font-semibold whitespace-nowrap tabular">× <span data-mount-qty>{{ $m['per'] * $panelQty + $m['fixed'] }}</span></td>
                                    <td class="py-2 text-right font-semibold whitespace-nowrap tabular" data-mount-amount>{{ Pricing::formatPhp((float) $m['product']->price_php * ($m['per'] * $panelQty + $m['fixed'])) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th scope="row" colspan="3" class="pr-4 text-right font-bold text-steel-950" style="padding-top: .5rem">Mounting</th><td class="text-right font-bold text-steel-950 tabular" style="padding-top: .5rem" data-mount-total>{{ Pricing::formatPhp($mountTotal) }}</td></tr></tfoot>
                    </table>
                </div>
                <button type="button" data-mount-apply hidden class="{{ Ui::button('dark', 'md', 'mt-3 w-full sm:w-auto') }}">Add recommended mounting</button>
                <p class="mt-2 text-sm text-steel-700" data-mount-done hidden role="status">Added to the Mounting hardware add-ons below — change the quantities there if you need to.</p>
            </div>
        @endif

        @if ($addons)
            <style>
                .addon-group > summary { list-style: none; }
                .addon-group > summary::-webkit-details-marker { display: none; }
                .addon-chev { width: 1.25rem; height: 1.25rem; flex: none; transition: transform .15s ease; }
                .addon-group[open] > summary .addon-chev { transform: rotate(180deg); }
                @media (prefers-reduced-motion: reduce) { .addon-chev { transition: none; } }
            </style>
            <div class="mt-6 flex flex-wrap items-end justify-between gap-2">
                <div>
                    <h3 class="text-lg font-bold text-steel-950">Add-ons <span class="text-base font-normal text-steel-600">(optional)</span></h3>
                    <p class="mt-1 text-sm text-steel-600">Tap a group to open or close it (▼ / ▲), then enter how many you need. Leave 0 for items you don’t want.</p>
                </div>
                <button type="button" data-addons-toggle hidden aria-expanded="false" class="inline-flex min-h-11 items-center gap-1.5 rounded-[var(--radius-control)] border border-steel-300 px-3 text-sm font-semibold text-steel-900 hover:border-steel-900">
                    <svg aria-hidden="true" class="addon-chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                    <span data-addons-toggle-label>Open all</span>
                </button>
            </div>
            <div class="mt-3 divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200">
                @foreach ($addons as $group)
                    <details data-addon-group class="addon-group" @if (collect($group['products'])->contains(fn ($p) => (int) old('addon.'.$p->id, 0) > 0)) open @endif>
                        <summary class="flex min-h-12 cursor-pointer items-center justify-between gap-3 px-4 font-semibold text-steel-950 hover:bg-steel-50">
                            <span>{{ $group['category']->name }} <span class="text-sm font-normal text-steel-600">({{ count($group['products']) }} items)</span></span>
                            <span class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-steel-700 tabular" data-group-total></span>
                                <svg aria-hidden="true" class="addon-chev text-steel-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
                            </span>
                        </summary>
                        <div class="overflow-x-auto border-t border-steel-200 px-4">
                            <table class="w-full text-left text-[0.95rem]">
                                <caption class="sr-only">{{ $group['category']->name }} add-ons</caption>
                                <tbody class="divide-y divide-steel-200">
                                    @foreach ($group['products'] as $o)
                                        <tr data-addon-row>
                                            <td class="py-2 pr-4" style="min-width: 12rem"><a href="/products/{{ $o->slug }}" class="text-steel-800 hover:underline">{{ $o->name }}</a>@if ($o->brand && $o->brand !== 'Generic')<span class="block text-sm text-steel-600">{{ $o->brand }}</span>@endif</td>
                                            <td class="py-2 pr-4 text-right whitespace-nowrap tabular">{{ Pricing::formatPhp($o->price_php) }}@if (\App\Support\Shop::perMetre($o))<span class="text-sm text-steel-600"> /m</span>@endif</td>
                                            <td class="py-2 pr-4 text-right">
                                                <label for="ba-{{ $o->id }}" class="sr-only">Quantity of {{ $o->name }}</label>
                                                <input id="ba-{{ $o->id }}" name="addon[{{ $o->id }}]" type="number" inputmode="numeric" min="0" max="{{ $maxQty }}" step="1" value="{{ (int) old('addon.'.$o->id, 0) }}" data-addon data-price="{{ (float) $o->price_php }}"
                                                    style="width: 5rem" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 bg-white px-2.5 text-right text-base text-steel-950 tabular">
                                            </td>
                                            <td class="py-2 text-right font-semibold whitespace-nowrap text-steel-950 tabular" style="min-width: 6rem" data-amount>₱0</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
        <dl class="mt-5 space-y-1 rounded-[var(--radius-panel)] bg-steel-50 p-4">
            <div class="flex justify-between gap-4 text-steel-700"><dt>Main parts</dt><dd class="tabular" data-main-total>{{ Pricing::formatPhp($buildTotal) }}</dd></div>
            @if ($addons)<div class="flex justify-between gap-4 text-steel-700"><dt>Add-ons</dt><dd class="tabular" data-addon-total>{{ Pricing::formatPhp($addonTotal) }}</dd></div>@endif
            <div class="flex justify-between gap-4 border-t border-steel-300 text-lg font-extrabold text-steel-950" style="padding-top: .5rem; margin-top: .5rem"><dt>Total</dt><dd class="tabular" data-total>{{ Pricing::formatPhp($buildTotal + $addonTotal) }}</dd></div>
        </dl>
        <p class="mt-2 text-sm text-steel-600">Everything is priced at our current pickup prices and added to your basket as separate items. Installation and delivery are not included.</p>
        <button type="submit" class="{{ Ui::button('primary', 'lg', 'mt-4 w-full sm:w-auto') }}"><x-icon name="cart" class="size-5" /> Add to Basket</button>
    </form>
    <script>
        (() => {
            const form = document.querySelector('[data-pkg-builder]');
            if (!form) return;
            const peso = (n) => '₱' + Math.round(n).toLocaleString('en-PH');
            const update = () => {
                let main = 0, extra = 0;
                form.querySelectorAll('[data-line]').forEach((row) => {
                    const pick = row.querySelector('[data-product]');
                    const price = parseFloat(pick.tagName === 'SELECT' ? pick.selectedOptions[0].dataset.price : pick.dataset.price) || 0;
                    const qty = Math.max(0, parseInt(row.querySelector('[data-qty]').value, 10) || 0);
                    row.querySelector('[data-unit]').firstChild.textContent = peso(price);
                    row.querySelector('[data-amount]').textContent = peso(price * qty);
                    row.style.opacity = qty ? '' : '.55';
                    main += price * qty;
                });
                form.querySelectorAll('[data-addon-group]').forEach((group) => {
                    let sum = 0, n = 0;
                    group.querySelectorAll('[data-addon-row]').forEach((row) => {
                        const input = row.querySelector('[data-addon]');
                        const qty = Math.max(0, parseInt(input.value, 10) || 0);
                        const amount = (parseFloat(input.dataset.price) || 0) * qty;
                        row.querySelector('[data-amount]').textContent = peso(amount);
                        sum += amount; n += qty ? 1 : 0;
                    });
                    group.querySelector('[data-group-total]').textContent = n ? n + (n === 1 ? ' item · ' : ' items · ') + peso(sum) : '';
                    extra += sum;
                });
                form.querySelectorAll('[data-main-total]').forEach((el) => { el.textContent = peso(main); });
                const addonEl = form.querySelector('[data-addon-total]');
                if (addonEl) addonEl.textContent = peso(extra);
                form.querySelector('[data-total]').textContent = peso(main + extra);
                // Recommended mounting follows the number of panels.
                const mountBox = form.querySelector('[data-mount]');
                if (mountBox) {
                    const n = Math.max(0, parseInt(form.querySelector('[name="quantity[' + mountBox.dataset.panelLine + ']"]')?.value, 10) || 0);
                    mountBox.querySelector('[data-mount-n]').textContent = n;
                    mountBox.querySelector('[data-mount-word]').textContent = n === 1 ? 'panel' : 'panels';
                    let mt = 0;
                    mountBox.querySelectorAll('[data-mount-row]').forEach((row) => {
                        const q = n > 0 ? (+row.dataset.per) * n + (+row.dataset.fixed) : 0;
                        row.querySelector('[data-mount-qty]').textContent = q;
                        row.querySelector('[data-mount-amount]').textContent = peso(q * (+row.dataset.price));
                        mt += q * (+row.dataset.price);
                    });
                    mountBox.querySelector('[data-mount-total]').textContent = peso(mt);
                    mountBox.querySelector('[data-mount-apply]').disabled = n < 1;
                }
            };
            const toggleAll = form.querySelector('[data-addons-toggle]');
            if (toggleAll) {
                const groups = [...form.querySelectorAll('[data-addon-group]')];
                const sync = () => {
                    const allOpen = groups.every((g) => g.open);
                    toggleAll.setAttribute('aria-expanded', String(allOpen));
                    toggleAll.querySelector('[data-addons-toggle-label]').textContent = allOpen ? 'Close all' : 'Open all';
                    toggleAll.querySelector('.addon-chev').style.transform = allOpen ? 'rotate(180deg)' : '';
                };
                toggleAll.hidden = false;
                toggleAll.addEventListener('click', () => { const open = !groups.every((g) => g.open); groups.forEach((g) => { g.open = open; }); sync(); });
                groups.forEach((g) => g.addEventListener('toggle', sync));
                sync();
            }
            const mountBox = form.querySelector('[data-mount]');
            if (mountBox) {
                const apply = mountBox.querySelector('[data-mount-apply]');
                apply.hidden = false;
                apply.addEventListener('click', () => {
                    mountBox.querySelectorAll('[data-mount-row]').forEach((row) => {
                        const input = form.querySelector('#ba-' + row.dataset.id);
                        if (input) { input.value = row.querySelector('[data-mount-qty]').textContent; input.closest('details')?.setAttribute('open', ''); }
                    });
                    mountBox.querySelector('[data-mount-done]').hidden = false;
                    update();
                });
            }
            form.addEventListener('input', update);
            form.addEventListener('change', update);
            update();
        })();
    </script>
</section>
