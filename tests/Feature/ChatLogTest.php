<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ChatLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::flush();
        config(['chatbot.enabled' => true, 'chatbot.api_key' => 'test-key', 'chatbot.model' => 'claude-haiku-5-5']);
    }

    private function staff(): User
    {
        return User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
    }

    public function test_chats_are_saved_and_staff_can_read_them(): void
    {
        Http::fakeSequence('api.anthropic.com/*')
            ->push(['content' => [['type' => 'text', 'text' => 'Yes, the [Jinko 650W](/products/jinko-solar-650w-bifacial-panel) is **₱6,300**.']], 'stop_reason' => 'end_turn'])
            ->push(['content' => [['type' => 'text', 'text' => 'Opo, available po.']], 'stop_reason' => 'end_turn']);

        $cid = '3f2a9c1e-1111-4222-8333-944455556666';
        $this->postJson('/chat', ['cid' => $cid, 'page' => '/products', 'messages' => [['role' => 'user', 'content' => 'Do you have 650W panels?']]], ['User-Agent' => 'Mozilla/5.0 (iPhone)'])->assertOk();
        $this->postJson('/chat', ['cid' => $cid, 'page' => '/products', 'messages' => [
            ['role' => 'user', 'content' => 'Do you have 650W panels?'],
            ['role' => 'assistant', 'content' => 'Yes, the [Jinko 650W](/products/jinko-solar-650w-bifacial-panel) is **₱6,300**.'],
            ['role' => 'user', 'content' => 'Meron po ba kayo ngayon?'],
        ]])->assertOk();

        $this->assertSame(1, ChatLog::query()->count());
        $row = ChatLog::query()->first();
        $this->assertSame(2, (int) $row->questions);
        $this->assertSame('fil', $row->lang);
        $this->assertSame('/products', $row->page);
        $this->assertCount(4, ChatLog::messages($row));
        $this->assertStringNotContainsString('127.0.0.1', json_encode($row));

        $this->get('/admin/chats')->assertRedirect('/admin/login');
        $this->actingAs($this->staff(), 'web');
        $this->get('/admin/chats')->assertOk()->assertSee('Do you have 650W panels?')->assertSee('2 questions')->assertSee('Filipino');
        $this->get('/admin/chats?q=Meron')->assertOk()->assertSee('Do you have 650W panels?');
        $this->get('/admin/chats?q=inverter')->assertOk()->assertSee('No chats with');
        // New chat: badge in the menu, highlighted in the list, and in the pop-up check; opening it marks it read.
        $this->get('/admin/chats')->assertSee('data-new-chats', false)->assertSee('Mark all as read (1)');
        $this->getJson('/admin/chats/new-count')->assertOk()->assertJson(['count' => 1, 'latest' => ['id' => $row->id, 'text' => 'Meron po ba kayo ngayon?']]);
        $this->get('/admin/chats/'.$row->id)->assertOk()
            ->assertSee('Opo, available po.')
            ->assertSee('<a href="/products/jinko-solar-650w-bifacial-panel"', false)
            ->assertSee('<strong>₱6,300</strong>', false);
        $this->getJson('/admin/chats/new-count')->assertJson(['count' => 0]);
        $this->get('/admin/chats')->assertDontSee('Mark all as read');
        $this->get('/admin')->assertSee('View Buckny chats');

        $this->post('/admin/chats/'.$row->id.'/delete')->assertRedirect('/admin/chats');
        $this->assertSame(0, ChatLog::query()->count());
    }

    public function test_long_chats_keep_their_beginning_and_old_chats_are_pruned(): void
    {
        $msgs = [];
        for ($i = 1; $i <= 13; $i++) {
            $msgs[] = ['role' => 'user', 'content' => "Q$i"];
            ChatLog::record('chat-abcdefgh', array_slice($msgs, -23), "A$i", false, '1.2.3.4', '/', 'en', null);
            $msgs[] = ['role' => 'assistant', 'content' => "A$i"];
        }
        $all = ChatLog::messages(ChatLog::query()->first());
        $this->assertCount(26, $all);
        $this->assertSame('Q1', $all[0]['content']);

        ChatLog::query()->update(['last_at' => now()->subDays(ChatLog::KEEP_DAYS + 1)]);
        $this->assertSame(1, ChatLog::prune());
    }

    public function test_privacy_policy_and_widget_say_chats_are_kept(): void
    {
        $this->get('/privacy')->assertSee('We keep chat conversations for '.ChatLog::KEEP_DAYS.' days');
        $this->get('/')->assertSee('Chats are saved for 90 days');
    }
}
