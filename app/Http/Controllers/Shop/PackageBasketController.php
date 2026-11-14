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
        if ($slug === 'build-your-own') {
            $back = redirect('/packages/build-your-own#build');
            $lines = PackageBuilder::customLines();
        } else {
            $pkg = PublicContent::snapshot()['packages']->firstWhere('slug', $slug);
            abort_unless($pkg instanceof Package, 404);
            $back = redirect('/packages/'.$pkg->slug.'#build');
            $lines = PackageBuilder::lines($pkg);
        }
        if (! $lines) {
            return $back->with('pkg_error', 'Sorry, this package can’t be bought online right now. Please ask us for a quotation.');
        }
        $sel = PackageBuilder::selection($request, $lines);
        if ($sel['error']) {
            return $back->withInput()->with('pkg_error', $sel['error']);
        }
        $cart = Shop::raw();
        $added = [];
        foreach ($sel['items'] as ['product' => $product, 'qty' => $qty]) {
            $cart[$product->id] = Shop::clampQuantity(($cart[$product->id] ?? 0) + $qty);
            $added[] = $qty.' × '.$product->name;
        }
        Shop::put($cart);

        return $back->with('pkg_added', $added);
    }
}
