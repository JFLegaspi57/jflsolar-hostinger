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
        ]);
    }

    public function show(int $id): View
    {
        $chat = ChatLog::query()->where('id', $id)->first();
        abort_unless($chat, 404);

        return view('admin.chats.show', [
            'chat' => $chat,
            'messages' => ChatLog::messages($chat),
            'others' => ChatLog::query()->where('visitor', $chat->visitor)->where('id', '!=', $chat->id)->count(),
        ]);
    }

    public function destroy(int $id): RedirectResponse
    {
        ChatLog::query()->where('id', $id)->delete();

        return redirect('/admin/chats')->with('status', 'Chat deleted.');
    }
}
