<?php

declare(strict_types=1);

use App\Discogs\FakeDiscogsClient;
use App\Services\ReleaseImporter;
use App\Services\ReleaseService;

function makeService(): ReleaseService
{
    return new ReleaseService(
        new FakeDiscogsClient,
        new ReleaseImporter,
    );
}

function fakeResults(array $items): array
{
    return ['results' => $items, 'pagination' => []];
}

it('keeps a single result when all share the same master_id', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->fakeSearch(fakeResults([
        ['id' => 1, 'title' => 'Dookie', 'master_id' => 100],
        ['id' => 2, 'title' => 'Dookie', 'master_id' => 100],
        ['id' => 3, 'title' => 'Dookie', 'master_id' => 100],
    ]));

    $service = new ReleaseService($fake, new ReleaseImporter);
    $results = $service->search('dookie')['results'];

    expect($results)->toHaveCount(1);
    expect($results[0]['id'])->toBe(1);
});

it('keeps one result per distinct master_id', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->fakeSearch(fakeResults([
        ['id' => 1, 'title' => 'Dookie',  'master_id' => 100],
        ['id' => 2, 'title' => 'Dookie',  'master_id' => 100],
        ['id' => 3, 'title' => 'Nimrod',  'master_id' => 200],
        ['id' => 4, 'title' => 'Nimrod',  'master_id' => 200],
    ]));

    $service = new ReleaseService($fake, new ReleaseImporter);
    $results = $service->search('green day')['results'];

    expect($results)->toHaveCount(2);
    expect(array_column($results, 'id'))->toBe([1, 3]);
});

it('always keeps results that have no master_id', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->fakeSearch(fakeResults([
        ['id' => 1, 'title' => 'Standalone A'],
        ['id' => 2, 'title' => 'Standalone B'],
        ['id' => 3, 'title' => 'Has Master', 'master_id' => 50],
    ]));

    $service = new ReleaseService($fake, new ReleaseImporter);
    $results = $service->search('test')['results'];

    expect($results)->toHaveCount(3);
});

it('returns an empty array when there are no results', function (): void {
    $fake = new FakeDiscogsClient;
    $fake->fakeSearch(['results' => [], 'pagination' => []]);

    $service = new ReleaseService($fake, new ReleaseImporter);

    expect($service->search('nothing')['results'])->toBeEmpty();
});
