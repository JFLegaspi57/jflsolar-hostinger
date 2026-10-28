<x-site-layout :title="$pkg->name" :path="'/packages/'.$pkg->slug" :description="$pkg->summary ?: $pkg->name" :noindex="$pkg->is_sample">
    <x-detail.package :pkg="$pkg" :settings="$settings" />
</x-site-layout>
