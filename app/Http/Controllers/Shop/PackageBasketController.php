<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Support\PackageBuilder;
use App\Support\PublicContent;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Adds a package's parts to the basket as separate products ("Build your package"). */
class PackageBasketController extends Controller
{
    public function add(Request $request, string $slug): RedirectResponse
    {
        abort_unless(Shop::visible(), 404);
        $pkg = PublicContent::snapshot()['packages']->firstWhere('slug', $slug);
        abort_unless($pkg instanceof Package, 404);
        $back = redirect('/packages/'.$pkg->slug.'#build');
        $lines = PackageBuilder::lines($pkg);
        if (! $lines) {
            return $back->with('pkg_error', 'Sorry, this package can’t be bought online right now. Please ask us for a quotation.');
        }
        $chosen = (array) $request->input('product', []);
        $quantities = (array) $request->input('quantity', []);
        $cart = Shop::raw();
        $added = [];
        foreach ($lines as $line) {
            $i = $line['index'];
            $qty = Shop::clampQuantity($quantities[$i] ?? 0);
            if ($qty < 1) {
                continue;   // the customer left this part out
            }
            $product = collect($line['options'])->firstWhere('id', (string) ($chosen[$i] ?? '')) ?? (count($line['options']) === 1 ? $line['options'][0] : null);
            if (! $product) {
                return $back->withInput()->with('pkg_error', 'Please choose a '.mb_strtolower($line['label']).'.');
            }
            $cart[$product->id] = Shop::clampQuantity(($cart[$product->id] ?? 0) + $qty);
            $added[] = $qty.' × '.$product->name;
        }
        // Optional add-ons (mounting, cables, protection devices, accessories): addon[product id] = quantity.
        $addonProducts = collect(PackageBuilder::addons($lines))->flatMap(fn ($g) => $g['products'])->keyBy('id');
        foreach ((array) $request->input('addon', []) as $id => $qty) {
            $product = $addonProducts->get((string) $id);
            $qty = Shop::clampQuantity($qty);
            if ($product && $qty > 0) {
                $cart[$product->id] = Shop::clampQuantity(($cart[$product->id] ?? 0) + $qty);
                $added[] = $qty.' × '.$product->name;
            }
        }
        if (! $added) {
            return $back->withInput()->with('pkg_error', 'Please set a quantity of at least 1 for one of the parts.');
        }
        Shop::put($cart);

        return $back->with('pkg_added', $added);
    }
}
