<?php

declare(strict_types=1);

use App\Discogs\DiscogsClient;
use App\Discogs\FakeDiscogsClient;
use App\Exceptions\Discogs\DiscogsNotFoundException;
use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Release;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

// ── index ────────────────────────────────────────────────────────────────────

describe('GET /collection', function (): void {
    it('returns paginated items with release data embedded', function (): void {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        CollectionItem::factory()->count(3)->create(['collection_id' => $collection->id]);

        $this->actingAs($this->user)
            ->getJson('/api/collection')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'added_at', 'release' => ['id', 'title', 'artist', 'year', 'cover_url']]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 3);
    });

    it('auto-creates a collection and returns empty data for a new user', function (): void {
        $this->actingAs($this->user)
            ->getJson('/api/collection')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);

        $this->assertDatabaseHas('collections', ['user_id' => $this->user->id]);
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/collection')->assertUnauthorized();
    });
});

// ── store ────────────────────────────────────────────────────────────────────

describe('POST /collection/items', function (): void {
    it('adds a release to the collection and returns the item', function (): void {
        $release = Release::factory()->create();

        $this->actingAs($this->user)
            ->postJson('/api/collection/items', ['discogs_id' => $release->discogs_id])
            ->assertCreated()
            ->assertJsonPath('data.release.id', $release->discogs_id);

        $this->assertDatabaseHas('collection_items', [
            'release_id' => $release->id,
        ]);
    });

    it('returns 404 when the release does not exist on Discogs', function (): void {
        $fake = new FakeDiscogsClient;
        $fake->failWith(new DiscogsNotFoundException('Release not found on Discogs.'));
        $this->app->instance(DiscogsClient::class, $fake);

        $this->actingAs($this->user)
            ->postJson('/api/collection/items', ['discogs_id' => 999999999])
            ->assertNotFound()
            ->assertJsonFragment(['message' => 'Release not found on Discogs.']);
    });

    it('imports and adds a release from Discogs when not in local cache', function (): void {
        $fake = new FakeDiscogsClient;
        $fake->fakeRelease(249504, [
            'id' => 249504,
            'title' => 'The Dark Side of the Moon',
            'year' => 1973,
            'artists' => [['name' => 'Pink Floyd']],
            'images' => [],
        ]);
        $this->app->instance(DiscogsClient::class, $fake);

        $this->actingAs($this->user)
            ->postJson('/api/collection/items', ['discogs_id' => 249504])
            ->assertCreated()
            ->assertJsonPath('data.release.id', 249504);

        $this->assertDatabaseHas('releases', ['discogs_id' => 249504]);
    });

    it('returns 422 when the release is already in the collection', function (): void {
        $release = Release::factory()->create();
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        CollectionItem::factory()->create([
            'collection_id' => $collection->id,
            'release_id' => $release->id,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/collection/items', ['discogs_id' => $release->discogs_id])
            ->assertUnprocessable()
            ->assertJsonPath('message', "Release {$release->discogs_id} is already in your collection.");
    });

    it('removes the wishlist item when wishlist_item_id is provided', function (): void {
        $release = Release::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $this->user->id]);
        $wishlistItem = WishlistItem::factory()->create([
            'wishlist_id' => $wishlist->id,
            'release_id' => $release->id,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/collection/items', [
                'discogs_id' => $release->discogs_id,
                'wishlist_item_id' => $wishlistItem->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('collection_items', ['release_id' => $release->id]);
        $this->assertDatabaseMissing('wishlist_items', ['id' => $wishlistItem->id]);
    });

    it('rolls back if the wishlist_item_id belongs to another user', function (): void {
        $release = Release::factory()->create();
        $otherWishlistItem = WishlistItem::factory()->create(['release_id' => $release->id]);

        $this->actingAs($this->user)
            ->postJson('/api/collection/items', [
                'discogs_id' => $release->discogs_id,
                'wishlist_item_id' => $otherWishlistItem->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('collection_items', ['release_id' => $release->id]);
        $this->assertDatabaseHas('wishlist_items', ['id' => $otherWishlistItem->id]);
    });

    it('requires authentication', function (): void {
        $this->postJson('/api/collection/items', [])->assertUnauthorized();
    });
});

// ── destroy ──────────────────────────────────────────────────────────────────

describe('DELETE /collection/items/{item}', function (): void {
    it('removes an item from the collection', function (): void {
        $collection = Collection::factory()->create(['user_id' => $this->user->id]);
        $item = CollectionItem::factory()->create(['collection_id' => $collection->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/collection/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('collection_items', ['id' => $item->id]);
    });

    it('returns 404 for an item belonging to another user', function (): void {
        $otherItem = CollectionItem::factory()->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/collection/items/{$otherItem->id}")
            ->assertNotFound();
    });

    it('requires authentication', function (): void {
        $this->deleteJson('/api/collection/items/1')->assertUnauthorized();
    });
});
