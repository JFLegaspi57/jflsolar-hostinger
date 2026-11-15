<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Quote requests from the website's contact form. */
class InquiryController extends Controller
{
    public function index(Request $request): View
    {
        $view = in_array($request->query('view'), ['handled', 'archived'], true) ? $request->query('view') : 'new';
        $inquiries = Inquiry::query()->where('status', $view)->latest('id')->paginate(30)->withQueryString();
        $counts = Inquiry::query()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return view('admin.inquiries.index', ['inquiries' => $inquiries, 'view' => $view, 'counts' => $counts, 'emailLinked' => \App\Support\InboundMail::configured()]);
    }

    /** Save (or clear) the Hostinger mail webhook secret that lets emails to info@ arrive here. */
    public function emailLink(Request $request): RedirectResponse
    {
        if ($request->boolean('clear')) {
            \App\Support\InboundMail::forgetSecret();

            return redirect('/admin/inquiries')->with('status', 'Email link removed. Emails to info@jflsolar.ph no longer arrive here.');
        }
        $secret = trim((string) $request->input('secret'));
        if (! preg_match('/^\S{16,200}$/', $secret)) {
            return redirect('/admin/inquiries')->with('status_error', 'Paste the webhook secret from Hostinger (one long code, no spaces).');
        }
        \App\Support\InboundMail::setSecret($secret);

        return redirect('/admin/inquiries')->with('status', 'Email link saved. Use Test in Hostinger, or send an email to info@jflsolar.ph, to check it.');
    }

    public function show(Inquiry $inquiry): View
    {
        return view('admin.inquiries.show', ['inquiry' => $inquiry]);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $data = [];
        if (in_array($request->input('status'), ['new', 'handled', 'archived'], true)) {
            $data['status'] = $request->input('status');
        }
        if ($request->has('staff_notes')) {
            $notes = trim((string) $request->input('staff_notes'));
            if (mb_strlen($notes) > 4000) {
                return back()->withInput()->with('status_error', 'Notes must be 4,000 characters or fewer.');
            }
            $data['staff_notes'] = $notes ?: null;
        }
        $inquiry->forceFill($data)->save();

        $messages = ['new' => 'Marked as new.', 'handled' => 'Marked as handled.', 'archived' => 'Archived.'];

        return redirect('/admin/inquiries/'.$inquiry->id)->with('status', $messages[$data['status'] ?? ''] ?? 'Notes saved.');
    }

    /**
     * Permanently deletes several inquiries at once: the ticked ones (ids[]), or every
     * inquiry in one list (all=new|handled|archived). Both go through a confirmation dialog.
     */
    public function destroyMany(Request $request): RedirectResponse
    {
        $statuses = ['new', 'handled', 'archived'];
        $back = in_array($request->input('back'), $statuses, true) ? $request->input('back') : 'new';
        $url = '/admin/inquiries'.($back === 'new' ? '' : '?view='.$back);

        if (in_array($request->input('all'), $statuses, true)) {
            $count = Inquiry::query()->where('status', $request->input('all'))->delete();
        } else {
            $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('ids', [])))));
            if ($ids === []) {
                return redirect($url)->with('status_error', 'Tick at least one inquiry to delete.');
            }
            $count = Inquiry::query()->whereIn('id', array_slice($ids, 0, 500))->delete();
        }

        return redirect($url)->with('status', $count === 1 ? '1 inquiry permanently deleted.' : $count.' inquiries permanently deleted.');
    }

    /** Permanently deletes an inquiry from any list (new, handled or archived), after a confirmation dialog. */
    public function destroy(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $name = $inquiry->name;
        $view = in_array($request->input('back'), ['new', 'handled', 'archived'], true) ? $request->input('back') : $inquiry->status;
        $inquiry->delete();

        return redirect('/admin/inquiries'.($view === 'new' ? '' : '?view='.$view))->with('status', 'Inquiry from '.$name.' permanently deleted.');
    }
}
