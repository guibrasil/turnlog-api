<?php

declare(strict_types=1);

namespace App\Discogs;

use App\Exceptions\Discogs\DiscogsAuthException;
use App\Exceptions\Discogs\DiscogsException;
use App\Exceptions\Discogs\DiscogsNotFoundException;
use App\Exceptions\Discogs\DiscogsRateLimitException;
use App\Exceptions\Discogs\DiscogsServerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class HttpDiscogsClient implements DiscogsClient
{
    private const string BASE_URL = 'https://api.discogs.com';

    public function __construct(private readonly string $token) {}

    /** @return array<string, mixed> */
    public function searchReleases(string $query, int $page = 1): array
    {
        return $this->get('/database/search', [
            'q' => $query,
            'type' => 'release',
            'format' => 'Vinyl',
            'status' => 'Official',
            'page' => $page,
        ]);
    }

    /** @return array<string, mixed> */
    public function searchByBarcode(string $barcode): array
    {
        return $this->get('/database/search', [
            'barcode' => $barcode,
            'type' => 'release',
        ]);
    }

    /** @return array<string, mixed> */
    public function getRelease(int $id): array
    {
        return $this->get("/releases/{$id}");
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function get(string $path, array $params = []): array
    {
        try {
            return $this->buildClient()->get($path, $params)->json();
        } catch (ConnectionException $e) {
            throw new DiscogsException('Could not connect to Discogs.', previous: $e);
        }
    }

    private function buildClient(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withHeaders([
                'Authorization' => "Discogs token={$this->token}",
                'User-Agent' => 'TurnLog/1.0 +https://github.com/guibrasil/turnlog-api',
            ])
            ->throw(function (Response $response, RequestException $e): void {
                throw match (true) {
                    $response->status() === 401 => new DiscogsAuthException(
                        'Discogs authentication failed.',
                    ),
                    $response->status() === 404 => new DiscogsNotFoundException(
                        'Release not found on Discogs.',
                    ),
                    $response->status() === 429 => new DiscogsRateLimitException(
                        'Discogs rate limit reached.',
                        (int) ($response->header('Retry-After') ?: 60),
                    ),
                    $response->serverError() => new DiscogsServerException(
                        'Discogs server error.',
                    ),
                    default => new DiscogsException(
                        "Discogs request failed with status {$response->status()}.",
                    ),
                };
            });
    }
}
