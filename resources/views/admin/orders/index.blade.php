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
        @php
            $deletable = in_array($view, ['ready', 'completed', 'cancelled'], true);
            $total = $countFor($view);
            $warn = 'Paid orders lose their payment record too, so keep any records you need for your accounts first. This can’t be undone.';
        @endphp
        @if ($deletable)
            <div class="mb-3 flex flex-wrap items-center gap-3 rounded-[var(--radius-panel)] border border-steel-200 bg-white px-4 py-3">
                <label class="inline-flex min-h-11 items-center gap-2 font-semibold text-steel-900">
                    <input type="checkbox" id="pick-all" class="size-5"> Select all on this page
                </label>
                <span id="pick-count" class="text-sm text-steel-600" aria-live="polite">0 selected</span>
                <span class="ml-auto flex flex-wrap gap-2">
                    <button type="button" data-open-dialog="dlg-pick-selected" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border-2 border-danger-700 px-4 text-sm font-semibold text-danger-700 hover:bg-danger-50">Delete selected</button>
                    <x-admin.confirm action="/admin/orders/delete" :title="'Delete all '.$total.' “'.$tabs[$view].'” orders?'" confirm-label="Delete all permanently" :label="'Delete all ('.$total.')'" :fields="['all' => \App\Http\Controllers\Admin\OrderController::VIEWS[$view][0], 'back' => $view]">
                        <p>Every order in the {{ $tabs[$view] }} list is removed for good, with its items and customer details. {{ $warn }}</p>
                    </x-admin.confirm>
                </span>
            </div>
            <dialog id="dlg-pick-selected" aria-labelledby="dlg-pick-selected-title" class="m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[var(--radius-panel)] p-0 shadow-2xl">
                <form id="pick-bulk" method="post" action="/admin/orders/delete" class="p-6">
                    @csrf
                    <input type="hidden" name="back" value="{{ $view }}">
                    <h2 id="dlg-pick-selected-title" class="text-xl font-bold text-steel-950">Delete the selected orders?</h2>
                    <p class="mt-2 text-steel-700"><span id="pick-count-dlg">0 orders</span> will be removed for good, with their items and customer details. {{ $warn }}</p>
                    <div class="mt-6 flex flex-wrap justify-end gap-2">
                        <button type="button" data-close-dialog autofocus class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border-2 border-steel-900 px-4 font-semibold text-steel-900 hover:bg-steel-50">Cancel</button>
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-danger-700 px-4 font-semibold text-white hover:bg-[#93190f]">Delete permanently</button>
                    </div>
                </form>
            </dialog>
        @endif
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($orders as $o)
                <li class="flex gap-3 p-4">
                    @if ($deletable)<input type="checkbox" name="ids[]" value="{{ $o->id }}" form="pick-bulk" data-pick class="mt-1.5 size-5 shrink-0" aria-label="Select order {{ $o->reference }}">@endif
                    <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <a href="/admin/orders/{{ $o->id }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline"><span class="font-mono">{{ $o->reference }}</span> · {{ $o->customer_name }}</a>
                        <span class="text-sm text-steel-600 tabular">{{ $o->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}</span>
                    </div>
                    <div class="flex flex-wrap items-end justify-between gap-3">
                    <p class="min-w-0 flex-1 text-sm text-steel-700">{{ $o->items_count }} {{ $o->items_count === 1 ? 'item' : 'items' }} · <span class="font-semibold tabular">{{ Pricing::formatPhp($o->total_php) }}</span> · {{ $o->statusLabel() }} · {{ $o->paid_at ? 'Paid' : ($o->payment_mode === 'in_person' ? 'Pays at pickup'.($o->payment_preference ? ' ('.\App\Support\Shop::choiceLabel($o->payment_preference).')' : '') : 'Not paid') }} · {{ \App\Support\Shop::LOCATION_NAMES[$o->pickup_location] ?? 'Office' }}</p>
                    @if ($deletable)
                        <x-admin.confirm :action="'/admin/orders/'.$o->id.'/delete'" :title="'Delete order '.$o->reference.'?'" confirm-label="Delete permanently" label="Delete" :sr-label="'order '.$o->reference" :fields="['back' => $view]">
                            <p>The order from {{ $o->customer_name }} is removed for good, with its items and customer details. {{ $warn }}</p>
                        </x-admin.confirm>
                    @endif
                    </div>
                    </div>
                </li>
            @endforeach
        </ul>
        @if ($deletable)
            <script>
                (function () {
                    var all = document.getElementById('pick-all'), picks = [].slice.call(document.querySelectorAll('[data-pick]'));
                    function update() {
                        var n = picks.filter(function (p) { return p.checked; }).length;
                        document.getElementById('pick-count').textContent = n + ' selected';
                        document.getElementById('pick-count-dlg').textContent = n === 1 ? '1 order' : n + ' orders';
                        all.checked = n > 0 && n === picks.length;
                        all.indeterminate = n > 0 && n < picks.length;
                    }
                    all.addEventListener('change', function () { picks.forEach(function (p) { p.checked = all.checked; }); update(); });
                    picks.forEach(function (p) { p.addEventListener('change', update); });
                    update();
                })();
            </script>
        @endif
        <div class="mt-4">{{ $orders->links() }}</div>
    @endif
</x-admin.layout>
