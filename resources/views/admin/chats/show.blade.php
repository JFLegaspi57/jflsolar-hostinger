@php
    use Illuminate\Support\Carbon;
    $at = fn ($t) => Carbon::parse($t)->timezone('Asia/Manila')->format('j F Y, g:i A');
    // Buckny writes light Markdown: show links and bold, everything else as plain text.
    $md = function (string $text): string {
        $html = e($text);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);

        return preg_replace_callback('/\[([^\]]+)\]\(((?:\/|https?:\/\/)[^)\s]*)\)/', fn ($m) => '<a href="'.$m[2].'" target="_blank" rel="noopener" class="underline">'.$m[1].'</a>', $html);
    };
@endphp
<x-admin.layout title="Buckny chat">
    <x-admin.page-title title="Buckny chat" back="/admin/chats" back-label="All chats"
        :intro="'Started '.$at($chat->created_at).' · last message '.$at($chat->last_at)" />

    <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">
        <section aria-label="Conversation" class="space-y-3 rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4 sm:p-6">
            @foreach ($messages as $m)
                @if ($m['role'] === 'user')
                    <div class="ml-auto rounded-[var(--radius-panel)] bg-steel-900 px-4 py-3 text-white" style="max-width: 85%; width: fit-content">
                        <p class="text-xs font-semibold text-steel-200">Visitor</p>
                        <p class="whitespace-pre-line break-words">{{ $m['content'] }}</p>
                    </div>
                @else
                    <div class="mr-auto rounded-[var(--radius-panel)] border border-steel-200 bg-steel-50 px-4 py-3 text-steel-900" style="max-width: 85%; width: fit-content">
                        <p class="text-xs font-semibold text-steel-600">Buckny</p>
                        <p class="whitespace-pre-line break-words">{!! $md($m['content']) !!}</p>
                    </div>
                @endif
            @endforeach
        </section>

        <aside class="space-y-4">
            <div class="rounded-[var(--radius-panel)] border border-steel-200 bg-white p-4">
                <h2 class="font-bold text-steel-950">Details</h2>
                <dl class="mt-2 grid gap-x-4 gap-y-1 text-sm" style="grid-template-columns: max-content 1fr">
                    <dt class="font-semibold">Questions</dt><dd>{{ $chat->questions }}</dd>
                    <dt class="font-semibold">Language</dt><dd>{{ $chat->lang === 'fil' ? 'Filipino' : 'English' }}</dd>
                    <dt class="font-semibold">Device</dt><dd>{{ $chat->device ?: '—' }}</dd>
                    <dt class="font-semibold">Started on</dt><dd class="break-words">@if ($chat->page)<a href="{{ $chat->page }}" target="_blank" rel="noopener" class="underline">{{ $chat->page }}</a>@else — @endif</dd>
                    <dt class="font-semibold">Callback</dt><dd>@if ($chat->callback)<a href="/admin/inquiries" class="font-semibold text-ok-700 underline">Yes — see Inquiries</a>@else No @endif</dd>
                </dl>
                @if ($others)
                    <a href="/admin/chats?visitor={{ $chat->visitor }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold underline">{{ $others }} other {{ $others === 1 ? 'chat' : 'chats' }} from this visitor</a>
                @endif
            </div>
            <x-admin.confirm :action="'/admin/chats/'.$chat->id.'/delete'" title="Delete this chat permanently?" confirm-label="Delete permanently" label="Delete chat">
                <p>The whole conversation is removed for good. This can’t be undone.</p>
            </x-admin.confirm>
        </aside>
    </div>
</x-admin.layout>
