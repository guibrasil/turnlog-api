<?php

declare(strict_types=1);

namespace App\Discogs;

use Illuminate\Support\Facades\Cache;

final class CachingDiscogsClient implements DiscogsClient
{
    private const int SEARCH_TTL = 3600;   // 1 hour

    private const int RELEASE_TTL = 86400; // 24 hours

    public function __construct(private readonly DiscogsClient $inner) {}

    /** @return array<string, mixed> */
    public function searchReleases(string $query, int $page = 1): array
    {
        $key = "discogs:search:{$query}:{$page}";

        return Cache::remember($key, self::SEARCH_TTL, fn () => $this->inner->searchReleases($query, $page));
    }

    /** @return array<string, mixed> */
    public function searchByBarcode(string $barcode): array
    {
        $key = "discogs:barcode:{$barcode}";

        return Cache::remember($key, self::SEARCH_TTL, fn () => $this->inner->searchByBarcode($barcode));
    }

    /** @return array<string, mixed> */
    public function getRelease(int $id): array
    {
        $key = "discogs:release:{$id}";

        return Cache::remember($key, self::RELEASE_TTL, fn () => $this->inner->getRelease($id));
    }
}
