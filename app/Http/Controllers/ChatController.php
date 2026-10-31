<?php

namespace App\Http\Controllers;

use App\Support\Chatbot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/** POST /chat — the website chat assistant (JSON in, JSON out). */
class ChatController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        abort_unless(Chatbot::available(), 404);
        [$messages, $error] = Chatbot::clean($request->input('messages'));
        if ($error) {
            return response()->json(['error' => $error], 422);
        }
        // Cost guards: per visitor per hour, and for the whole site per day.
        $visitor = 'chat-hour|'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        if (RateLimiter::tooManyAttempts($visitor, (int) config('chatbot.per_visitor_hourly'))) {
            return response()->json(['error' => 'You’ve sent a lot of messages. Please wait a while, or call us directly.'], 429);
        }
        $day = 'chat-day|'.now('Asia/Manila')->toDateString();
        Cache::add($day, 0, now()->addDays(2));
        if ((int) Cache::get($day, 0) >= (int) config('chatbot.daily_cap')) {
            return response()->json(['error' => 'Our chat is busy right now. Please call or message us directly.'], 429);
        }
        RateLimiter::hit($visitor, 3600);
        Cache::increment($day);

        return response()->json(Chatbot::reply($messages, (string) $request->ip()));
    }
}
