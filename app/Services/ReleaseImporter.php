<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Release;

class ReleaseImporter
{
    /** @param array<string, mixed> $discogsRelease */
    public function import(array $discogsRelease): Release
    {
        return Release::updateOrCreate(
            ['discogs_id' => $discogsRelease['id']],
            [
                'title' => $discogsRelease['title'],
                'artist' => $this->extractArtist($discogsRelease),
                'year' => $discogsRelease['year'] ?? null,
                'cover_url' => $this->extractCoverUrl($discogsRelease),
                'raw' => $discogsRelease,
            ],
        );
    }

    /** @param array<string, mixed> $release */
    private function extractArtist(array $release): string
    {
        return $release['artists'][0]['name'] ?? 'Unknown Artist';
    }

    /** @param array<string, mixed> $release */
    private function extractCoverUrl(array $release): ?string
    {
        $images = $release['images'] ?? [];
        $primary = collect($images)->firstWhere('type', 'primary');

        return ($primary ?? ($images[0] ?? null))['uri'] ?? null;
    }
}
