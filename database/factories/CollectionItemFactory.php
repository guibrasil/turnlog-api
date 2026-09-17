<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Collection;
use App\Models\CollectionItem;
use App\Models\Release;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionItem>
 */
class CollectionItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'release_id' => Release::factory(),
        ];
    }
}
