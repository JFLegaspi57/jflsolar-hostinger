<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Support\Input;
use App\Support\Members;
use App\Support\Newsletter;
use App\Support\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin → Members (customer accounts) and Admin → Subscribers (newsletter list). */
class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $q = mb_substr(trim(Input::str($request, 'q')), 0, 100);
        $members = Members::hasTables()
            ? Member::query()
                ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('email', 'like', '%'.addcslashes($q, '%_\\').'%')->orWhere('phone', 'like', '%'.addcslashes($q, '%_\\').'%')))
                ->when(Shop::hasTables() && Schema::hasColumn('orders', 'member_id'), fn ($w) => $w->withCount('orders'))
                ->latest('id')->paginate(40)->withQueryString()
            : null;

        return view('admin.members.index', ['members' => $members, 'q' => $q, 'logins' => $members ? Members::lastLogins($members->pluck('id')->all()) : []]);
    }

    /** One member: details, orders, sign-ins (time and place) and pages viewed while signed in. */
    public function show(int $member): View
    {
        Members::ensureTables();
        $m = Member::query()->findOrFail($member);
        $hasActivity = Schema::hasTable('member_activity');
        $orders = Shop::hasTables() && Schema::hasColumn('orders', 'member_id')
            ? \App\Models\Order::query()->where('member_id', $m->id)->latest('id')->limit(50)->get()
            : collect();

        return view('admin.members.show', [
            'm' => $m,
            'orders' => $orders,
            'logins' => $hasActivity ? DB::table('member_activity')->where('member_id', $m->id)->where('kind', 'login')->orderByDesc('id')->limit(50)->get() : collect(),
            'pages' => $hasActivity ? DB::table('member_activity')->where('member_id', $m->id)->where('kind', 'page')->orderByDesc('id')->limit(300)->get() : collect(),
            'subscribed' => Newsletter::isSubscribed($m->email),
        ]);
    }

    /** CSV of all members with their last sign-in time and place. */
    public function exportMembers(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['name', 'email', 'phone', 'joined', 'last_sign_in', 'last_sign_in_place', 'status', 'promos']);
            if (Members::hasTables()) {
                $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;
                Member::query()->orderBy('id')->chunk(200, function ($rows) use ($out, $safe) {
                    $logins = Members::lastLogins($rows->pluck('id')->all());
                    foreach ($rows as $m) {
                        $l = $logins[$m->id] ?? null;
                        fputcsv($out, [$safe($m->name), $safe($m->email), $safe($m->phone), $m->created_at?->timezone('Asia/Manila')->format('Y-m-d H:i'),
                            $l ? \Illuminate\Support\Carbon::parse($l->created_at)->timezone('Asia/Manila')->format('Y-m-d H:i') : $m->last_login_at?->timezone('Asia/Manila')->format('Y-m-d H:i'),
                            $safe($l->place ?? ''), $m->status, Newsletter::isSubscribed($m->email) ? 'yes' : 'no']);
                    }
                });
            }
            fclose($out);
        }, 'jflsolar-members-'.now('Asia/Manila')->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function update(Request $request, int $member): RedirectResponse
    {
        $m = Member::query()->findOrFail($member);
        $action = Input::str($request, 'action');
        if ($action === 'disable') {
            $m->forceFill(['status' => 'disabled', 'remember_token' => null])->save();
            $message = $m->name.'’s account is disabled. They are signed out and cannot sign in.';
        } elseif ($action === 'enable') {
            $m->forceFill(['status' => 'active'])->save();
            $message = $m->name.'’s account is active again.';
        } else {
            return back();
        }

        return redirect('/admin/members'.($request->query('q') ? '?q='.rawurlencode(Input::str($request, 'q')) : ''))->with('status', $message);
    }

    public function subscribers(Request $request): View
    {
        $show = $request->query('show') === 'left' ? 'left' : 'active';
        $rows = Newsletter::hasTables()
            ? DB::table('newsletter_subscribers')->when($show === 'active', fn ($w) => $w->whereNull('unsubscribed_at'), fn ($w) => $w->whereNotNull('unsubscribed_at'))->latest('id')->paginate(50)->withQueryString()
            : null;
        $counts = Newsletter::hasTables()
            ? ['active' => DB::table('newsletter_subscribers')->whereNull('unsubscribed_at')->count(), 'left' => DB::table('newsletter_subscribers')->whereNotNull('unsubscribed_at')->count()]
            : ['active' => 0, 'left' => 0];

        return view('admin.subscribers.index', ['rows' => $rows, 'show' => $show, 'counts' => $counts]);
    }

    /** CSV of active subscribers, for your email tool. Each row has its personal unsubscribe link. */
    public function export(): StreamedResponse
    {
        $name = 'jflsolar-subscribers-'.now('Asia/Manila')->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // so Excel opens accents correctly
            fputcsv($out, ['email', 'name', 'joined', 'source', 'unsubscribe_link']);
            if (Newsletter::hasTables()) {
                DB::table('newsletter_subscribers')->whereNull('unsubscribed_at')->orderBy('id')->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        // Leading = + - @ would run as a formula in Excel: prefix with a quote.
                        $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;
                        fputcsv($out, [$safe($r->email), $safe($r->name), \Illuminate\Support\Carbon::parse($r->subscribed_at)->setTimezone('Asia/Manila')->format('Y-m-d'), Newsletter::SOURCES[$r->source] ?? $r->source, Newsletter::unsubscribeUrl($r->token)]);
                    }
                });
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
