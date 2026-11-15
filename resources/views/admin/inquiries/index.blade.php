@php
    use App\Support\Labels;
    $tabs = ['new' => 'New', 'handled' => 'Handled', 'archived' => 'Archived'];
    $tab = 'inline-flex min-h-11 items-center gap-1.5 rounded-full border border-steel-300 px-4 text-sm font-semibold text-steel-800 aria-[current=page]:border-steel-900 aria-[current=page]:bg-steel-900 aria-[current=page]:text-white';
@endphp
<x-admin.layout title="Inquiries">
    <x-admin.page-title title="Inquiries" intro="Quote requests sent through the website, and emails to info@jflsolar.ph. Reply by phone or email, then mark them as handled." />
    <details class="mb-4 rounded-[var(--radius-panel)] border border-steel-200 bg-white px-4 py-3" @unless ($emailLinked) open @endunless>
        <summary class="inline-flex min-h-11 cursor-pointer items-center gap-2 font-semibold text-steel-900">
            Emails to info@jflsolar.ph:
            @if ($emailLinked)<span class="text-ok-700">linked</span>@else<span class="text-danger-700">not linked yet</span>@endif
        </summary>
        <div class="mt-2 space-y-3 text-sm text-steel-700">
            <p>Each email to info@jflsolar.ph (and sales@) is added here as a new inquiry, with the sender, subject and the start of the message. The full email stays in the mailbox.</p>
            <ol class="list-decimal space-y-1 pl-5">
                <li>In Hostinger: Emails → jflsolar.ph → Agentic mail → Webhooks → <strong>Add webhook</strong>. Mailbox: info@jflsolar.ph. URL: <code class="rounded bg-steel-100 px-1">{{ \App\Support\Site::url('/webhooks/hostinger-mail') }}</code></li>
                <li>Copy the <strong>secret</strong> Hostinger shows (it is shown only once) and paste it below.</li>
                <li>Click <strong>Test</strong> next to the webhook in Hostinger, or send an email to info@jflsolar.ph.</li>
            </ol>
            <form method="post" action="/admin/inquiries/email-link" class="flex flex-wrap items-end gap-3">
                @csrf
                <label class="grid gap-1 font-semibold text-steel-900">Webhook secret
                    <input type="password" name="secret" autocomplete="off" spellcheck="false" style="width: 22rem" class="min-h-11 max-w-full rounded-[var(--radius-control)] border border-steel-500 px-3 font-normal">
                </label>
                <button type="submit" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-steel-900 px-4 font-semibold text-white">Save secret</button>
                @if ($emailLinked)
                    <button type="submit" name="clear" value="1" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border border-steel-400 px-4 font-semibold text-steel-900">Remove link</button>
                @endif
            </form>
        </div>
    </details>
    <nav aria-label="Filter inquiries" class="mb-4 flex flex-wrap gap-2">
        @foreach ($tabs as $key => $label)
            <a href="/admin/inquiries{{ $key === 'new' ? '' : '?view='.$key }}" @if ($view === $key) aria-current="page" @endif class="{{ $tab }}">{{ $label }} <span class="tabular">({{ $counts[$key] ?? 0 }})</span></a>
        @endforeach
    </nav>
    @if ($inquiries->isEmpty())
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">{{ $view === 'new' ? 'No new inquiries.' : 'Nothing here.' }}</p>
    @else
        @php $total = $counts[$view] ?? $inquiries->total(); @endphp
        <div class="mb-3 flex flex-wrap items-center gap-3 rounded-[var(--radius-panel)] border border-steel-200 bg-white px-4 py-3">
            <label class="inline-flex min-h-11 items-center gap-2 font-semibold text-steel-900">
                <input type="checkbox" id="inq-all" class="size-5"> Select all on this page
            </label>
            <span id="inq-count" class="text-sm text-steel-600" aria-live="polite">0 selected</span>
            <span class="ml-auto flex flex-wrap gap-2">
                <button type="button" data-open-dialog="dlg-inq-selected" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border-2 border-danger-700 px-4 text-sm font-semibold text-danger-700 hover:bg-danger-50">Delete selected</button>
                <x-admin.confirm action="/admin/inquiries/delete" :title="'Delete all '.$total.' '.strtolower($tabs[$view]).' inquiries?'" confirm-label="Delete all permanently" :label="'Delete all '.strtolower($tabs[$view]).' ('.$total.')'" :fields="['all' => $view, 'back' => $view]">
                    <p>Every inquiry in the {{ $tabs[$view] }} list, with the customers’ contact details and messages, is removed for good. This can’t be undone.</p>
                </x-admin.confirm>
            </span>
        </div>
        <dialog id="dlg-inq-selected" aria-labelledby="dlg-inq-selected-title" class="m-auto w-[min(28rem,calc(100vw-2rem))] rounded-[var(--radius-panel)] p-0 shadow-2xl">
            <form id="inq-bulk" method="post" action="/admin/inquiries/delete" class="p-6">
                @csrf
                <input type="hidden" name="back" value="{{ $view }}">
                <h2 id="dlg-inq-selected-title" class="text-xl font-bold text-steel-950">Delete the selected inquiries?</h2>
                <p class="mt-2 text-steel-700"><span id="inq-count-dlg">0 inquiries</span> will be removed for good, with the customers’ contact details and messages. This can’t be undone.</p>
                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" data-close-dialog autofocus class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] border-2 border-steel-900 px-4 font-semibold text-steel-900 hover:bg-steel-50">Cancel</button>
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-[var(--radius-control)] bg-danger-700 px-4 font-semibold text-white hover:bg-[#93190f]">Delete permanently</button>
                </div>
            </form>
        </dialog>
        <ul class="divide-y divide-steel-200 rounded-[var(--radius-panel)] border border-steel-200 bg-white">
            @foreach ($inquiries as $i)
                <li class="flex gap-3 p-4">
                    <input type="checkbox" name="ids[]" value="{{ $i->id }}" form="inq-bulk" data-inq-pick class="mt-1.5 size-5 shrink-0" aria-label="Select inquiry from {{ $i->name }}">
                    <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <a href="/admin/inquiries/{{ $i->id }}" class="text-lg font-bold text-steel-950 underline-offset-2 hover:underline">{{ $i->name }}</a>
                        <span class="text-sm text-steel-600 tabular">{{ $i->created_at->timezone('Asia/Manila')->format('j M Y, g:i A') }}</span>
                    </div>
                    <p class="text-sm text-steel-700">{{ Labels::inquiryType($i->inquiry_type) }}@if ($i->item_label) · {{ $i->item_label }}@endif @if ($i->location) · {{ $i->location }}@endif</p>
                    <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                        <p class="line-clamp-2 min-w-0 flex-1 text-steel-700">{{ $i->message }}</p>
                        <x-admin.confirm :action="'/admin/inquiries/'.$i->id.'/delete'" title="Delete this inquiry permanently?" confirm-label="Delete permanently" label="Delete" :sr-label="'inquiry from '.$i->name" :fields="['back' => $view]">
                            <p>The inquiry from {{ $i->name }}, with their contact details and message, is removed for good. This can’t be undone.</p>
                        </x-admin.confirm>
                    </div>
                    </div>
                </li>
            @endforeach
        </ul>
        <script>
            (function () {
                var all = document.getElementById('inq-all'), picks = [].slice.call(document.querySelectorAll('[data-inq-pick]'));
                function update() {
                    var n = picks.filter(function (p) { return p.checked; }).length;
                    document.getElementById('inq-count').textContent = n + ' selected';
                    document.getElementById('inq-count-dlg').textContent = n === 1 ? '1 inquiry' : n + ' inquiries';
                    all.checked = n > 0 && n === picks.length;
                    all.indeterminate = n > 0 && n < picks.length;
                }
                all.addEventListener('change', function () { picks.forEach(function (p) { p.checked = all.checked; }); update(); });
                picks.forEach(function (p) { p.addEventListener('change', update); });
                update();
            })();
        </script>
        <div class="mt-4">{{ $inquiries->links() }}</div>
    @endif
</x-admin.layout>
