<?php

declare(strict_types=1);

use App\Discogs\DiscogsClient;
use App\Discogs\FakeDiscogsClient;
use App\Models\User;

beforeEach(function (): void {
    $this->fake = new FakeDiscogsClient;
    $this->app->instance(DiscogsClient::class, $this->fake);
});

it('returns normalized results for an authenticated user', function (): void {
    $this->fake->fakeSearch([
        'results' => [
            [
                'id' => 249504,
                'title' => 'Pink Floyd - The Dark Side Of The Moon',
                'year' => '1973',
                'thumb' => 'https://example.com/thumb.jpg',
                'cover_image' => 'https://example.com/cover.jpg',
                'label' => ['Harvest'],
                'genre' => ['Rock'],
                'style' => ['Psychedelic Rock', 'Prog Rock'],
            ],
        ],
        'pagination' => ['page' => 1, 'pages' => 10, 'per_page' => 50, 'items' => 500],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=pink+floyd')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'title', 'artist', 'year', 'label', 'cover_url', 'genres', 'styles']],
            'meta' => ['page', 'pages', 'per_page', 'total'],
        ])
        ->assertJsonPath('data.0.id', 249504)
        ->assertJsonPath('data.0.title', 'The Dark Side Of The Moon')
        ->assertJsonPath('data.0.artist', 'Pink Floyd')
        ->assertJsonPath('data.0.year', 1973)
        ->assertJsonPath('data.0.label', 'Harvest')
        ->assertJsonPath('data.0.cover_url', 'https://example.com/cover.jpg')
        ->assertJsonPath('data.0.genres', ['Rock'])
        ->assertJsonPath('data.0.styles', ['Psychedelic Rock', 'Prog Rock'])
        ->assertJsonPath('meta.total', 500);
});

it('prefers cover_image over thumb for cover_url', function (): void {
    $this->fake->fakeSearch([
        'results' => [[
            'id' => 1, 'title' => 'Artist - Album',
            'cover_image' => 'https://example.com/cover.jpg',
            'thumb' => 'https://example.com/thumb.jpg',
        ]],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=test')
        ->assertJsonPath('data.0.cover_url', 'https://example.com/cover.jpg');
});

it('falls back to thumb when cover_image is absent', function (): void {
    $this->fake->fakeSearch([
        'results' => [[
            'id' => 1, 'title' => 'Artist - Album',
            'thumb' => 'https://example.com/thumb.jpg',
        ]],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=test')
        ->assertJsonPath('data.0.cover_url', 'https://example.com/thumb.jpg');
});

it('splits artist and title on the first occurrence of " - "', function (): void {
    $this->fake->fakeSearch([
        'results' => [[
            'id' => 1,
            'title' => 'Miles Davis - Kind of Blue - Legacy Edition',
        ]],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=miles')
        ->assertJsonPath('data.0.artist', 'Miles Davis')
        ->assertJsonPath('data.0.title', 'Kind of Blue - Legacy Edition');
});

it('returns empty artist when the title has no " - " separator', function (): void {
    $this->fake->fakeSearch([
        'results' => [['id' => 1, 'title' => 'Unknown Album']],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=test')
        ->assertJsonPath('data.0.artist', '')
        ->assertJsonPath('data.0.title', 'Unknown Album');
});

it('casts year string to integer', function (): void {
    $this->fake->fakeSearch([
        'results' => [['id' => 1, 'title' => 'A - B', 'year' => '1997']],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=test')
        ->assertOk();

    expect($response->json('data.0.year'))->toBe(1997);
});

it('returns null year when the field is absent', function (): void {
    $this->fake->fakeSearch([
        'results' => [['id' => 1, 'title' => 'A - B']],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=test')
        ->assertJsonPath('data.0.year', null);
});

it('does not expose raw Discogs fields', function (): void {
    $this->fake->fakeSearch([
        'results' => [[
            'id' => 1,
            'title' => 'A - B',
            'thumb' => 'https://example.com/thumb.jpg',
            'cover_image' => 'https://example.com/cover.jpg',
            'genre' => ['Rock'],
            'format' => ['Vinyl'],
            'country' => 'UK',
            'master_id' => 12345,
            'resource_url' => 'https://api.discogs.com/releases/1',
        ]],
        'pagination' => ['page' => 1, 'pages' => 1, 'per_page' => 50, 'items' => 1],
    ]);

    $response = $this->actingAs(User::factory()->create())
        ->getJson('/api/releases/search?q=test')
        ->assertOk();

    expect($response->json('data.0'))->not->toHaveKeys([
        'thumb', 'cover_image', 'genre', 'format', 'country', 'master_id', 'resource_url',
    ]);
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
