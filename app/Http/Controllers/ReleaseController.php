<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchReleasesRequest;
use App\Http\Resources\ReleaseCollection;
use App\Services\ReleaseService;

class ReleaseController extends Controller
{
    public function __construct(private readonly ReleaseService $releaseService) {}

    public function search(SearchReleasesRequest $request): ReleaseCollection
    {
        $response = $this->releaseService->search(
            query: $request->validated('q'),
            page: $request->integer('page', 1),
        );

        return new ReleaseCollection($response);
    }
}
