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
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'year' => $this->resource['year'] ?? null,
            'thumb' => $this->resource['thumb'] ?? null,
            'cover_image' => $this->resource['cover_image'] ?? null,
            'label' => $this->resource['label'] ?? [],
            'format' => $this->resource['format'] ?? [],
            'genre' => $this->resource['genre'] ?? [],
            'country' => $this->resource['country'] ?? null,
        ];
    }
}
