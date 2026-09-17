<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AddToWishlistRequest;
use App\Http\Resources\WishlistItemResource;
use App\Models\User;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WishlistController extends Controller
{
    public function __construct(private readonly WishlistService $wishlistService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return WishlistItemResource::collection(
            $this->wishlistService->getItems($user, $request->integer('page', 1)),
        );
    }

    public function store(AddToWishlistRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $item = $this->wishlistService->addRelease(
            user: $user,
            discogsId: $request->integer('discogs_id'),
        );

        $item->load('release');

        return (new WishlistItemResource($item))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, int $item): Response
    {
        /** @var User $user */
        $user = $request->user();

        $this->wishlistService->removeItem($user, $item);

        return response()->noContent();
    }
}
