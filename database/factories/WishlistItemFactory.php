<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Release;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'wishlist_id' => Wishlist::factory(),
            'release_id' => Release::factory(),
        ];
    }
}
