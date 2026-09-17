<?php

declare(strict_types=1);

namespace App\Discogs;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class HttpDiscogsClient implements DiscogsClient
{
    private const string BASE_URL = 'https://api.discogs.com';

    public function __construct(private readonly string $token) {}

    /** @return array<string, mixed> */
    public function searchReleases(string $query, int $page = 1): array
    {
        return $this->client()
            ->get('/database/search', [
                'q' => $query,
                'type' => 'release',
                'page' => $page,
            ])
            ->throw()
            ->json();
    }

    /** @return array<string, mixed> */
    public function searchByBarcode(string $barcode): array
    {
        return $this->client()
            ->get('/database/search', [
                'barcode' => $barcode,
                'type' => 'release',
            ])
            ->throw()
            ->json();
    }

    /** @return array<string, mixed> */
    public function getRelease(int $id): array
    {
        return $this->client()
            ->get("/releases/{$id}")
            ->throw()
            ->json();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withHeaders([
                'Authorization' => "Discogs token={$this->token}",
                'User-Agent' => 'TurnLog/1.0 +https://github.com/guibrasil/turnlog-api',
            ]);
    }
}
