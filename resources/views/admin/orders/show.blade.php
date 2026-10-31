@php
    use App\Support\Pricing;
    $o = $order;
    $btn = 'inline-flex min-h-11 items-center rounded-[var(--radius-control)] px-4 text-sm font-semibold';
    $digits = preg_replace('/[^0-9+]/', '', $o->customer_phone);
    $actions = [
        'paid' => ['ready' => 'Mark ready for pickup', 'completed' => 'Mark picked up'],
        'ready' => ['completed' => 'Mark picked up', 'paid' => 'Back to “to prepare”'],
        'completed' => ['ready' => 'Undo pickup'],
        'pending_payment' => [],
        'cancelled' => [],
    ][$o->status] ?? [];
@endphp
<x-admin.layout :title="'Order '.$o->reference">
    <x-admin.page-title :title="'Order '.$o->reference" back="/admin/orders" back-label="All orders" :intro="'Placed '.$o->created_at->timezone('Asia/Manila')->format('j F Y, g:i A').' · '.$o->statusLabel()" />

    @if ($o->status === 'pending_payment')
        <p class="mb-4 rounded-[var(--radius-panel)] border border-[#f0d39a] bg-warn-50 p-4 font-semibold text-warn-800">Not paid. PayMongo has not confirmed a payment for this order — do not release the items.</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        <div class="space-y-6">
            <section aria-label="Customer" class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
                <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-[max-content_1fr]">
                    <dt class="font-semibold">Customer</dt><dd>{{ $o->customer_name }}</dd>
                    <dt class="font-semibold">Phone</dt><dd><a href="tel:{{ $digits }}" class="underline tabular">{{ $o->customer_phone }}</a></dd>
                    <dt class="font-semibold">Email</dt><dd><a href="mailto:{{ $o->customer_email }}" class="underline break-all">{{ $o->customer_email }}</a></dd>
                    <dt class="font-semibold">Fulfilment</dt><dd>Pickup</dd>
                    <dt class="font-semibold">Payment</dt>
                    <dd>
                        @if ($o->paid_at)
                            Paid {{ Pricing::formatPhp($o->amount_paid_php ?? $o->total_php) }}{{ $o->payment_method ? ' by '.\App\Support\Shop::methodName($o->payment_method) : '' }} on {{ $o->paid_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}
                            @if ($o->paymongo_payment_id)<br><span class="font-mono text-sm text-steel-600">PayMongo {{ $o->paymongo_payment_id }}</span>@endif
                        @else
                            Not paid
                        @endif
                    </dd>
                    <dt class="font-semibold">Email alert</dt><dd>{{ $o->emailed_at ? 'Sent '.$o->emailed_at->timezone('Asia/Manila')->format('j M Y, g:i A') : 'Not sent' }}</dd>
                </dl>
                @if ($o->customer_notes)
                    <h2 class="mt-6 text-lg font-bold text-steel-950">Customer notes</h2>
                    <p class="mt-2 whitespace-pre-line text-steel-800">{{ $o->customer_notes }}</p>
                @endif
            </section>

            <section aria-labelledby="items-h" class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
                <h2 id="items-h" class="text-lg font-bold text-steel-950">Items</h2>
                <table class="mt-3 w-full text-left text-sm">
                    <thead class="border-b border-steel-300 text-steel-700"><tr><th class="py-2 pr-4">Item</th><th class="py-2 pr-4 text-right">Qty</th><th class="py-2 pr-4 text-right">Price</th><th class="py-2 text-right">Total</th></tr></thead>
                    <tbody class="divide-y divide-steel-200">
                        @foreach ($o->items as $i)
                            <tr>
                                <td class="py-2 pr-4"><span class="font-semibold">{{ $i->name }}</span>@if ($i->brand || $i->model)<br><span class="text-steel-600">{{ trim($i->brand.' '.$i->model) }}</span>@endif</td>
                                <td class="py-2 pr-4 text-right tabular">{{ $i->quantity }} {{ $i->unitLabel() }}</td>
                                <td class="py-2 pr-4 text-right tabular">{{ Pricing::formatPhp($i->unit_price_php) }}</td>
                                <td class="py-2 text-right tabular">{{ Pricing::formatPhp($i->line_total_php) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="border-t border-steel-300 font-bold"><td colspan="3" class="py-2 pr-4 text-right">Total</td><td class="py-2 text-right tabular">{{ Pricing::formatPhp($o->total_php) }}</td></tr></tfoot>
                </table>
            </section>
        </div>

        <aside class="space-y-4">
            <div class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <h2 class="font-bold text-steel-950">Status</h2>
                <p class="mt-1 text-steel-800">{{ $o->statusLabel() }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($actions as $status => $label)
                        <form method="post" action="/admin/orders/{{ $o->id }}">@csrf<input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="lock_version" value="{{ $o->lock_version }}">
                            <button type="submit" class="{{ $btn }} {{ $loop->first ? 'bg-steel-900 text-white hover:bg-steel-800' : 'border border-steel-300 bg-white text-steel-900 hover:border-steel-900' }}">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
                @if (in_array($o->status, ['paid', 'ready', 'pending_payment'], true))
                    <form method="post" action="/admin/orders/{{ $o->id }}" class="mt-3">@csrf<input type="hidden" name="status" value="cancelled"><input type="hidden" name="lock_version" value="{{ $o->lock_version }}">
                        <button type="submit" class="{{ $btn }} border border-danger-700 bg-white text-danger-700 hover:bg-danger-50">Cancel order</button>
                    </form>
                    @if ($o->paid_at)
                        <p class="mt-2 text-sm text-steel-600">Cancelling here does not refund the customer. Issue any refund in the PayMongo dashboard.</p>
                    @endif
                @endif
            </div>
            <form method="post" action="/admin/orders/{{ $o->id }}" class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                @csrf
                <input type="hidden" name="lock_version" value="{{ $o->lock_version }}">
                <label for="notes" class="font-bold text-steel-950">Staff notes</label>
                <p id="notes-hint" class="text-sm text-steel-600">For your team only — e.g. pickup date agreed with the customer.</p>
                <textarea id="notes" name="staff_notes" rows="5" maxlength="4000" aria-describedby="notes-hint" class="mt-2 w-full rounded-[var(--radius-control)] border border-steel-500 px-3 py-2">{{ old('staff_notes', $o->staff_notes) }}</textarea>
                <button type="submit" class="{{ $btn }} mt-2 border border-steel-300 bg-white text-steel-900 hover:border-steel-900">Save notes</button>
            </form>
        </aside>
    </div>
</x-admin.layout>
