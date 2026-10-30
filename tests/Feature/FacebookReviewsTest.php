<?php

namespace Tests\Feature;

use App\Support\FacebookReviews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacebookReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_and_sync_keep_only_positive_reviews_with_text(): void
    {
        Storage::fake('local');
        Http::fake(function ($req) {
            $u = $req->url();
            if (str_contains($u, '/oauth/access_token')) {
                return Http::response(['access_token' => 'LONG']);
            }
            if (str_contains($u, '/me/accounts')) {
                return Http::response(['data' => [['id' => '123', 'name' => 'JFL Solar', 'access_token' => 'PAGETOKEN']]]);
            }
            if (str_contains($u, 'after=CUR')) {
                $this->assertStringContainsString('access_token=PAGETOKEN', $u);   // next-page link keeps its query

                return Http::response(['data' => [['recommendation_type' => 'positive', 'review_text' => 'Page two review', 'reviewer' => ['name' => 'Ana Cruz']]]]);
            }

            return Http::response(['data' => [
                ['recommendation_type' => 'positive', 'review_text' => 'Great install', 'reviewer' => ['name' => 'Pedro Santos']],
                ['recommendation_type' => 'negative', 'review_text' => 'Bad'],
                ['recommendation_type' => 'positive', 'review_text' => ''],
            ], 'paging' => ['next' => 'https://graph.facebook.com/123/ratings?after=CUR&access_token=PAGETOKEN']]);
        });

        $page = FacebookReviews::connect('SHORT', '111', 'SECRET');
        $this->assertSame('JFL Solar', $page['page_name']);
        $this->assertTrue($page['never_expires']);
        $this->assertStringNotContainsString('PAGETOKEN', Storage::disk('local')->get('facebook/connection.enc'));

        $this->assertSame(2, FacebookReviews::sync());
        $this->assertSame([['Pedro Santos', 'Great install'], ['Ana Cruz', 'Page two review']], FacebookReviews::items());
    }

    public function test_errors_are_saved_without_tokens_and_old_reviews_are_kept(): void
    {
        Storage::fake('local');
        Http::fake(['*/me/accounts*' => Http::response(['data' => []]), '*/me?*' => Http::response(['id' => '123', 'name' => 'JFL Solar']), '*' => Http::response(['data' => [['recommendation_type' => 'positive', 'review_text' => 'Kept']]])]);
        FacebookReviews::connect('PAGETOKEN');
        FacebookReviews::sync();

        Http::fake(['*' => Http::response(['error' => ['message' => 'Session has expired']], 400)]);
        try {
            FacebookReviews::sync();
            $this->fail('Expected an error');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Session has expired', $e->getMessage());
        }
        $this->assertCount(1, FacebookReviews::items());
        $this->assertSame('x?access_token=[hidden]', FacebookReviews::clean('x?access_token=SECRET123'));
    }

    public function test_admin_page_is_owner_only(): void
    {
        $this->get('/admin/facebook-reviews')->assertRedirect('/admin/login');
    }
}
