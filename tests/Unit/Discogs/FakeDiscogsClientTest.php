<?php

declare(strict_types=1);

use App\Discogs\FakeDiscogsClient;
use App\Exceptions\Discogs\DiscogsException;
use App\Exceptions\Discogs\DiscogsNotFoundException;
use App\Exceptions\Discogs\DiscogsRateLimitException;
use App\Exceptions\Discogs\DiscogsServerException;

it('returns configured search results', function (): void {
    $fake = new FakeDiscogsClient;
    $response = [
        'results' => [['id' => 1, 'title' => 'The Dark Side of the Moon']],
        'pagination' => ['page' => 1, 'pages' => 1, 'items' => 1],
    ];

    $fake->fakeSearch($response);

    expect($fake->searchReleases('dark side'))->toBe($response);
});

it('returns empty results when no search response is configured', function (): void {
    $fake = new FakeDiscogsClient;

    expect($fake->searchReleases('anything')['results'])->toBeEmpty();
});

it('passes the page argument through', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->fakeSearch(['results' => [['id' => 1]], 'pagination' => []]);

    // The fake returns the same payload regardless of page; the real client sends it to Discogs.
    expect($fake->searchReleases('ok computer', 2))->toHaveKey('results');
});

it('returns configured barcode result', function (): void {
    $fake = new FakeDiscogsClient;
    $response = [
        'results' => [['id' => 4947580, 'title' => 'OK Computer']],
        'pagination' => ['page' => 1, 'pages' => 1, 'items' => 1],
    ];

    $fake->fakeBarcode('5099749524224', $response);

    expect($fake->searchByBarcode('5099749524224'))->toBe($response);
});

it('returns empty results for an unconfigured barcode', function (): void {
    $fake = new FakeDiscogsClient;

    expect($fake->searchByBarcode('0000000000000')['results'])->toBeEmpty();
});

it('returns configured release by id', function (): void {
    $fake = new FakeDiscogsClient;
    $release = ['id' => 249504, 'title' => 'The Dark Side Of The Moon', 'year' => 1973];

    $fake->fakeRelease(249504, $release);

    expect($fake->getRelease(249504))->toBe($release);
});

it('throws when no fake release is configured for an id', function (): void {
    $fake = new FakeDiscogsClient;

    expect(fn () => $fake->getRelease(9999))->toThrow(RuntimeException::class);
});

it('throws the configured exception on the next call', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->failWith(new DiscogsRateLimitException('rate limited', 30));

    expect(fn () => $fake->searchReleases('any'))
        ->toThrow(DiscogsRateLimitException::class, 'rate limited');
});

it('clears the exception after one throw', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->fakeSearch(['results' => [], 'pagination' => []]);
    $fake->failWith(new DiscogsException('boom'));

    try {
        $fake->searchReleases('first');
    } catch (DiscogsException) {
        // expected
    }

    // Second call must succeed.
    expect($fake->searchReleases('second'))->toHaveKey('results');
});

it('failWith works for barcode search', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->failWith(new DiscogsServerException('server error'));

    expect(fn () => $fake->searchByBarcode('123456789'))
        ->toThrow(DiscogsServerException::class);
});

it('failWith works for getRelease', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->failWith(new DiscogsNotFoundException('not found'));

    expect(fn () => $fake->getRelease(1))
        ->toThrow(DiscogsNotFoundException::class);
});
