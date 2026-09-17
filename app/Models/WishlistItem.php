<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WishlistItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wishlist_id', 'release_id'])]
class WishlistItem extends Model
{
    /** @use HasFactory<WishlistItemFactory> */
    use HasFactory;

    public function wishlist(): BelongsTo
    {
        return $this->belongsTo(Wishlist::class);
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }
}
