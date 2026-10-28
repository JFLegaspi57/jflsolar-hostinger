@php
    use App\Support\Labels;
    $i = $inquiry;
    $btn = 'inline-flex min-h-11 items-center rounded-[var(--radius-control)] px-4 text-sm font-semibold';
    $digits = $i->phone ? preg_replace('/[^0-9+]/', '', $i->phone) : null;
@endphp
<x-admin.layout :title="'Inquiry from '.$i->name">
    <x-admin.page-title :title="$i->name" back="/admin/inquiries" back-label="All inquiries" :intro="'Received '.$i->created_at->timezone('Asia/Manila')->format('j F Y, g:i A').' · '.ucfirst($i->status)" />

    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        <section aria-label="Inquiry" class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-6">
            <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-[max-content_1fr]">
                <dt class="font-semibold">Phone</dt><dd>@if ($i->phone)<a href="tel:{{ $digits }}" class="underline tabular">{{ $i->phone }}</a>@else — @endif</dd>
                <dt class="font-semibold">Email</dt><dd>@if ($i->email)<a href="mailto:{{ $i->email }}" class="underline break-all">{{ $i->email }}</a>@else — @endif</dd>
                <dt class="font-semibold">About</dt><dd>{{ Labels::INQUIRY_TYPES[$i->inquiry_type] ?? $i->inquiry_type }}</dd>
                @if ($i->item_label)<dt class="font-semibold">Selected item</dt><dd>{{ $i->item_label }}</dd>@endif
                <dt class="font-semibold">Location</dt><dd>{{ $i->location ?: '—' }}</dd>
                @if ($i->bill_range)<dt class="font-semibold">Monthly bill</dt><dd>{{ Labels::BILL_RANGES[$i->bill_range] ?? $i->bill_range }}</dd>@endif
                <dt class="font-semibold">Email alert</dt><dd>{{ $i->emailed_at ? 'Sent '.$i->emailed_at->timezone('Asia/Manila')->format('j M Y, g:i A') : 'Not sent' }}</dd>
            </dl>
            <h2 class="mt-6 text-lg font-bold text-steel-950">Message</h2>
            <p class="mt-2 whitespace-pre-line text-steel-800">{{ $i->message }}</p>
        </section>

        <aside class="space-y-4">
            <div class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <h2 class="font-bold text-steel-950">Status</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (['handled' => 'Mark handled', 'new' => 'Mark as new', 'archived' => 'Archive'] as $status => $label)
                        @continue($i->status === $status)
                        <form method="post" action="/admin/inquiries/{{ $i->id }}">@csrf<input type="hidden" name="status" value="{{ $status }}">
                            <button type="submit" class="{{ $btn }} {{ $status === 'handled' ? 'bg-steel-900 text-white hover:bg-steel-800' : 'border border-steel-300 bg-white text-steel-900 hover:border-steel-900' }}">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
            <form method="post" action="/admin/inquiries/{{ $i->id }}" class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                @csrf
                <label for="notes" class="font-bold text-steel-950">Staff notes</label>
                <p id="notes-hint" class="text-sm text-steel-600">For your team only — e.g. who replied and when.</p>
                <textarea id="notes" name="staff_notes" rows="5" maxlength="4000" aria-describedby="notes-hint" class="mt-2 w-full rounded-[var(--radius-control)] border border-steel-500 px-3 py-2">{{ old('staff_notes', $i->staff_notes) }}</textarea>
                <button type="submit" class="{{ $btn }} mt-2 border border-steel-300 bg-white text-steel-900 hover:border-steel-900">Save notes</button>
            </form>
            @if ($i->status === 'archived')
                <x-admin.confirm :action="'/admin/inquiries/'.$i->id.'/delete'" title="Delete this inquiry permanently?" confirm-label="Delete permanently" label="Delete permanently">
                    <p>The customer's details and message are removed. Do this when you no longer need them (for example, after the quotation is closed).</p>
                </x-admin.confirm>
            @endif
        </aside>
    </div>
</x-admin.layout>
