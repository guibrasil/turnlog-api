<?php

declare(strict_types=1);

use App\Discogs\DiscogsClient;
use App\Discogs\FakeDiscogsClient;
use App\Models\User;

beforeEach(function (): void {
    $this->fake = new FakeDiscogsClient;
    $this->app->instance(DiscogsClient::class, $this->fake);
});

it('returns shaped results for an authenticated user', function (): void {
    $this->fake->fakeSearch([
        'results' => [
            [
                'id' => 249504,
                'title' => 'Pink Floyd - The Dark Side Of The Moon',
                'year' => '1973',
                'thumb' => 'https://example.com/thumb.jpg',
                'cover_image' => 'https://example.com/cover.jpg',
                'label' => ['Harvest'],
                'format' => ['Vinyl', 'LP'],
                'genre' => ['Rock'],
                'country' => 'UK',
            ],
        ],
        'pagination' => ['page' => 1, 'pages' => 10, 'per_page' => 50, 'items' => 500],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=pink+floyd')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'title', 'year', 'thumb', 'cover_image', 'label', 'format', 'genre', 'country']],
            'meta' => ['page', 'pages', 'per_page', 'total'],
        ])
        ->assertJsonPath('data.0.id', 249504)
        ->assertJsonPath('data.0.title', 'Pink Floyd - The Dark Side Of The Moon')
        ->assertJsonPath('meta.total', 500);
});

it('includes pagination metadata', function (): void {
    $this->fake->fakeSearch([
        'results' => [],
        'pagination' => ['page' => 3, 'pages' => 20, 'per_page' => 50, 'items' => 1000],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=radiohead&page=3')
        ->assertOk()
        ->assertJsonPath('meta.page', 3)
        ->assertJsonPath('meta.pages', 20)
        ->assertJsonPath('meta.total', 1000);
});

it('strips undeclared discogs fields from the response', function (): void {
    $this->fake->fakeSearch([
        'results' => [[
            'id' => 1,
            'title' => 'OK Computer',
            'master_id' => 12345,       // internal Discogs field — must not leak
            'resource_url' => 'https://api.discogs.com/releases/1',
        ]],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=ok+computer')
        ->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys(['master_id', 'resource_url']);
});

it('requires the q parameter', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});

it('rejects an invalid page value', function (): void {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=test&page=0')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['page']);
});

it('requires authentication', function (): void {
    $this->getJson('/api/releases/search?q=pink+floyd')
        ->assertUnauthorized();
});
