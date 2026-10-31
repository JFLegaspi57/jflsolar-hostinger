@php
    use App\Support\Pricing;
    use App\Support\Ui;
    $lines = $summary['lines'];
@endphp
<x-site-layout title="Your cart" path="/cart" :noindex="true" :reviews-above-footer="false">
    <x-container class="py-10 sm:py-14">
        <h1 class="text-3xl font-extrabold text-steel-950 sm:text-4xl">Your cart</h1>
        @include('partials.shop-preview-note')

        @if ($cancelled)
            <p role="status" class="mt-6 rounded-[var(--radius-control)] border border-steel-300 bg-steel-50 p-4 text-steel-800">Payment for order <span class="font-mono">{{ $cancelled }}</span> was not completed, so nothing was charged. Your items are still here.</p>
        @endif
        @if (session('cart_status'))<p role="status" class="mt-6 rounded-[var(--radius-control)] border border-[#b7e2c6] bg-ok-50 p-4 font-semibold text-ok-700">{{ session('cart_status') }}</p>@endif
        @if (session('cart_error'))<p role="alert" class="mt-6 rounded-[var(--radius-control)] border border-[#f3c3be] bg-danger-50 p-4 font-semibold text-danger-700">{{ session('cart_error') }}</p>@endif
        @if ($summary['removed'])
            <p role="alert" class="mt-6 rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 p-4 font-semibold text-warn-800">Some items can no longer be bought online and were removed from your cart.</p>
        @endif

        @if (! $lines)
            <div class="mt-8 rounded-[var(--radius-panel)] border border-dashed border-steel-300 p-8 text-center">
                <p class="text-lg text-steel-700">Your cart is empty.</p>
                <a href="/products" class="{{ Ui::button('primary', 'lg', 'mt-4') }}">Browse products</a>
            </div>
        @else
            <form method="post" action="/cart" class="mt-8">
                @csrf
                {{-- Pressing Enter in a quantity box uses the form's first button: make that "Update", not "Remove". --}}
                <button type="submit" tabindex="-1" aria-hidden="true" class="sr-only">Update quantities</button>
                <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
                    @foreach ($lines as $line)
                        @php $p = $line['product']; $qid = 'qty-'.$loop->index; @endphp
                        <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                            <div class="min-w-0 flex-1">
                                <a href="/products/{{ $p->slug }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline">{{ $p->name }}</a>
                                <p class="text-sm text-steel-600">{{ trim(($p->brand ?? '').' '.($p->model ?? '')) }}</p>
                                <p class="mt-1 text-sm text-steel-700 tabular">{{ Pricing::formatPhp($line['unit_price']) }} {{ \App\Support\Shop::perMetre($p) ? 'per metre' : 'each' }}</p>
                            </div>
                            <div class="flex items-end gap-2">
                                <div>
                                    <label for="{{ $qid }}" class="mb-1 block text-sm font-semibold text-steel-950">Quantity{{ \App\Support\Shop::perMetre($p) ? ' (m)' : '' }}</label>
                                    <input id="{{ $qid }}" name="quantity[{{ $p->id }}]" type="number" inputmode="numeric" min="0" max="{{ config('shop.max_quantity') }}" step="1" value="{{ $line['qty'] }}"
                                        style="width: 6rem" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base tabular focus:border-steel-900">
                                </div>
                                <button type="submit" name="remove" value="{{ $p->id }}" class="{{ Ui::button('ghost', 'sm') }}">Remove<span class="sr-only"> {{ $p->name }}</span></button>
                            </div>
                            <p class="text-right text-lg font-bold text-steel-950 tabular" style="min-width: 7rem">{{ Pricing::formatPhp($line['line_total']) }}</p>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <button type="submit" class="{{ Ui::button('secondary', 'md') }}">Update quantities</button>
                    <div class="text-right">
                        <p class="text-lg text-steel-800">Total <span class="ml-1 font-display text-3xl font-extrabold text-steel-950 tabular">{{ Pricing::formatPhp($summary['total']) }}</span></p>
                        <p class="mt-1 text-sm text-steel-600">Pickup prices. You collect the items from us — delivery is not included.</p>
                    </div>
                </div>
            </form>
            <div class="mt-6 flex flex-col gap-2 sm:flex-row justify-end">
                <a href="/products" class="{{ Ui::button('ghost', 'lg') }}">Continue shopping</a>
                <a href="/checkout" class="{{ Ui::button('primary', 'lg') }}">Check out <x-icon name="arrow" class="size-5" /></a>
            </div>
        @endif
    </x-container>
</x-site-layout>
