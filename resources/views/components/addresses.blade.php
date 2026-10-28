@props(['settings'])
<dl class="grid gap-4 sm:grid-cols-2">
    <div class="rounded-[var(--radius-control)] border border-steel-200 bg-white p-4">
        <dt class="flex items-center gap-2 text-sm font-bold tracking-wide text-steel-950 uppercase"><x-icon name="building" class="size-4 text-gold-800" /> Office</dt>
        <dd class="mt-1.5 text-steel-700">
            <address class="not-italic">{{ $settings->office_address }}</address>
            <p class="mt-1 text-sm text-steel-600">Main office.</p>
        </dd>
    </div>
    @if ($settings->warehouse_address)
        <div class="rounded-[var(--radius-control)] border border-steel-200 bg-white p-4">
            <dt class="flex items-center gap-2 text-sm font-bold tracking-wide text-steel-950 uppercase"><x-icon name="warehouse" class="size-4 text-gold-800" /> Warehouse</dt>
            <dd class="mt-1.5 text-steel-700">
                <address class="not-italic">{{ $settings->warehouse_address }}</address>
                <p class="mt-1 text-sm text-steel-600">Equipment warehouse. Pickup details are confirmed with your quotation.</p>
            </dd>
        </div>
    @endif
    @if ($settings->business_hours)
        <div class="sm:col-span-2">
            <dt class="text-sm font-bold text-steel-950">Business hours</dt>
            <dd class="text-steel-700">{{ $settings->business_hours }}</dd>
        </div>
    @endif
</dl>
