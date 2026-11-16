<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\InventorySync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin → Inventory sync: link the Google Sheet and see what the last sync changed. */
class InventoryController extends Controller
{
    public function index(): View
    {
        return view('admin.inventory', [
            'configured' => InventorySync::configured(),
            'last' => InventorySync::last(),
            'products' => Product::query()->orderBy('name')->get(['slug', 'name', 'brand', 'model']),
            'script' => session('inventory_script'),
        ]);
    }

    /** Owner: make a new secret and show the script once. */
    public function connect(): RedirectResponse
    {
        $script = InventorySync::script(InventorySync::newSecret());

        return redirect('/admin/inventory')->with('inventory_script', $script)->with('status', 'New sync link made. Copy the script below into your Google Sheet now: it is shown only once.');
    }

    public function disconnect(): RedirectResponse
    {
        InventorySync::forgetSecret();

        return redirect('/admin/inventory')->with('status', 'Sync link removed. The sheet can no longer change the website.');
    }

    /** Link a sheet item to a product ("-" = not sold on the website; empty = undo). */
    public function link(Request $request): RedirectResponse
    {
        $name = trim(mb_substr((string) $request->input('name'), 0, 200));
        $slug = (string) $request->input('slug');
        if ($name === '' || ($slug !== '' && $slug !== '-' && ! Product::query()->where('slug', $slug)->exists())) {
            return redirect('/admin/inventory')->with('status_error', 'Choose a product to link.');
        }
        InventorySync::link($name, $slug === '' ? null : $slug);

        return redirect('/admin/inventory')->with('status', $slug === '-' ? '“'.$name.'” will be ignored.' : '“'.$name.'” linked. It takes effect at the next sync.');
    }
}
