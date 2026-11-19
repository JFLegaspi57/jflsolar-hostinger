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

        $counts = Members::hasTables() ? [
            'total' => Member::query()->count(),
            'active' => Member::query()->where('status', 'active')->count(),
            'new30' => Member::query()->where('created_at', '>=', now()->subDays(30))->count(),
        ] : ['total' => 0, 'active' => 0, 'new30' => 0];

        $online = Members::online();
        $courseIds = array_values(array_unique(array_merge($members ? $members->pluck('id')->all() : [], $online->pluck('id')->all())));

        return view('admin.members.index', ['members' => $members, 'q' => $q, 'counts' => $counts, 'logins' => $members ? Members::lastLogins($members->pluck('id')->all()) : [], 'online' => $online,
            'course' => \App\Support\CourseResults::progressFor($courseIds)]);
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
            'courseRecord' => \App\Support\CourseResults::record($m->id, true),
            'messages' => \App\Support\MemberMessages::all($m->id),
        ]);
    }

    /** A personal message shown on this member's account page until they close it. */
    public function addMessage(Request $request, int $member): RedirectResponse
    {
        $m = Member::query()->findOrFail($member);
        $data = $request->validate(['title' => ['required', 'string', 'max:200'], 'body' => ['required', 'string', 'max:10000']]);
        \App\Support\MemberMessages::add($m->id, $data['title'], $data['body']);

        return redirect('/admin/members/'.$m->id.'#messages')->with('status', 'Message posted on '.$m->name.'’s account page.');
    }

    /** Issue or remove the member's certificate of completion number (CTN). */
    public function certificate(Request $request, int $member): RedirectResponse
    {
        $m = Member::query()->findOrFail($member);
        $back = redirect('/admin/members/'.$m->id.'#course-h');
        switch ($request->input('action')) {
            case 'revoke':
                \App\Support\CourseCertificates::revoke($m->id);

                return $back->with('status', 'Certificate number removed.');
            case 'paid':
            case 'unpaid':
                \App\Support\CourseCertificates::setPaid($m->id, $request->input('action') === 'paid');

                return $back->with('status', $request->input('action') === 'paid' ? 'Marked as paid: '.$m->name.' can now download the certificate.' : 'Marked as not paid: the download is locked.');
            case 'reject':
                \App\Support\CourseCertificates::rejectReference($m->id);

                return $back->with('status', 'Reference rejected. '.$m->name.' will see a warning the next time they open their account.');
            case 'reset-wrong':
                \App\Support\CourseCertificates::resetWrong($m->id);

                return $back->with('status', 'Wrong reference count reset to 0.');
            case 'upload':
                $error = \App\Support\CourseCertificates::saveFiles($m->id, $request->file('image'), $request->file('pdf'));

                return $error ? $back->with('status_error', $error) : $back->with('status', 'Certificate uploaded.');
        }
        $data = $request->validate(['completed_on' => ['nullable', 'date']]);
        $cert = \App\Support\CourseCertificates::issue($m->id, $data['completed_on'] ?? null);

        return redirect('/admin/members/'.$m->id.'#course-h')->with('status', 'Certificate number '.$cert->ctn.' issued to '.$m->name.'.');
    }

    public function certificateThumb(int $member): \Illuminate\Http\Response
    {
        $cert = \App\Support\CourseCertificates::for($member);
        $jpg = $cert ? \App\Support\CourseCertificates::thumbnail($cert) : null;
        abort_unless($jpg, 404);

        return response($jpg, 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'private, max-age=600']);
    }

    public function deleteMessage(int $member, int $id): RedirectResponse
    {
        \App\Support\MemberMessages::delete($member, $id);

        return redirect('/admin/members/'.$member.'#messages')->with('status', 'Message removed.');
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
            ? [
                'active' => DB::table('newsletter_subscribers')->whereNull('unsubscribed_at')->count(),
                'left' => DB::table('newsletter_subscribers')->whereNotNull('unsubscribed_at')->count(),
                'new30' => DB::table('newsletter_subscribers')->whereNull('unsubscribed_at')->where('subscribed_at', '>=', now()->subDays(30))->count(),
            ]
            : ['active' => 0, 'left' => 0, 'new30' => 0];

        return view('admin.subscribers.index', ['rows' => $rows, 'show' => $show, 'counts' => $counts,
            'welcome' => ['on' => \App\Support\WelcomeEmail::enabled(), 'ready' => \App\Support\WelcomeEmail::ready(), 'from' => \App\Support\WelcomeEmail::fromAddress(), 'gmail' => \App\Support\WelcomeEmail::viaGmail(), 'pending' => \App\Support\WelcomeEmail::pendingCount(), 'sent' => \App\Support\WelcomeEmail::sentSinceCount(), 'error' => \App\Support\WelcomeEmail::lastError(), 'errorAt' => \App\Support\WelcomeEmail::lastErrorAt(), 'check' => auth('web')->user()?->isOwner() ? \App\Support\WelcomeEmail::settingsCheck() : null]]);
    }

    /** Sends the welcome email to members who haven't received it yet. */
    public function sendWelcome(): \Illuminate\Http\RedirectResponse
    {
        $sent = \App\Support\WelcomeEmail::sendPending();
        $left = \App\Support\WelcomeEmail::pendingCount();

        return redirect('/admin/subscribers')->with('status', 'Welcome email sent to '.$sent.' '.($sent === 1 ? 'member' : 'members').'.'.($left ? ' '.$left.' still waiting: send again.' : ''));
    }

    /** Owner only: the Google and Facebook app keys for "Continue with Google / Facebook". */
    public function socialLogin(): \Illuminate\View\View
    {
        abort_unless(auth('web')->user()?->isOwner(), 403);
        $info = [];
        foreach (\App\Support\SocialLogin::PROVIDERS as $p => $name) {
            [$id, $secret] = \App\Support\SocialLogin::keys($p);
            $info[$p] = ['name' => $name, 'id' => $id, 'has_secret' => $secret !== '', 'on' => \App\Support\SocialLogin::enabled($p), 'callback' => \App\Support\SocialLogin::callback($p)];
        }

        return view('admin.members.social-login', ['info' => $info]);
    }

    public function saveSocialLogin(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless(auth('web')->user()?->isOwner(), 403);
        $p = (string) $request->input('provider');
        abort_unless(isset(\App\Support\SocialLogin::PROVIDERS[$p]), 404);
        $id = trim((string) $request->input('app_id', ''));
        $secret = trim((string) $request->input('app_secret', ''));
        if (mb_strlen($id) > 200 || mb_strlen($secret) > 200 || ($id !== '' && ! preg_match('/^[A-Za-z0-9._-]+$/', $id))) {
            return redirect('/admin/social-login')->with('status_error', 'That app ID does not look right. Copy it again from the developer page.');
        }
        \App\Support\SocialLogin::save($p, $id, $secret);
        $name = \App\Support\SocialLogin::PROVIDERS[$p];

        return redirect('/admin/social-login')->with('status', \App\Support\SocialLogin::enabled($p)
            ? $name.' sign-in is on: the button now shows on the sign-up and sign-in pages.'
            : ($id === '' ? $name.' sign-in is off.' : $name.' app ID saved. Add the app secret to turn it on.'));
    }

    /** Owner only: turns the automatic welcome email on or off. */
    public function welcomeSwitch(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless(auth('web')->user()?->isOwner(), 403);
        $on = $request->input('on') === '1';
        \App\Support\WelcomeEmail::setEnabled($on);

        return redirect('/admin/subscribers')->with('status', $on ? 'The automatic welcome email is on: new members get it right after signing up.' : 'The automatic welcome email is off: nobody gets it.');
    }

    /** Owner only: saves the Gmail address + app password the welcome email is sent from, then tests the login. */
    public function gmailSender(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless(auth('web')->user()?->isOwner(), 403);
        if ($request->input('stop') === '1') {
            \App\Support\GmailSender::forget();

            return redirect('/admin/subscribers')->with('status', 'Gmail is no longer used: the welcome email is sent from the website mailbox.');
        }
        $user = mb_strtolower(trim((string) $request->input('gmail', '')));
        $pass = (string) $request->input('app_password', '');
        if (! filter_var($user, FILTER_VALIDATE_EMAIL) || trim($pass) === '' || mb_strlen($pass) > 100) {
            return redirect('/admin/subscribers')->with('status_error', 'Type the Gmail address and its 16-letter app password.');
        }
        \App\Support\GmailSender::set($user, $pass);
        $r = \App\Support\WelcomeEmail::testLogin();

        return redirect('/admin/subscribers')->with($r['ok'] ? 'status' : 'status_error', $r['ok']
            ? 'Gmail saved, and the login works: the welcome email is now sent from '.$user.'.'
            : 'Gmail saved, but Google refused the login. Check the address, and use an app password (not your normal Gmail password). ('.$r['message'].')');
    }

    /** Owner only: signs in to the mail server without sending anything. */
    public function testMail(): \Illuminate\Http\RedirectResponse
    {
        abort_unless(auth('web')->user()?->isOwner(), 403);
        $r = \App\Support\WelcomeEmail::testLogin();

        return redirect('/admin/subscribers')->with($r['ok'] ? 'status' : 'status_error', 'Mail login test: '.$r['message']);
    }

    /** Owner only: saves the website mailbox password (encrypted), then tests the login without sending anything. */
    public function mailPassword(Request $request): \Illuminate\Http\RedirectResponse
    {
        abort_unless(auth('web')->user()?->isOwner(), 403);
        $pw = (string) $request->input('password', '');
        if ($pw === '' || mb_strlen($pw) > 200) {
            return redirect('/admin/subscribers')->with('status_error', 'Type the mailbox password first.');
        }
        \App\Support\MailPassword::set($pw);
        $r = \App\Support\WelcomeEmail::testLogin();

        return redirect('/admin/subscribers')->with($r['ok'] ? 'status' : 'status_error', $r['ok']
            ? 'Mailbox password saved, and the login works. The welcome email can be sent now.'
            : 'Mailbox password saved, but the mail server still refuses it: check it is the same password as info@jflsolar.ph in Hostinger → Emails. ('.$r['message'].')');
    }

    /** Owner only: marks the welcome email as already received for these addresses (it was sent to them by hand, e.g. from Gmail). */
    public function markWelcome(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth('web')->user()?->isOwner(), 403);
        $emails = collect((array) $request->input('emails', []))->take(5000)
            ->map(fn ($e) => mb_strtolower(trim((string) $e)))->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))->unique()->values();
        \App\Support\WelcomeEmail::ensureColumn();
        $marked = 0;
        foreach ($emails->chunk(500) as $chunk) {
            $marked += \App\Models\Member::query()->whereNull('welcome_sent_at')->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(email)'), $chunk->all())->update(['welcome_sent_at' => now()]);
        }

        return response()->json(['marked' => $marked, 'pending' => \App\Support\WelcomeEmail::pendingCount()]);
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
