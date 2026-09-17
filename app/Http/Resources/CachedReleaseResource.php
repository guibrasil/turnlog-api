<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CachedReleaseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->discogs_id,
            'title' => $this->title,
            'artist' => $this->artist,
            'year' => $this->year,
            'cover_url' => $this->cover_url,
        ];
    }
}
