<?php

declare(strict_types=1);

namespace App\Services;

use App\Discogs\DiscogsClient;
use App\Exceptions\ReleaseNotFoundException;
use App\Models\Release;

class ReleaseService
{
    public function __construct(
        private readonly DiscogsClient $discogs,
        private readonly ReleaseImporter $importer,
    ) {}

    /** @return array<string, mixed> */
    public function search(string $query, int $page = 1): array
    {
        return $this->discogs->searchReleases($query, $page);
    }

    public function findByBarcode(string $barcode): Release
    {
        $results = $this->discogs->searchByBarcode($barcode)['results'] ?? [];

        if (empty($results)) {
            throw new ReleaseNotFoundException("No release found for barcode {$barcode}.");
        }

        $discogsId = (int) $results[0]['id'];

        $cached = Release::where('discogs_id', $discogsId)->first();
        if ($cached !== null) {
            return $cached;
        }

        return $this->importer->import(
            $this->discogs->getRelease($discogsId),
        );
    }
}
