<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SearchReleasesRequest;
use App\Http\Resources\CachedReleaseResource;
use App\Http\Resources\ReleaseCollection;
use App\Http\Resources\ReleaseDetailResource;
use App\Services\ReleaseService;
use Illuminate\Http\JsonResponse;

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

    public function show(int $discogsId): JsonResponse
    {
        return (new ReleaseDetailResource(
            $this->releaseService->find($discogsId),
        ))->response()->setStatusCode(200);
    }

    public function barcode(string $barcode): JsonResponse
    {
        return (new CachedReleaseResource(
            $this->releaseService->findByBarcode($barcode),
        ))->response()->setStatusCode(200);
    }
}
