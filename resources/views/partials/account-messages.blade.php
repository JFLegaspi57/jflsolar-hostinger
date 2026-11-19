{{-- Personal messages from JFL Solar to this member (Admin → Members). Shown until the member closes them. --}}
@foreach (\App\Support\MemberMessages::open($member->id) as $msg)
    <section aria-labelledby="msg-{{ $msg->id }}-h" class="mt-6 rounded-[var(--radius-panel)] border p-5 sm:p-6" style="border-color:#2563eb;background:#eff6ff">
        <div class="flex items-start justify-between gap-3">
            <h2 id="msg-{{ $msg->id }}-h" class="text-lg font-bold text-steel-950">✉️ {{ $msg->title }}</h2>
            <form method="post" action="/account/messages/{{ $msg->id }}/close" class="shrink-0">@csrf<button type="submit" class="text-sm font-semibold underline text-steel-800" aria-label="Close this message">Close</button></form>
        </div>
        <div class="mt-2 text-steel-800"><x-paragraphs :text="$msg->body" /></div>
        <p class="mt-3 text-sm text-steel-600">{{ \Illuminate\Support\Carbon::parse($msg->created_at)->timezone('Asia/Manila')->format('j M Y') }}</p>
    </section>
@endforeach
