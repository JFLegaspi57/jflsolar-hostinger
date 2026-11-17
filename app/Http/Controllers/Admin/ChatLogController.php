<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ChatLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Admin → Buckny chats: read the chat assistant's conversations with visitors. */
class ChatLogController extends Controller
{
    public function index(Request $request): View
    {
        $view = $request->query('view') === 'callback' ? 'callback' : 'all';
        $search = trim(mb_substr((string) $request->query('q', ''), 0, 80));
        $visitor = preg_match('/^[0-9a-f]{16}$/', (string) $request->query('visitor')) ? (string) $request->query('visitor') : null;

        $query = ChatLog::query()->orderByDesc('last_at');
        if ($view === 'callback') {
            $query->where('callback', true);
        }
        if ($visitor) {
            $query->where('visitor', $visitor);
        }
        if ($search !== '') {
            $query->where('messages', 'like', '%'.addcslashes($search, '%_\\').'%');
        }

        return view('admin.chats.index', [
            'chats' => $query->paginate(30)->withQueryString(),
            'view' => $view,
            'search' => $search,
            'visitor' => $visitor,
            'counts' => ['all' => ChatLog::query()->count(), 'callback' => ChatLog::query()->where('callback', true)->count()],
            'today' => ChatLog::todayCount(),
            'newCount' => ChatLog::newCount(),
        ]);
    }

    public function show(int $id): View
    {
        $chat = ChatLog::query()->where('id', $id)->first();
        abort_unless($chat, 404);
        ChatLog::markSeen($id);

        return view('admin.chats.show', [
            'chat' => $chat,
            'messages' => ChatLog::messages($chat),
            'others' => ChatLog::query()->where('visitor', $chat->visitor)->where('id', '!=', $chat->id)->count(),
        ]);
    }

    /** "Mark all as read". */
    public function seenAll(): RedirectResponse
    {
        ChatLog::markSeen();

        return redirect('/admin/chats')->with('status', 'All chats marked as read.');
    }

    /** For the admin pop-up: how many new chats, and the newest one. */
    public function newCount(): \Illuminate\Http\JsonResponse
    {
        $latest = ChatLog::unseen()->orderByDesc('last_at')->first();
        $text = '';
        if ($latest) {
            $user = array_values(array_filter(ChatLog::messages($latest), fn ($m) => $m['role'] === 'user'));
            $text = \Illuminate\Support\Str::limit((string) (end($user)['content'] ?? ''), 90);
        }

        return response()->json(['count' => ChatLog::newCount(), 'latest' => $latest ? ['id' => $latest->id, 'text' => $text, 'callback' => (bool) $latest->callback] : null])
            ->header('Cache-Control', 'no-store');
    }

    public function destroy(int $id): RedirectResponse
    {
        ChatLog::query()->where('id', $id)->delete();

        return redirect('/admin/chats')->with('status', 'Chat deleted.');
    }
}
