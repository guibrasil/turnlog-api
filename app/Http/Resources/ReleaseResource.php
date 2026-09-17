<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $raw = $this->resource;
        [$artist, $title] = $this->splitTitle((string) ($raw['title'] ?? ''));

        return [
            'id' => $raw['id'],
            'title' => $title,
            'artist' => $artist,
            'year' => isset($raw['year']) && $raw['year'] !== '' ? (int) $raw['year'] : null,
            'label' => ($raw['label'] ?? [])[0] ?? null,
            'cover_url' => $raw['cover_image'] ?? $raw['thumb'] ?? null,
            'genres' => $raw['genre'] ?? [],
            'styles' => $raw['style'] ?? [],
        ];
    }

    /** @return array{string, string} */
    private function splitTitle(string $title): array
    {
        $parts = explode(' - ', $title, 2);

        return count($parts) === 2 ? [$parts[0], $parts[1]] : ['', $title];
    }
}
