<x-site-layout :title="$product->name" :path="'/products/'.$product->slug" :description="$product->summary ?: $product->name" :noindex="$product->is_sample" :reviews-above-footer="false">
    <x-detail.product :product="$product" :settings="$settings" />
</x-site-layout>
