<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wishlist>
 */
class WishlistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => 'My Wishlist',
        ];
    }
}
