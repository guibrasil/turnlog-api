<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddToCollectionRequest;
use App\Http\Resources\CollectionItemResource;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CollectionController extends Controller
{
    public function __construct(private readonly CollectionService $collectionService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return CollectionItemResource::collection(
            $this->collectionService->getItems($user, $request->integer('page', 1)),
        );
    }

    public function store(AddToCollectionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $item = $this->collectionService->addRelease(
            user: $user,
            discogsId: $request->integer('discogs_id'),
            wishlistItemId: $request->integer('wishlist_item_id') ?: null,
        );

        $item->load('release');

        return (new CollectionItemResource($item))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $item): Response
    {
        /** @var User $user */
        $user = $request->user();

        $this->collectionService->removeItem($user, $item);

        return response()->noContent();
    }
}
