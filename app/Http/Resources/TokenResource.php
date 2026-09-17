<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TokenResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'type' => 'Bearer',
            'user' => new UserResource($this->resource['user']),
        ];
    }
}
