<?php

declare(strict_types=1);

namespace App\Services;

use App\Discogs\DiscogsClient;

class ReleaseService
{
    public function __construct(private readonly DiscogsClient $discogs) {}

    /** @return array<string, mixed> */
    public function search(string $query, int $page = 1): array
    {
        return $this->discogs->searchReleases($query, $page);
    }
}
