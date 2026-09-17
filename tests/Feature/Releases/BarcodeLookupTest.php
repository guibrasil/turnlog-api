<?php

declare(strict_types=1);

use App\Discogs\DiscogsClient;
use App\Discogs\FakeDiscogsClient;
use App\Models\Release;
use App\Models\User;

beforeEach(function (): void {
    $this->fake = new FakeDiscogsClient;
    $this->app->instance(DiscogsClient::class, $this->fake);
    $this->user = User::factory()->create();
});

it('finds a release by barcode, imports it, and returns the cached record', function (): void {
    $this->fake->fakeBarcode('5099748521828', [
        'results' => [['id' => 4947580]],
        'pagination' => [],
    ]);
    $this->fake->fakeRelease(4947580, [
        'id' => 4947580,
        'title' => 'OK Computer',
        'artists' => [['name' => 'Radiohead']],
        'year' => 1997,
        'images' => [['type' => 'primary', 'uri' => 'https://example.com/cover.jpg']],
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/releases/barcode/5099748521828')
        ->assertOk()
        ->assertJsonPath('data.id', 4947580)
        ->assertJsonPath('data.title', 'OK Computer')
        ->assertJsonPath('data.artist', 'Radiohead')
        ->assertJsonPath('data.year', 1997)
        ->assertJsonPath('data.cover_url', 'https://example.com/cover.jpg');

    $this->assertDatabaseHas('releases', [
        'discogs_id' => 4947580,
        'title' => 'OK Computer',
        'artist' => 'Radiohead',
    ]);
});

it('returns the cached release without calling getRelease a second time', function (): void {
    Release::factory()->create([
        'discogs_id' => 4947580,
        'title' => 'OK Computer',
        'artist' => 'Radiohead',
    ]);

    $this->fake->fakeBarcode('5099748521828', [
        'results' => [['id' => 4947580]],
        'pagination' => [],
    ]);
    // fakeRelease intentionally not configured — getRelease would throw if called

    $this->actingAs($this->user)
        ->getJson('/api/releases/barcode/5099748521828')
        ->assertOk()
        ->assertJsonPath('data.title', 'OK Computer');

    expect($this->fake->getReleaseCalls())->toBe(0);
});

it('extracts the primary image as cover_url', function (): void {
    $this->fake->fakeBarcode('1234567890', ['results' => [['id' => 1]], 'pagination' => []]);
    $this->fake->fakeRelease(1, [
        'id' => 1,
        'title' => 'Album',
        'artists' => [['name' => 'Artist']],
        'year' => 2000,
        'images' => [
            ['type' => 'secondary', 'uri' => 'https://example.com/back.jpg'],
            ['type' => 'primary', 'uri' => 'https://example.com/front.jpg'],
        ],
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/releases/barcode/1234567890')
        ->assertJsonPath('data.cover_url', 'https://example.com/front.jpg');
});

it('returns 404 when the barcode has no Discogs match', function (): void {
    $this->fake->fakeBarcode('0000000000000', ['results' => [], 'pagination' => []]);

    $this->actingAs($this->user)
        ->getJson('/api/releases/barcode/0000000000000')
        ->assertNotFound()
        ->assertJsonPath('message', 'No release found for barcode 0000000000000.');
});

it('returns 404 for a non-numeric barcode', function (): void {
    $this->actingAs($this->user)
        ->getJson('/api/releases/barcode/NOTABARCODE')
        ->assertNotFound();
});

it('requires authentication', function (): void {
    $this->getJson('/api/releases/barcode/5099748521828')
        ->assertUnauthorized();
});
