{{-- "Call Sales before pickup" note. Needs $settings. Optional: $reference (order number). --}}
<p role="note" class="rounded-[var(--radius-control)] border border-[#f0d39a] bg-warn-50 px-4 py-3 text-warn-800">
    <span class="font-bold">Please call our Sales team before pickup</span> at
    <a href="{{ \App\Support\Site::telHref($settings->phone_e164) }}" class="font-semibold underline tabular">{{ $settings->phone_display ?? $settings->phone_e164 }}</a>@isset($reference) and mention order <span class="font-mono">{{ $reference }}</span>@endisset,
    so we can confirm your order is ready before you go to {{ \App\Support\Shop::pickupWhere($settings) }}.
</p>
