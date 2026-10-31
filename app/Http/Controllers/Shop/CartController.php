<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The shopping cart, kept in the visitor's session. */
class CartController extends Controller
{
    public function show(Request $request): View
    {
        abort_unless(Shop::visible(), 404);
        $summary = Shop::summary();

        return view('pages.cart', [
            'summary' => $summary,
            'cancelled' => preg_match('/^JFL-\d{6}-[A-Z0-9]{4}$/', (string) $request->query('cancelled')) ? $request->query('cancelled') : null,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        abort_unless(Shop::visible(), 404);
        $product = Shop::catalog()->get((string) $request->input('product'));
        if (! $product) {
            return back()->with('cart_error', 'Sorry, that item can’t be bought online right now. Please request a quote instead.');
        }
        $qty = Shop::clampQuantity($request->input('quantity', 1));
        if ($qty < 1) {
            return back()->with('cart_error', 'Please enter a quantity of at least 1.');
        }
        $cart = Shop::raw();
        $cart[$product->id] = Shop::clampQuantity(($cart[$product->id] ?? 0) + $qty);
        Shop::put($cart);

        return redirect('/products/'.$product->slug)->with('cart_added', $cart[$product->id].' '.Shop::unitLabel($product, $cart[$product->id]));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(Shop::visible(), 404);
        $cart = Shop::raw();
        $quantities = $request->input('quantity', []);
        if (is_array($quantities)) {
            foreach ($quantities as $id => $qty) {
                if (isset($cart[$id])) {
                    $cart[$id] = Shop::clampQuantity($qty);
                }
            }
        }
        if (is_string($remove = $request->input('remove')) && isset($cart[$remove])) {
            unset($cart[$remove]);
        }
        Shop::put($cart);

        return redirect('/cart')->with('cart_status', 'Your cart was updated.');
    }
}
