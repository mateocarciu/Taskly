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

    public function test_unauthenticated_users_cannot_access_link_preview()
    {
        $response = $this->getJson('/link-preview?url=' . urlencode('https://example.com'));

        $response->assertStatus(401);
    }

    public function test_url_parameter_is_required_and_must_be_valid()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/link-preview');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);

        $response = $this->actingAs($this->user)
            ->getJson('/link-preview?url=not-a-url');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_can_fetch_and_parse_link_preview()
    {
        Http::fake([
            'https://example.com*' => Http::response(
                '<html>
                    <head>
                        <title>Example Domain</title>
                        <meta property="og:description" content="This is an example description">
                        <meta property="og:image" content="/images/og.png">
                        <link rel="icon" href="/favicon.ico">
                    </head>
                    <body>Hello</body>
                </html>',
                200,
                ['Content-Type' => 'text/html']
            )
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/link-preview?url=' . urlencode('https://example.com/some-page'));

        $response->assertStatus(200)
            ->assertJson([
                'url' => 'https://example.com/some-page',
                'title' => 'Example Domain',
                'description' => 'This is an example description',
                'image' => 'https://example.com/images/og.png',
                'favicon' => 'https://example.com/favicon.ico',
            ]);
    }

    public function test_local_urls_are_not_fetched_and_return_mock_data()
    {
        Http::preventStrayRequests();

        $response = $this->actingAs($this->user)
            ->getJson('/link-preview?url=' . urlencode('http://127.0.0.1:8000/some-local-path'));

        $response->assertStatus(200)
            ->assertJson([
                'title' => '127.0.0.1',
                'description' => 'Link to local page',
                'image' => null,
            ]);
    }
}
