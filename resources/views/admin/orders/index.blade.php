@php
    use App\Support\Pricing;
    $tabs = ['paid' => 'To prepare', 'ready' => 'Ready for pickup', 'completed' => 'Picked up', 'unpaid' => 'Not paid', 'cancelled' => 'Cancelled'];
    $countFor = fn ($key) => collect(\App\Http\Controllers\Admin\OrderController::VIEWS[$key])->sum(fn ($s) => $counts[$s] ?? 0);
    $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
@endphp
<x-admin.layout title="Orders">
    <x-admin.page-title title="Orders" intro="Orders placed on the website — paid online, or reserved to pay at pickup. Prepare the items, mark the order ready, and record payment and pickup when the customer collects." />
    <nav aria-label="Filter orders" class="mb-4 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="/admin/orders{{ $key === 'paid' ? '' : '?view='.$key }}" @if ($view === $key) aria-current="page" @endif class="{{ $tab }}">{{ $label }} <span class="tabular">({{ $countFor($key) }})</span></a>
        @endforeach
    </nav>
    @if ($view === 'unpaid')
        <p class="mb-4 text-sm text-steel-700">Online-payment orders where the customer opened the payment page but PayMongo has not confirmed payment. Do not release items for these.</p>
    @endif
    @if ($orders->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $view === 'paid' ? 'No paid orders waiting to be prepared.' : 'Nothing here.' }}</p>
    @else
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($orders as $o)
                <li class="p-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <a href="/admin/orders/{{ $o->id }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline"><span class="font-mono">{{ $o->reference }}</span> · {{ $o->customer_name }}</a>
                        <span class="text-sm text-steel-600 tabular">{{ $o->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}</span>
                    </div>
                    <p class="text-sm text-steel-700">{{ $o->items_count }} {{ $o->items_count === 1 ? 'item' : 'items' }} · <span class="font-semibold tabular">{{ Pricing::formatPhp($o->total_php) }}</span> · {{ $o->statusLabel() }} · {{ $o->paid_at ? 'Paid' : ($o->payment_mode === 'in_person' ? 'Pays at pickup'.($o->payment_preference ? ' ('.\App\Support\Shop::choiceLabel($o->payment_preference).')' : '') : 'Not paid') }} · {{ \App\Support\Shop::LOCATION_NAMES[$o->pickup_location] ?? 'Office' }}</p>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-admin.layout>
