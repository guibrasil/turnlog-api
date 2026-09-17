<?php

declare(strict_types=1);

namespace App\Discogs;

interface DiscogsClient
{
    /** @return array<string, mixed> */
    public function searchReleases(string $query, int $page = 1): array;

    /** @return array<string, mixed> */
    public function searchByBarcode(string $barcode): array;

    /** @return array<string, mixed> */
    public function getRelease(int $id): array;
}
