{{-- The member's orders (account page and Your Orders page). --}}
@php use App\Support\Pricing; use App\Support\Ui; @endphp
@if ($orders->isEmpty())
    <p class="mt-3 text-steel-700">No orders yet. Orders you place while signed in appear here.</p>
    <p class="mt-4"><a href="/products" class="{{ Ui::button('primary') }}">Browse products</a></p>
@else
    <ul class="mt-3 divide-y divide-steel-200">
        @foreach ($orders as $o)
            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                <div>
                    <a href="{{ $o->publicPath() }}" class="font-mono font-semibold text-steel-950 underline">{{ $o->reference }}</a>
                    <p class="text-sm text-steel-600">{{ $o->created_at->copy()->setTimezone('Asia/Manila')->format('M j, Y') }} · {{ $o->items_count }} {{ $o->items_count === 1 ? 'item' : 'items' }} · {{ $o->statusLabel() }}</p>
                </div>
                <span class="font-semibold text-steel-950 tabular">{{ Pricing::formatPhp($o->total_php) }}</span>
            </li>
        @endforeach
    </ul>
@endif
