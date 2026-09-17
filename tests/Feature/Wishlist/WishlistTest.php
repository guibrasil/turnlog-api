<?php

declare(strict_types=1);

use App\Discogs\DiscogsClient;
use App\Discogs\FakeDiscogsClient;
use App\Exceptions\Discogs\DiscogsNotFoundException;
use App\Models\Release;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->fake = new FakeDiscogsClient;
    $this->app->instance(DiscogsClient::class, $this->fake);
});

// ── index ────────────────────────────────────────────────────────────────────

describe('GET /wishlist', function (): void {
    it('returns paginated items with release data embedded', function (): void {
        $wishlist = Wishlist::factory()->create(['user_id' => $this->user->id]);
        WishlistItem::factory()->count(3)->create(['wishlist_id' => $wishlist->id]);

        $this->actingAs($this->user)
            ->getJson('/api/wishlist')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'added_at', 'release' => ['id', 'title', 'artist', 'year', 'cover_url']]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 3);
    });

    it('auto-creates a wishlist and returns empty data for a new user', function (): void {
        $this->actingAs($this->user)
            ->getJson('/api/wishlist')
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0);

        $this->assertDatabaseHas('wishlists', ['user_id' => $this->user->id]);
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/wishlist')->assertUnauthorized();
    });
});

// ── store ────────────────────────────────────────────────────────────────────

describe('POST /wishlist/items', function (): void {
    it('adds a cached release to the wishlist and returns the item', function (): void {
        $release = Release::factory()->create();

        $this->actingAs($this->user)
            ->postJson('/api/wishlist/items', ['discogs_id' => $release->discogs_id])
            ->assertCreated()
            ->assertJsonPath('data.release.id', $release->discogs_id);

        $this->assertDatabaseHas('wishlist_items', ['release_id' => $release->id]);
    });

    it('imports and adds a release from Discogs when not in local cache', function (): void {
        $this->fake->fakeRelease(249504, [
            'id' => 249504,
            'title' => 'The Dark Side of the Moon',
            'artists' => [['name' => 'Pink Floyd']],
            'year' => 1973,
            'images' => [],
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/wishlist/items', ['discogs_id' => 249504])
            ->assertCreated()
            ->assertJsonPath('data.release.id', 249504);

        $this->assertDatabaseHas('releases', ['discogs_id' => 249504]);
    });

    it('returns 422 when the release is already in the wishlist', function (): void {
        $release = Release::factory()->create();
        $wishlist = Wishlist::factory()->create(['user_id' => $this->user->id]);
        WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'release_id' => $release->id]);

        $this->actingAs($this->user)
            ->postJson('/api/wishlist/items', ['discogs_id' => $release->discogs_id])
            ->assertUnprocessable()
            ->assertJsonPath('message', "Release {$release->discogs_id} is already in your wishlist.");
    });

    it('returns 404 when the release does not exist on Discogs', function (): void {
        $this->fake->failWith(new DiscogsNotFoundException('Release not found on Discogs.'));

        $this->actingAs($this->user)
            ->postJson('/api/wishlist/items', ['discogs_id' => 999999999])
            ->assertNotFound();
    });

    it('requires authentication', function (): void {
        $this->postJson('/api/wishlist/items', [])->assertUnauthorized();
    });
});

// ── destroy ──────────────────────────────────────────────────────────────────

describe('DELETE /wishlist/items/{item}', function (): void {
    it('removes an item from the wishlist', function (): void {
        $wishlist = Wishlist::factory()->create(['user_id' => $this->user->id]);
        $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

        $this->actingAs($this->user)
            ->deleteJson("/api/wishlist/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('wishlist_items', ['id' => $item->id]);
    });

    it('returns 404 for an item belonging to another user', function (): void {
        $otherItem = WishlistItem::factory()->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/wishlist/items/{$otherItem->id}")
            ->assertNotFound();
    });

    it('requires authentication', function (): void {
        $this->deleteJson('/api/wishlist/items/1')->assertUnauthorized();
    });
});
