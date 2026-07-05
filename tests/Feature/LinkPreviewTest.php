<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LinkPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->user = User::factory()->create([
            'team_id' => $this->team->id,
        ]);
    }

    public function test_unauthenticated_users_cannot_access_link_preview_batch()
    {
        $response = $this->getJson('/link-previews/batch?urls[]=' . urlencode('https://example.com'));

        $response->assertStatus(401);
    }

    public function test_batch_endpoint_requires_urls_array_and_valid_urls()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/link-previews/batch');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['urls']);

        $response = $this->actingAs($this->user)
            ->getJson('/link-previews/batch?urls[]=not-a-url');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['urls.0']);
    }

    public function test_can_fetch_link_previews_in_batch()
    {
        Http::fake([
            'https://example1.com*' => Http::response(
                '<html><head><title>Title One</title><meta property="og:description" content="Desc One"></head><body></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://example2.com*' => Http::response(
                '<html><head><title>Title Two</title><meta property="og:description" content="Desc Two"></head><body></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/link-previews/batch?urls[]=' . urlencode('https://example1.com') . '&urls[]=' . urlencode('https://example2.com'));

        $response->assertStatus(200)
            ->assertJson([
                'https://example1.com' => [
                    'url' => 'https://example1.com',
                    'title' => 'Title One',
                    'description' => 'Desc One',
                ],
                'https://example2.com' => [
                    'url' => 'https://example2.com',
                    'title' => 'Title Two',
                    'description' => 'Desc Two',
                ],
            ]);
    }
}
