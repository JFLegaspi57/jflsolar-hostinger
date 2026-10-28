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

        return view('admin.inquiries.index', ['inquiries' => $inquiries, 'view' => $view, 'counts' => $counts]);
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

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        $inquiry->delete();

        return redirect('/admin/inquiries?view=archived')->with('status', 'Inquiry permanently deleted.');
    }
}
