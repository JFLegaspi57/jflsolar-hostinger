@php
    use App\Support\Labels;
    use Illuminate\Support\Carbon;
    $isOwner = auth('web')->user()?->isOwner();
    $btn = 'inline-flex min-h-11 items-center rounded-[var(--radius-control)] px-4 font-semibold';
    $label = fn ($s) => Labels::AVAILABILITY[$s] ?? $s;
    $qty = fn ($q) => rtrim(rtrim(number_format((float) $q, 2), '0'), '.');
@endphp
<x-admin.layout title="Inventory sync">
    <x-admin.page-title title="Inventory sync" intro="Website stock follows the JFL Solar Inventory List in Google Sheets. On hand above 0 → “Available to order”; 0 or less → “Ask about availability” (pre-order items stay on pre-order). Only item names and quantities are sent, never prices." />

    <section class="mb-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
        <h2 class="text-lg font-bold text-steel-950">Google Sheet link:
            @if ($configured)<span class="text-ok-700">linked</span>@else<span class="text-danger-700">not linked yet</span>@endif
        </h2>
        @if ($script)
            <ol class="mt-3 list-decimal space-y-1 pl-5 text-steel-800">
                <li>Open the <strong>JFL Solar Inventory List</strong> sheet → <strong>Extensions → Apps Script</strong>.</li>
                <li>Delete what is in the editor, paste the script below, and click <strong>Save</strong> (disk icon).</li>
                <li>Choose <strong>setup</strong> in the function list at the top, click <strong>Run</strong>, then <strong>Review permissions → Allow</strong> with your Google account.</li>
                <li>Come back here and reload: the first sync appears below. After that it syncs every hour and shortly after you edit the sheet. The sheet also gets a <strong>JFL Solar → Sync website stock now</strong> menu.</li>
            </ol>
            <p class="mt-3 font-semibold text-danger-700">The script contains the sync key and is shown only this once. Don’t share it.</p>
            <textarea id="inv-script" readonly rows="12" class="mt-2 w-full rounded-[var(--radius-control)] border border-steel-500 p-3 font-mono text-sm" spellcheck="false">{{ $script }}</textarea>
            <button type="button" class="{{ $btn }} mt-2 bg-steel-900 text-white" onclick="navigator.clipboard.writeText(document.getElementById('inv-script').value).then(() => { this.textContent = 'Copied'; })">Copy script</button>
        @elseif ($isOwner)
            <p class="mt-2 text-steel-700">{{ $configured ? 'To set up the sheet again (e.g. after removing the script), make a new link: the old one stops working.' : 'Make a link, then paste the script it gives you into the Google Sheet.' }}</p>
        @else
            <p class="mt-2 text-steel-700">An owner can set this up.</p>
        @endif
        @if ($isOwner && ! $script)
            <div class="mt-3 flex flex-wrap gap-2">
                <form method="post" action="/admin/inventory/connect">@csrf<button type="submit" class="{{ $btn }} bg-steel-900 text-white">{{ $configured ? 'Make a new link' : 'Make sync link' }}</button></form>
                @if ($configured)
                    <form method="post" action="/admin/inventory/disconnect">@csrf<button type="submit" class="{{ $btn }} border border-steel-400 text-steel-900">Remove link</button></form>
                @endif
            </div>
        @endif
    </section>

    @if (! $last)
        <p class="rounded-[var(--radius-panel)] border border-dashed border-steel-300 bg-white p-8 text-center text-steel-700">No sync yet.</p>
    @else
        <section class="mb-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
            <h2 class="text-lg font-bold text-steel-950">Last sync</h2>
            <p class="mt-1 text-steel-700">{{ Carbon::parse($last['at'])->timezone('Asia/Manila')->format('j M Y, g:i A') }} from tab “{{ $last['sheet'] }}” · {{ $last['rows'] }} sheet items · {{ count($last['linked']) }} website products updated from the sheet.</p>
            <h3 class="mt-4 font-bold text-steel-950">Changed this time ({{ count($last['changed']) }})</h3>
            @if ($last['changed'])
                <ul class="mt-1 space-y-1 text-steel-800">
                    @foreach ($last['changed'] as $c)
                        <li><a href="/products/{{ $c['slug'] }}" class="underline" target="_blank" rel="noopener">{{ trim(($c['brand'] ?? '').' '.$c['name']) }}</a>: {{ $label($c['from']) }} → <strong>{{ $label($c['to']) }}</strong> ({{ $qty($c['qty']) }} on hand)</li>
                    @endforeach
                </ul>
            @else
                <p class="mt-1 text-steel-700">Nothing changed.</p>
            @endif
        </section>

        @if ($last['unmatched'])
            <section class="mb-6 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <h2 class="text-lg font-bold text-steel-950">Sheet items not linked to a website product ({{ count($last['unmatched']) }})</h2>
                <p class="mt-1 text-sm text-steel-700">Link an item to its website product, or mark it “Not on the website” to hide it from this list. Takes effect at the next sync.</p>
                <ul class="mt-3 divide-y divide-steel-200">
                    @foreach ($last['unmatched'] as $u)
                        <li class="flex flex-wrap items-center gap-3 py-2">
                            <span class="min-w-0 flex-1 text-steel-900">{{ $u['name'] }} <span class="text-sm text-steel-600 tabular">({{ $qty($u['qty']) }} on hand)</span></span>
                            <form method="post" action="/admin/inventory/link" class="flex flex-wrap gap-2">
                                @csrf
                                <input type="hidden" name="name" value="{{ $u['name'] }}">
                                <label class="sr-only" for="link-{{ $loop->index }}">Website product for {{ $u['name'] }}</label>
                                <select id="link-{{ $loop->index }}" name="slug" class="min-h-11 rounded-[var(--radius-control)] border border-steel-500 px-2" style="max-width: 18rem">
                                    <option value="-">Not on the website</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->slug }}">{{ trim($p->brand.' '.$p->name) }}{{ $p->model ? ' ('.$p->model.')' : '' }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="{{ $btn }} border border-steel-400 text-steel-900">Save</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <details class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
            <summary class="inline-flex min-h-11 cursor-pointer items-center font-bold text-steel-950">Website products updated from the sheet ({{ count($last['linked']) }})</summary>
            <ul class="mt-2 divide-y divide-steel-200 text-steel-800">
                @foreach ($last['linked'] as $l)
                    <li class="flex flex-wrap justify-between gap-2 py-1.5"><a href="/products/{{ $l['slug'] }}" class="underline" target="_blank" rel="noopener">{{ trim(($l['brand'] ?? '').' '.$l['name']) }}</a><span class="tabular">{{ $qty($l['qty']) }} on hand · {{ $label($l['status']) }}</span></li>
                @endforeach
            </ul>
            <p class="mt-2 text-sm text-steel-600">Products not in the sheet (e.g. Trina, Sungrow, GoodWe Lynx) keep the availability set in Products.</p>
        </details>
    @endif
</x-admin.layout>
