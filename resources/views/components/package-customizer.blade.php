{{-- "Customize this package" on complete (installed) packages: change the linked parts (e.g. panels and batteries), see the estimated price, then ask about that setup. --}}
@props(['pkg', 'lines'])
@php
    use App\Support\PackageBuilder;
    use App\Support\PackageCustomizer;
    use App\Support\Pricing;
    use App\Support\Ui;
    $base = PackageCustomizer::basePrice($pkg);
    $label = fn ($o) => trim(($o->brand && $o->brand !== 'Generic' && ! str_starts_with($o->name, $o->brand) ? $o->brand.' ' : '').$o->name);
@endphp
<section id="customize" aria-labelledby="customize-title" style="scroll-margin-top: 7rem" class="rounded-[var(--radius-panel)] border border-steel-300 bg-white p-5 sm:p-6">
    <h2 id="customize-title" class="text-xl font-bold text-steel-950">Customize this package</h2>
    <p class="mt-1 text-steel-700">Need more or fewer solar panels or batteries? Change the quantities below to see the estimated price of your own setup, then ask us about it.</p>
    <form method="get" action="/contact" class="mt-4" data-pkg-custom data-base="{{ $base }}">
        <input type="hidden" name="package" value="{{ $pkg->slug }}">
        <input type="hidden" name="custom" value="1">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-[0.95rem]">
                <caption class="sr-only">Parts you can change in {{ $pkg->name }}</caption>
                <thead class="border-b border-steel-300 text-xs tracking-wide text-steel-600 uppercase">
                    <tr><th scope="col" class="py-2 pr-4 font-semibold">Part</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Unit price</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Qty</th><th scope="col" class="py-2 text-right font-semibold">Change</th></tr>
                </thead>
                <tbody class="divide-y divide-steel-200">
                    @foreach ($lines as $line)
                        @php $first = $line['options'][0]; $i = $line['index']; @endphp
                        <tr data-line data-base-qty="{{ $line['quantity'] }}" data-base-price="{{ (float) $first->price_php }}">
                            <td class="py-3 pr-4 align-top" style="min-width: 14rem">
                                <span class="block font-semibold text-steel-950">{{ $line['label'] }}</span>
                                <span class="block text-sm text-steel-600">Package has {{ $line['quantity'] }}</span>
                                @if (count($line['options']) > 1)
                                    <label for="cp-{{ $i }}" class="sr-only">Choose the {{ mb_strtolower($line['label']) }}</label>
                                    <select id="cp-{{ $i }}" name="p[{{ $i }}]" data-product class="mt-1 min-h-11 w-full rounded-[var(--radius-control)] border border-steel-500 bg-white px-2.5 text-[0.95rem] text-steel-950">
                                        @foreach ($line['options'] as $o)
                                            <option value="{{ $o->slug }}" data-price="{{ (float) $o->price_php }}" data-w="{{ PackageBuilder::rating($o, 'W') }}" data-kwh="{{ PackageBuilder::rating($o, 'kWh') }}">{{ $label($o) }} — {{ Pricing::formatPhp($o->price_php) }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="p[{{ $i }}]" value="{{ $first->slug }}" data-product data-price="{{ (float) $first->price_php }}" data-w="{{ PackageBuilder::rating($first, 'W') }}" data-kwh="{{ PackageBuilder::rating($first, 'kWh') }}">
                                    <a href="/products/{{ $first->slug }}" class="text-steel-700 underline">{{ $label($first) }}</a>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right align-top whitespace-nowrap tabular" data-unit>{{ Pricing::formatPhp($first->price_php) }}</td>
                            <td class="py-3 pr-4 text-right align-top">
                                <label for="cq-{{ $i }}" class="sr-only">Quantity of {{ mb_strtolower($line['label']) }}</label>
                                <input id="cq-{{ $i }}" name="q[{{ $i }}]" type="number" inputmode="numeric" min="0" max="99" step="1" value="{{ $line['quantity'] }}" data-qty
                                    style="width: 5rem" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 bg-white px-2.5 text-right text-base text-steel-950 tabular">
                            </td>
                            <td class="py-3 text-right align-top font-semibold whitespace-nowrap text-steel-950 tabular" data-change>₱0</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <dl class="mt-5 space-y-1 rounded-[var(--radius-panel)] bg-steel-50 p-4">
            <div class="flex justify-between gap-4 text-steel-700"><dt>Package price</dt><dd class="tabular">{{ Pricing::formatPhp($base) }}</dd></div>
            <div class="flex justify-between gap-4 text-steel-700"><dt>Your changes</dt><dd class="tabular" data-changes>₱0</dd></div>
            <div class="flex justify-between gap-4 text-steel-700" data-cap hidden><dt>Your setup</dt><dd class="text-right" data-cap-text></dd></div>
            <div class="flex justify-between gap-4 border-t border-steel-300 text-lg font-extrabold text-steel-950" style="padding-top: .5rem; margin-top: .5rem"><dt>Estimated price</dt><dd class="tabular" data-total>{{ Pricing::formatPhp($base) }}</dd></div>
        </dl>
        <p class="mt-2 text-sm text-steel-600">Parts are added or taken away at our current prices; installation stays included. The final price is confirmed after the site ocular inspection.</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <button type="submit" class="{{ Ui::button('primary', 'lg', 'w-full sm:w-auto') }}">Ask About This Setup</button>
        </div>
    </form>
    <script>
        (() => {
            const form = document.querySelector('[data-pkg-custom]');
            if (!form) return;
            const peso = (n) => (n < 0 ? '−₱' : '₱') + Math.round(Math.abs(n)).toLocaleString('en-PH');
            const update = () => {
                let changes = 0, watts = 0, kwh = 0, hasW = false, hasKwh = false;
                form.querySelectorAll('[data-line]').forEach((row) => {
                    const pick = row.querySelector('[data-product]');
                    const opt = pick.tagName === 'SELECT' ? pick.selectedOptions[0] : pick;
                    const price = parseFloat(opt.dataset.price) || 0;
                    const qty = Math.max(0, Math.min(99, parseInt(row.querySelector('[data-qty]').value, 10) || 0));
                    const change = qty * price - (+row.dataset.baseQty) * (+row.dataset.basePrice);
                    row.querySelector('[data-unit]').textContent = peso(price);
                    row.querySelector('[data-change]').textContent = change > 0 ? '+' + peso(change) : peso(change);
                    changes += change;
                    if (+opt.dataset.w) { watts += qty * (+opt.dataset.w); hasW = true; }
                    if (+opt.dataset.kwh) { kwh += qty * (+opt.dataset.kwh); hasKwh = true; }
                });
                form.querySelector('[data-changes]').textContent = changes > 0 ? '+' + peso(changes) : peso(changes);
                form.querySelector('[data-total]').textContent = peso(Math.max(0, (+form.dataset.base) + changes));
                const parts = [];
                if (hasW) parts.push((Math.round(watts / 10) / 100) + ' kWp of solar panels');
                if (hasKwh) parts.push((Math.round(kwh * 100) / 100) + ' kWh of batteries');
                form.querySelector('[data-cap]').hidden = !parts.length;
                form.querySelector('[data-cap-text]').textContent = parts.join(' · ');
            };
            form.addEventListener('input', update);
            form.addEventListener('change', update);
            update();
        })();
    </script>
</section>
