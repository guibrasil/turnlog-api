<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Release>
 */
class ReleaseFactory extends Factory
{
    public function definition(): array
    {
        $discogsId = fake()->unique()->numberBetween(1_000_000, 9_999_999);

        return [
            'discogs_id' => $discogsId,
            'title' => fake()->words(3, true),
            'artist' => fake()->name(),
            'year' => fake()->numberBetween(1950, 2024),
            'cover_url' => fake()->imageUrl(),
            'raw' => ['id' => $discogsId, 'title' => 'stub'],
        ];
    }
}
