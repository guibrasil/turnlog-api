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
        $response = $this->discogs->searchReleases($query, $page);
        $response['results'] = $this->deduplicateByMaster($response['results'] ?? []);

        return $response;
    }

    public function find(int $discogsId): Release
    {
        return Release::where('discogs_id', $discogsId)->first()
            ?? $this->importer->import($this->discogs->getRelease($discogsId));
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

    /**
     * Keep the first result per master release; results with no master_id are always kept.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @return array<int, array<string, mixed>>
     */
    private function deduplicateByMaster(array $results): array
    {
        $seen = [];

        return array_values(array_filter(
            $results,
            function (array $result) use (&$seen): bool {
                $masterId = $result['master_id'] ?? null;

                if ($masterId === null) {
                    return true;
                }

                if (isset($seen[$masterId])) {
                    return false;
                }

                $seen[$masterId] = true;

                return true;
            },
        ));
    }
}
