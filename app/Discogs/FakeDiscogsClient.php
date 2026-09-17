<?php

declare(strict_types=1);

namespace App\Discogs;

final class FakeDiscogsClient implements DiscogsClient
{
    /** @var array<string, mixed> */
    private array $searchResults = ['results' => [], 'pagination' => []];

    /** @var array<string, array<string, mixed>> */
    private array $barcodeResults = [];

    /** @var array<int, array<string, mixed>> */
    private array $releases = [];

    private int $getReleaseCalls = 0;

    /** @param array<string, mixed> $response */
    public function fakeSearch(array $response): void
    {
        $this->searchResults = $response;
    }

    /** @param array<string, mixed> $response */
    public function fakeBarcode(string $barcode, array $response): void
    {
        $this->barcodeResults[$barcode] = $response;
    }

    /** @param array<string, mixed> $release */
    public function fakeRelease(int $id, array $release): void
    {
        $this->releases[$id] = $release;
    }

    public function getReleaseCalls(): int
    {
        return $this->getReleaseCalls;
    }

    /** @return array<string, mixed> */
    public function searchReleases(string $query, int $page = 1): array
    {
        return $this->searchResults;
    }

    /** @return array<string, mixed> */
    public function searchByBarcode(string $barcode): array
    {
        return $this->barcodeResults[$barcode] ?? ['results' => [], 'pagination' => []];
    }

    /** @return array<string, mixed> */
    public function getRelease(int $id): array
    {
        $this->getReleaseCalls++;

        return $this->releases[$id]
            ?? throw new \RuntimeException("No fake release configured for id {$id}.");
    }
}
