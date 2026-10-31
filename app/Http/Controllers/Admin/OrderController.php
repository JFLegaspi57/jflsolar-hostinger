<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Online orders paid through PayMongo. Staff prepare them and record pickup. */
class OrderController extends Controller
{
    /** Tabs: what staff need to act on first. */
    public const VIEWS = [
        'paid' => ['paid'],
        'ready' => ['ready'],
        'completed' => ['completed'],
        'unpaid' => ['pending_payment'],
        'cancelled' => ['cancelled'],
    ];

    public function index(Request $request): View
    {
        $this->guard();
        $view = array_key_exists((string) $request->query('view'), self::VIEWS) ? (string) $request->query('view') : 'paid';
        $orders = Order::query()->whereIn('status', self::VIEWS[$view])->withCount('items')->latest('id')->paginate(30)->withQueryString();
        $counts = Order::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.orders.index', ['orders' => $orders, 'view' => $view, 'counts' => $counts]);
    }

    public function show(int $order): View
    {
        $this->guard();

        return view('admin.orders.show', ['order' => Order::query()->with('items')->findOrFail($order)]);
    }

    public function update(Request $request, int $order): RedirectResponse
    {
        $this->guard();
        $o = Order::query()->findOrFail($order);
        $back = redirect('/admin/orders/'.$o->id);
        if ((int) $request->input('lock_version', -1) !== $o->lock_version) {
            return $back->with('status_error', 'Someone else changed this order while you had it open. Check it and try again.');
        }

        $data = [];
        $status = (string) $request->input('status', '');
        if ($status !== '') {
            // Payment is confirmed only by PayMongo, so staff cannot mark an unpaid order paid here.
            $allowed = ['paid' => ['ready', 'completed', 'cancelled'], 'ready' => ['paid', 'completed', 'cancelled'], 'completed' => ['ready'], 'pending_payment' => ['cancelled'], 'cancelled' => []];
            if (! in_array($status, $allowed[$o->status] ?? [], true)) {
                return $back->with('status_error', 'That status change is not possible for this order.');
            }
            $data['status'] = $status;
        }
        if ($request->has('staff_notes')) {
            $notes = trim((string) $request->input('staff_notes'));
            if (mb_strlen($notes) > 4000) {
                return $back->withInput()->with('status_error', 'Notes must be 4,000 characters or fewer.');
            }
            $data['staff_notes'] = $notes ?: null;
        }
        if (! $data) {
            return $back;
        }
        // Also conditional on the status staff saw: a payment arriving meanwhile bumps the version.
        $saved = Order::query()->whereKey($o->id)->where('lock_version', $o->lock_version)->where('status', $o->status)
            ->update($data + ['lock_version' => $o->lock_version + 1, 'updated_at' => now()]);
        if (! $saved) {
            return $back->with('status_error', 'Someone else changed this order while you had it open. Check it and try again.');
        }

        return $back->with('status', isset($data['status']) ? 'Order marked “'.Shop::STATUSES[$data['status']].'”.' : 'Notes saved.');
    }

    /** Orders exist only once the shop has been switched on; until then this page does not either. */
    private function guard(): void
    {
        abort_unless(Shop::ordersVisible(), 404);
        if (Shop::enabled()) {
            Shop::ensureTables();
        }
        abort_unless(Shop::hasTables(), 404);
    }
}
