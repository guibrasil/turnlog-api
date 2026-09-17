<?php

declare(strict_types=1);

namespace App\Services;

use App\Discogs\DiscogsClient;
use App\Exceptions\DuplicateWishlistItemException;
use App\Models\Release;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Pagination\LengthAwarePaginator;

class WishlistService
{
    public function __construct(
        private readonly DiscogsClient $discogs,
        private readonly ReleaseImporter $importer,
    ) {}

    public function getItems(User $user, int $page = 1): LengthAwarePaginator
    {
        $wishlist = Wishlist::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => 'My Wishlist'],
        );

        return $wishlist->items()
            ->with('release')
            ->latest()
            ->paginate(perPage: 20, page: $page);
    }

    public function addRelease(User $user, int $discogsId): WishlistItem
    {
        $wishlist = Wishlist::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => 'My Wishlist'],
        );

        $release = Release::where('discogs_id', $discogsId)->first()
            ?? $this->importer->import($this->discogs->getRelease($discogsId));

        if ($wishlist->items()->where('release_id', $release->id)->exists()) {
            throw new DuplicateWishlistItemException(
                "Release {$discogsId} is already in your wishlist.",
            );
        }

        return $wishlist->items()->create(['release_id' => $release->id]);
    }

    public function removeItem(User $user, int $itemId): void
    {
        WishlistItem::whereHas(
            'wishlist',
            fn ($q) => $q->where('user_id', $user->id),
        )->findOrFail($itemId)->delete();
    }
}
