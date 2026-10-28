@php
    use App\Support\Labels;
    $rows = $packages->map(function ($p) {
        $promo = $p->promoState();
        $price = $p->price($promo);
        $promoText = ['ended' => ' · Promotion ended', 'upcoming' => ' · Promotion upcoming', 'active' => ' · Promotion active'][$promo] ?? '';

        return [
            'id' => $p->id, 'name' => $p->name, 'status' => $p->status, 'isSample' => $p->is_sample, 'featured' => $p->featured,
            'subtitle' => Labels::PACKAGE_TYPE[$p->package_type].' · '.$price['headline'].($price['isSample'] ? ' (sample price)' : '').$promoText,
        ];
    })->all();
@endphp
<x-admin.layout title="Packages">
    <x-admin.page-title title="Packages" intro="Installed systems and equipment-only bundles. Use ↑ ↓ to set the order on the public site; ★ features a package on the home page.">
        <x-slot:action><x-admin.new-button href="/admin/packages/new">New package</x-admin.new-button></x-slot:action>
    </x-admin.page-title>
    <x-admin.record-list entity="packages" :rows="$rows" :archived="$archived" empty-text="No packages yet. Create the first one." />
</x-admin.layout>
