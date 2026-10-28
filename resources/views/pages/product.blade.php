<x-site-layout :title="$product->name" :path="'/products/'.$product->slug" :description="$product->summary ?: $product->name" :noindex="$product->is_sample">
    <x-detail.product :product="$product" :settings="$settings" />
</x-site-layout>
