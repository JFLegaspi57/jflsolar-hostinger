<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Support\Chatbot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
    }

    private function on(): void
    {
        config(['chatbot.enabled' => true, 'chatbot.api_key' => 'test-key', 'chatbot.model' => 'claude-haiku-5-5']);
    }

    public function test_widget_appears_only_with_an_api_key(): void
    {
        config(['chatbot.api_key' => '']);
        $this->get('/')->assertOk()->assertDontSee('id="jfl-chat"', false);
        $this->postJson('/chat', ['messages' => [['role' => 'user', 'content' => 'Hi']]])->assertNotFound();

        $this->on();
        $this->get('/')->assertOk()->assertSee('id="jfl-chat"', false)->assertSee('Chat with us');
        $this->get('/privacy')->assertSee('id="chat"', false)->assertSee('Claude by Anthropic');
    }

    public function test_reply_uses_the_live_site_facts(): void
    {
        $this->on();
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'The [6kW Trio](/packages/6kw-trio-package-combo) is ₱205,000.']], 'stop_reason' => 'end_turn'])]);

        $this->postJson('/chat', ['messages' => [['role' => 'user', 'content' => 'Magkano ang 6kW package?']]])
            ->assertOk()->assertJson(['reply' => 'The [6kW Trio](/packages/6kw-trio-package-combo) is ₱205,000.', 'callback' => false]);

        Http::assertSent(function (HttpRequest $r) {
            $system = $r['system'][0]['text'] ?? '';

            return $r->hasHeader('x-api-key', 'test-key') && $r['model'] === 'claude-haiku-5-5'
                && str_contains($system, 'SITE FACTS') && str_contains($system, '/products/') && str_contains($system, 'Never invent')
                && $r['messages'][0]['content'] === 'Magkano ang 6kW package?' && $r['tools'][0]['name'] === 'request_callback';
        });
    }

    public function test_callback_request_is_saved_as_an_inquiry(): void
    {
        $this->on();
        Http::fakeSequence('api.anthropic.com/*')
            ->push(['content' => [['type' => 'text', 'text' => 'Saving that now.'], ['type' => 'tool_use', 'id' => 'tu_1', 'name' => 'request_callback', 'input' => ['name' => 'Maria Santos', 'mobile' => '0917 123 4567', 'topic' => 'package', 'notes' => 'Wants the 8kW Trio']]], 'stop_reason' => 'tool_use'])
            ->push(['content' => [['type' => 'text', 'text' => 'Done! Our Sales team will call you back.']], 'stop_reason' => 'end_turn']);

        $this->postJson('/chat', ['messages' => [
            ['role' => 'user', 'content' => 'Talk to a person'],
            ['role' => 'assistant', 'content' => 'Sure — your name and mobile number?'],
            ['role' => 'user', 'content' => 'Maria Santos, 0917 123 4567, about the 8kW Trio'],
        ]])->assertOk()->assertJson(['reply' => 'Done! Our Sales team will call you back.', 'callback' => true]);

        $inquiry = Inquiry::query()->latest('created_at')->firstOrFail();
        $this->assertSame('Maria Santos', $inquiry->name);
        $this->assertSame('0917 123 4567', $inquiry->phone);
        $this->assertSame('package', $inquiry->inquiry_type);
        $this->assertStringContainsString('website chat', $inquiry->message);
        $this->assertStringContainsString('Wants the 8kW Trio', $inquiry->message);
        Http::assertSentCount(2);
    }

    public function test_bad_numbers_are_not_saved(): void
    {
        [$ok, $note] = Chatbot::saveCallback(['name' => 'Jo', 'mobile' => 'call me', 'topic' => 'other', 'notes' => ''], [], '1.2.3.4');
        $this->assertFalse($ok);
        $this->assertSame(0, Inquiry::query()->where('name', 'Jo')->count());
    }

    public function test_invalid_conversations_and_limits(): void
    {
        $this->on();
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Hi!']], 'stop_reason' => 'end_turn'])]);
        $this->postJson('/chat', ['messages' => []])->assertStatus(422);
        $this->postJson('/chat', ['messages' => [['role' => 'assistant', 'content' => 'x']]])->assertStatus(422);
        $this->postJson('/chat', ['messages' => [['role' => 'user', 'content' => str_repeat('a', 1001)]]])->assertStatus(422);

        config(['chatbot.daily_cap' => 1]);
        $this->postJson('/chat', ['messages' => [['role' => 'user', 'content' => 'Hi']]])->assertOk();
        $this->postJson('/chat', ['messages' => [['role' => 'user', 'content' => 'Hi again']]])->assertStatus(429);
    }

    public function test_api_failure_gives_a_friendly_fallback(): void
    {
        $this->on();
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => ['type' => 'overloaded_error']], 529)]);
        $this->postJson('/chat', ['messages' => [['role' => 'user', 'content' => 'Hi']]])->assertOk()
            ->assertJsonPath('callback', false)->assertJson(fn ($j) => $j->where('reply', fn ($r) => str_contains($r, 'call us at'))->etc());
    }
}
