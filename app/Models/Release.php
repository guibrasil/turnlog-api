<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['discogs_id', 'title', 'artist', 'year', 'cover_url', 'raw'])]
class Release extends Model
{
    /** @use HasFactory<ReleaseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'discogs_id' => 'integer',
            'year' => 'integer',
            'raw' => 'array',
        ];
    }
}
