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

        return view('admin.members.index', ['members' => $members, 'q' => $q]);
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
