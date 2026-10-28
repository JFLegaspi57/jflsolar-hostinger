@php
    use App\Support\Labels;
    $catName = $categories->pluck('name', 'id');
    $rows = $products->map(function ($p) use ($catName) {
        $price = $p->price();

        return [
            'id' => $p->id, 'name' => $p->name, 'status' => $p->status, 'isSample' => $p->is_sample, 'featured' => $p->featured,
            'subtitle' => implode(' · ', array_filter([$catName[$p->category_id] ?? null, $p->brand, $p->model, $price['hasAmount'] ? $price['headline'] : 'Request price', Labels::AVAILABILITY[$p->availability]])),
        ];
    })->all();
    $field = 'min-h-11 rounded-[var(--radius-control)] border border-steel-500 bg-white px-3 text-base';
@endphp
<x-admin.layout title="Products">
    <x-admin.page-title title="Products" intro="Equipment catalogue. Listing a brand does not mean JFL Solar is an authorised distributor — only state that once it is confirmed.">
        <x-slot:action><x-admin.new-button href="/admin/products/new">New product</x-admin.new-button></x-slot:action>
    </x-admin.page-title>
    <form method="get" role="search" aria-label="Find products" class="mb-5 flex flex-wrap items-end gap-2 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-3">
        @if ($archived)<input type="hidden" name="view" value="archived">@endif
        <div class="min-w-0 flex-1 basis-48">
            <label for="admin-q" class="mb-1 block text-sm font-semibold">Search name, brand or model</label>
            <input id="admin-q" name="q" type="search" value="{{ $q }}" class="{{ $field }} w-full">
        </div>
        <div class="basis-48">
            <label for="admin-cat" class="mb-1 block text-sm font-semibold">Category</label>
            <select id="admin-cat" name="category" class="{{ $field }} w-full">
                <option value="">All categories</option>
                @foreach ($categories as $c)<option value="{{ $c->id }}" @selected($category === $c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-4 font-semibold text-white">Find</button>
        @if ($filtering)<a href="{{ $archived ? '/admin/products?view=archived' : '/admin/products' }}" class="inline-flex min-h-11 items-center px-2 font-semibold underline">Clear</a>@endif
    </form>
    @if ($filtering)
        <p role="status" class="mb-3 text-sm text-steel-600">Showing {{ count($rows) }} of {{ $total }}. Reordering is only available when no search is active.</p>
    @endif
    <x-admin.record-list entity="products" :rows="$rows" :archived="$archived" :can-reorder="! $filtering" :keep="$keep" :empty-text="$filtering ? 'No products match your search.' : 'No products yet.'" />
</x-admin.layout>
