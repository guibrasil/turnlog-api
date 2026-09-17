<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddToCollectionRequest extends FormRequest
{
    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'discogs_id' => ['required', 'integer'],
            'wishlist_item_id' => ['nullable', 'integer'],
        ];
    }
}
