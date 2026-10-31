<?php

namespace Tests\Feature;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_package_pages_do_not_show_customer_reviews(): void
    {
        $this->seed();
        $pkg = Package::query()->where('status', 'published')->firstOrFail();

        // Other pages (e.g. About) keep the reviews strip above the footer; the packages list already hides it.
        $this->get('/about')->assertOk()->assertSee('What people say about us');
        $this->get('/packages/'.$pkg->slug)->assertOk()->assertSee($pkg->name)->assertDontSee('What people say about us');
    }

    public function test_project_pages_do_not_show_customer_reviews(): void
    {
        $this->seed();
        \App\Support\PublicContent::flush();
        $project = \App\Models\Project::query()->where('status', 'published')->firstOrFail();
        $this->get('/projects')->assertOk()->assertDontSee('What people say about us');
        $this->get('/projects/'.$project->slug)->assertOk()->assertSee($project->title)->assertDontSee('What people say about us');
    }

    public function test_forum_pages_do_not_show_customer_reviews(): void
    {
        $this->seed();
        $this->get('/forum')->assertOk()->assertDontSee('What people say about us');
        $topic = \App\Models\ForumTopic::query()->where('status', 'published')->first();
        if ($topic) {
            $this->get($topic->path())->assertOk()->assertDontSee('What people say about us');
        }
    }
}
