<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $raw = $this->raw ?? [];

        return [
            'id' => $this->discogs_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'year' => $this->year,
            'label' => $raw['labels'][0]['name'] ?? null,
            'cover_url' => $this->cover_url,
            'genres' => $raw['genres'] ?? [],
            'styles' => $raw['styles'] ?? [],
            'tracklist' => collect($raw['tracklist'] ?? [])
                ->filter(fn (array $t) => ($t['type_'] ?? 'track') === 'track')
                ->values()
                ->map(fn (array $t) => [
                    'position' => $t['position'] ?? '',
                    'title' => $t['title'] ?? '',
                    'duration' => ($t['duration'] ?? '') ?: null,
                ]),
        ];
    }
}
