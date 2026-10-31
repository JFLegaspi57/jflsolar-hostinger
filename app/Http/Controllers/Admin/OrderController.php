<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Online orders: paid through PayMongo, or reserved to pay at pickup. Staff prepare them and record pickup. */
class OrderController extends Controller
{
    /** Tabs: what staff need to act on first. */
    public const VIEWS = [
        'paid' => ['reserved', 'paid'],
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
            $inPerson = $o->payment_mode === 'in_person';
            $unpaid = $o->paid_at === null;
            // Online orders are marked paid only by PayMongo. Pay-at-pickup orders are marked paid
            // by staff, who record how the customer paid at the counter.
            $allowed = [
                'pending_payment' => ['cancelled'],
                'reserved' => ['ready', 'paid', 'completed', 'cancelled'],
                'paid' => ['ready', 'completed', 'cancelled'],
                'ready' => [$unpaid ? 'reserved' : 'paid', 'completed', 'cancelled'],
                'completed' => ['ready'],
                'cancelled' => [],
            ][$o->status] ?? [];
            if ($unpaid && ! $inPerson) {
                $allowed = array_values(array_diff($allowed, ['paid', 'completed']));
            }
            if ($unpaid && $status === 'paid' && $o->status === 'ready') {
                $allowed = [];   // record the payment with "Paid & picked up" instead
            }
            if (! in_array($status, $allowed, true)) {
                return $back->with('status_error', 'That status change is not possible for this order.');
            }
            if ($inPerson && $unpaid && in_array($status, ['paid', 'completed'], true)) {
                $method = (string) $request->input('counter_method', '');
                if (! array_key_exists($method, Shop::COUNTER_METHODS)) {
                    return $back->with('status_error', 'Choose how the customer paid before recording the payment.');
                }
                $data += ['paid_at' => now(), 'amount_paid_php' => $o->total_php, 'payment_method' => $method];
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

        $message = match (true) {
            ! isset($data['status']) => 'Notes saved.',
            isset($data['paid_at']) && $data['status'] === 'completed' => 'Payment recorded — order picked up.',
            isset($data['paid_at']) => 'Payment recorded.',
            default => 'Order marked “'.Shop::STATUSES[$data['status']].'”.',
        };

        return $back->with('status', $message);
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
