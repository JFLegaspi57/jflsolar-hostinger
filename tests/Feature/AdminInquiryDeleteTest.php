<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInquiryDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        return User::query()->create(['name' => 'Owner', 'email' => 'owner@jflsolar.test', 'password' => 'a long test password', 'role' => 'owner', 'active' => true, 'must_change_password' => false]);
    }

    private function inquiry(string $name, string $status): Inquiry
    {
        return Inquiry::query()->create(['name' => $name, 'phone' => '09170000000', 'email' => strtolower($name).'@example.com', 'inquiry_type' => 'equipment', 'location' => 'Cavite', 'message' => 'Test message', 'status' => $status]);
    }

    public function test_every_list_and_inquiry_page_offers_delete(): void
    {
        $staff = $this->staff();
        $handled = $this->inquiry('Handled', 'handled');
        $new = $this->inquiry('Fresh', 'new');

        $this->actingAs($staff, 'web')->get('/admin/inquiries?view=handled')->assertOk()
            ->assertSee('action="/admin/inquiries/'.$handled->id.'/delete"', false)->assertSee('Delete permanently');
        $this->actingAs($staff, 'web')->get('/admin/inquiries')->assertSee('action="/admin/inquiries/'.$new->id.'/delete"', false);
        $this->actingAs($staff, 'web')->get('/admin/inquiries/'.$new->id)->assertSee('action="/admin/inquiries/'.$new->id.'/delete"', false);
    }

    public function test_deleting_returns_to_the_same_list(): void
    {
        $staff = $this->staff();
        $handled = $this->inquiry('Handled', 'handled');
        $keep = $this->inquiry('Keep', 'handled');

        $this->actingAs($staff, 'web')->post('/admin/inquiries/'.$handled->id.'/delete', ['back' => 'handled'])
            ->assertRedirect('/admin/inquiries?view=handled')->assertSessionHas('status', 'Inquiry from Handled permanently deleted.');
        $this->assertDatabaseMissing('inquiries', ['id' => $handled->id]);
        $this->assertDatabaseHas('inquiries', ['id' => $keep->id]);
    }

    public function test_signed_out_visitors_cannot_delete(): void
    {
        $i = $this->inquiry('Safe', 'new');
        $this->post('/admin/inquiries/'.$i->id.'/delete')->assertRedirect('/admin/login');
        $this->assertDatabaseHas('inquiries', ['id' => $i->id]);
    }
}
