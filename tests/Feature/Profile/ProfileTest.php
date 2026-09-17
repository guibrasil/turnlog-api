<?php

declare(strict_types=1);

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;

it('returns the authenticated user profile with zero counts for a new user', function (): void {
    $user = User::factory()->create(['name' => 'Gui', 'email' => 'gui@example.com']);

    $this->actingAs($user)
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'collection_count', 'wishlist_count']])
        ->assertJsonPath('data.name', 'Gui')
        ->assertJsonPath('data.email', 'gui@example.com')
        ->assertJsonPath('data.collection_count', 0)
        ->assertJsonPath('data.wishlist_count', 0);
});

it('returns correct collection and wishlist counts', function (): void {
    $user = User::factory()->create();
    $collection = Collection::factory()->create(['user_id' => $user->id]);
    CollectionItem::factory()->count(5)->create(['collection_id' => $collection->id]);
    $wishlist = Wishlist::factory()->create(['user_id' => $user->id]);
    WishlistItem::factory()->count(3)->create(['wishlist_id' => $wishlist->id]);

    $this->actingAs($user)
        ->getJson('/api/profile')
        ->assertOk()
        ->assertJsonPath('data.collection_count', 5)
        ->assertJsonPath('data.wishlist_count', 3);
});

it('does not expose password or remember_token', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/profile')->assertOk();

    expect($response->json('data'))->not->toHaveKeys(['password', 'remember_token']);
});

it('requires authentication', function (): void {
    $this->getJson('/api/profile')->assertUnauthorized();
});
