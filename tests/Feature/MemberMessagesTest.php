<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MemberMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Cache::forget('member-messages-table-v1');
    }

    public function test_staff_post_a_message_that_only_that_member_sees_until_closed(): void
    {
        $warren = Member::query()->create(['name' => 'Warren', 'email' => 'warren@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $other = Member::query()->create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'a long test password', 'status' => 'active']);
        $owner = User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);

        $this->actingAs($owner, 'web')->post('/admin/members/'.$warren->id.'/messages', ['title' => 'A personal message from JF Legaspi', 'body' => "Hi Warren,\nCongratulations!\n<script>x</script>"])
            ->assertRedirect('/admin/members/'.$warren->id.'#messages');
        $this->get('/admin/members/'.$warren->id)->assertSee('Showing on the account page (not closed yet)');

        $this->actingAs($warren, 'member')->get('/account')->assertSee('A personal message from JF Legaspi')->assertSee('Congratulations!')->assertDontSee('<script>x</script>', false);
        session()->forget('member_pw');   // a fresh sign-in for the other member
        $this->actingAs($other, 'member')->get('/account')->assertDontSee('A personal message from JF Legaspi');

        $id = \App\Support\MemberMessages::all($warren->id)->first()->id;
        $this->actingAs($other, 'member')->post('/account/messages/'.$id.'/close');   // not theirs: nothing happens
        session()->forget('member_pw');
        $this->actingAs($warren, 'member')->get('/account')->assertSee('A personal message from JF Legaspi');
        $this->post('/account/messages/'.$id.'/close')->assertRedirect('/account');
        $this->get('/account')->assertDontSee('A personal message from JF Legaspi');

        $this->actingAs($owner, 'web')->get('/admin/members/'.$warren->id)->assertSee('Read and closed by the member');
        $this->post('/admin/members/'.$warren->id.'/messages/'.$id.'/delete')->assertRedirect();
        $this->assertCount(0, \App\Support\MemberMessages::all($warren->id));
    }
}
