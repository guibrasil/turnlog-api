<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ReleaseCollection extends ResourceCollection
{
    public $collects = ReleaseResource::class;

    /** @var array<string, mixed> */
    private array $pagination = [];

    /** @param array<string, mixed> $discogsResponse */
    public function __construct(array $discogsResponse)
    {
        parent::__construct(collect($discogsResponse['results'] ?? []));
        $this->pagination = $discogsResponse['pagination'] ?? [];
    }

    /** @return array<string, mixed> */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'page' => $this->pagination['page'] ?? 1,
                'pages' => $this->pagination['pages'] ?? 1,
                'per_page' => $this->pagination['per_page'] ?? 50,
                'total' => $this->pagination['items'] ?? 0,
            ],
        ];
    }
}
