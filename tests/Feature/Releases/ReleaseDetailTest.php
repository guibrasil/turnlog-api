<?php

declare(strict_types=1);

use App\Discogs\DiscogsClient;
use App\Discogs\FakeDiscogsClient;
use App\Exceptions\Discogs\DiscogsNotFoundException;
use App\Models\Release;
use App\Models\User;

beforeEach(function (): void {
    $this->fake = new FakeDiscogsClient;
    $this->app->instance(DiscogsClient::class, $this->fake);
    $this->user = User::factory()->create();
});

it('returns full release detail from the local cache', function (): void {
    $release = Release::factory()->create([
        'discogs_id' => 249504,
        'title' => 'The Dark Side of the Moon',
        'artist' => 'Pink Floyd',
        'year' => 1973,
        'cover_url' => 'https://example.com/cover.jpg',
        'raw' => [
            'labels' => [['name' => 'Harvest']],
            'genres' => ['Rock'],
            'styles' => ['Prog Rock', 'Psychedelic Rock'],
            'tracklist' => [
                ['type_' => 'track', 'position' => 'A1', 'title' => 'Speak to Me', 'duration' => '1:30'],
                ['type_' => 'track', 'position' => 'A2', 'title' => 'Breathe', 'duration' => '2:43'],
                ['type_' => 'heading', 'position' => '', 'title' => 'Side B', 'duration' => ''],
                ['type_' => 'track', 'position' => 'B1', 'title' => 'Money', 'duration' => '6:22'],
            ],
        ],
    ]);

    $this->actingAs($this->user)
        ->getJson("/api/releases/{$release->discogs_id}")
        ->assertOk()
        ->assertJsonPath('data.id', 249504)
        ->assertJsonPath('data.title', 'The Dark Side of the Moon')
        ->assertJsonPath('data.artist', 'Pink Floyd')
        ->assertJsonPath('data.year', 1973)
        ->assertJsonPath('data.label', 'Harvest')
        ->assertJsonPath('data.cover_url', 'https://example.com/cover.jpg')
        ->assertJsonPath('data.genres', ['Rock'])
        ->assertJsonPath('data.styles', ['Prog Rock', 'Psychedelic Rock'])
        ->assertJsonCount(3, 'data.tracklist')
        ->assertJsonPath('data.tracklist.0.position', 'A1')
        ->assertJsonPath('data.tracklist.0.title', 'Speak to Me')
        ->assertJsonPath('data.tracklist.0.duration', '1:30')
        ->assertJsonPath('data.tracklist.2.position', 'B1');

    expect($this->fake->getReleaseCalls())->toBe(0);
});

it('filters out heading entries from the tracklist', function (): void {
    Release::factory()->create([
        'discogs_id' => 1,
        'raw' => [
            'tracklist' => [
                ['type_' => 'heading', 'position' => '', 'title' => 'Side A', 'duration' => ''],
                ['type_' => 'track', 'position' => 'A1', 'title' => 'Track One', 'duration' => '3:00'],
                ['type_' => 'heading', 'position' => '', 'title' => 'Side B', 'duration' => ''],
                ['type_' => 'track', 'position' => 'B1', 'title' => 'Track Two', 'duration' => '4:00'],
            ],
        ],
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/releases/1')
        ->assertOk()
        ->assertJsonCount(2, 'data.tracklist')
        ->assertJsonPath('data.tracklist.0.title', 'Track One')
        ->assertJsonPath('data.tracklist.1.title', 'Track Two');
});

it('coerces empty duration strings to null', function (): void {
    Release::factory()->create([
        'discogs_id' => 1,
        'raw' => [
            'tracklist' => [
                ['type_' => 'track', 'position' => 'A1', 'title' => 'Track', 'duration' => ''],
            ],
        ],
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/releases/1')
        ->assertOk()
        ->assertJsonPath('data.tracklist.0.duration', null);
});

it('fetches from Discogs and imports when release is not cached', function (): void {
    $this->fake->fakeRelease(249504, [
        'id' => 249504,
        'title' => 'The Dark Side of the Moon',
        'artists' => [['name' => 'Pink Floyd']],
        'year' => 1973,
        'images' => [['type' => 'primary', 'uri' => 'https://example.com/cover.jpg']],
        'labels' => [['name' => 'Harvest']],
        'genres' => ['Rock'],
        'styles' => ['Prog Rock'],
        'tracklist' => [
            ['type_' => 'track', 'position' => 'A1', 'title' => 'Speak to Me', 'duration' => '1:30'],
        ],
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/releases/249504')
        ->assertOk()
        ->assertJsonPath('data.id', 249504)
        ->assertJsonPath('data.artist', 'Pink Floyd')
        ->assertJsonPath('data.label', 'Harvest')
        ->assertJsonCount(1, 'data.tracklist');

    $this->assertDatabaseHas('releases', ['discogs_id' => 249504]);
    expect($this->fake->getReleaseCalls())->toBe(1);
});

it('returns 404 when the release does not exist on Discogs', function (): void {
    $this->fake->failWith(new DiscogsNotFoundException('Release not found on Discogs.'));

    $this->actingAs($this->user)
        ->getJson('/api/releases/999999999')
        ->assertNotFound()
        ->assertJsonFragment(['message' => 'Release not found on Discogs.']);
});

it('requires authentication', function (): void {
    $this->getJson('/api/releases/249504')->assertUnauthorized();
});
