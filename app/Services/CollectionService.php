<?php

declare(strict_types=1);

namespace App\Services;

use App\Discogs\DiscogsClient;
use App\Exceptions\DuplicateCollectionItemException;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Release;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CollectionService
{
    public function __construct(
        private readonly DiscogsClient $discogs,
        private readonly ReleaseImporter $importer,
    ) {}

    public function getItems(User $user, int $page = 1): LengthAwarePaginator
    {
        $collection = Collection::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => 'My Collection'],
        );

        return $collection->items()
            ->with('release')
            ->latest()
            ->paginate(perPage: 20, page: $page);
    }

    public function addRelease(User $user, int $discogsId, ?int $wishlistItemId = null): CollectionItem
    {
        return DB::transaction(function () use ($user, $discogsId, $wishlistItemId): CollectionItem {
            $collection = Collection::firstOrCreate(
                ['user_id' => $user->id],
                ['name' => 'My Collection'],
            );

            $release = Release::where('discogs_id', $discogsId)->first()
                ?? $this->importer->import($this->discogs->getRelease($discogsId));

            if ($collection->items()->where('release_id', $release->id)->exists()) {
                throw new DuplicateCollectionItemException(
                    "Release {$discogsId} is already in your collection.",
                );
            }

            $item = $collection->items()->create(['release_id' => $release->id]);

            if ($wishlistItemId !== null) {
                WishlistItem::whereHas(
                    'wishlist',
                    fn ($q) => $q->where('user_id', $user->id),
                )->findOrFail($wishlistItemId)->delete();
            }

            return $item;
        });
    }

    public function removeItem(User $user, int $itemId): void
    {
        CollectionItem::whereHas(
            'collection',
            fn ($q) => $q->where('user_id', $user->id),
        )->findOrFail($itemId)->delete();
    }
}
