<?php

declare(strict_types=1);

use App\Discogs\CachingDiscogsClient;
use App\Discogs\FakeDiscogsClient;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Cache::flush();
});

it('caches search results and only calls the inner client once', function (): void {
    $inner = new FakeDiscogsClient;
    $inner->fakeSearch(['results' => [['id' => 1]], 'pagination' => []]);

    $client = new CachingDiscogsClient($inner);

    $first = $client->searchReleases('pink floyd');
    $second = $client->searchReleases('pink floyd');

    expect($first)->toBe($second);

    // Replace inner with a blank fake — a second real call would return empty results.
    // The cached client still returns the original data, proving it hit the cache.
    $blank = new FakeDiscogsClient;
    $client2 = new CachingDiscogsClient($blank);

    expect($client2->searchReleases('pink floyd'))->toBe($first);
});

it('uses separate cache keys for different queries', function (): void {
    $inner = new FakeDiscogsClient;
    $inner->fakeSearch(['results' => [['id' => 1, 'title' => 'A']], 'pagination' => []]);
    $client = new CachingDiscogsClient($inner);
    $client->searchReleases('pink floyd');

    $inner2 = new FakeDiscogsClient;
    $inner2->fakeSearch(['results' => [['id' => 2, 'title' => 'B']], 'pagination' => []]);
    $client2 = new CachingDiscogsClient($inner2);
    $result = $client2->searchReleases('radiohead');

    expect($result['results'][0]['id'])->toBe(2);
});

it('uses separate cache keys for different pages', function (): void {
    $inner = new FakeDiscogsClient;
    $inner->fakeSearch(['results' => [['id' => 10]], 'pagination' => []]);
    $client = new CachingDiscogsClient($inner);
    $client->searchReleases('pink floyd', 1);

    $inner2 = new FakeDiscogsClient;
    $inner2->fakeSearch(['results' => [['id' => 20]], 'pagination' => []]);
    $client2 = new CachingDiscogsClient($inner2);
    $result = $client2->searchReleases('pink floyd', 2);

    expect($result['results'][0]['id'])->toBe(20);
});

it('caches barcode results', function (): void {
    $inner = new FakeDiscogsClient;
    $inner->fakeBarcode('5099748521828', ['results' => [['id' => 4947580]], 'pagination' => []]);
    $client = new CachingDiscogsClient($inner);
    $client->searchByBarcode('5099748521828');

    $blank = new FakeDiscogsClient;
    $client2 = new CachingDiscogsClient($blank);
    $result = $client2->searchByBarcode('5099748521828');

    expect($result['results'][0]['id'])->toBe(4947580);
});

it('caches release details', function (): void {
    $inner = new FakeDiscogsClient;
    $inner->fakeRelease(249504, ['id' => 249504, 'title' => 'Dark Side']);
    $client = new CachingDiscogsClient($inner);
    $client->getRelease(249504);

    $blank = new FakeDiscogsClient;
    $client2 = new CachingDiscogsClient($blank);
    $result = $client2->getRelease(249504);

    expect($result['title'])->toBe('Dark Side');
});

it('stores search results under the correct cache key', function (): void {
    $inner = new FakeDiscogsClient;
    $inner->fakeSearch(['results' => [['id' => 1]], 'pagination' => []]);
    $client = new CachingDiscogsClient($inner);
    $client->searchReleases('dookie', 2);

    expect(Cache::has('discogs:search:dookie:2'))->toBeTrue();
});

it('stores release details under the correct cache key', function (): void {
    $inner = new FakeDiscogsClient;
    $inner->fakeRelease(42, ['id' => 42, 'title' => 'Test']);
    $client = new CachingDiscogsClient($inner);
    $client->getRelease(42);

    expect(Cache::has('discogs:release:42'))->toBeTrue();
});
