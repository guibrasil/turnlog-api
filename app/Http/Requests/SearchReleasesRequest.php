<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchReleasesRequest extends FormRequest
{
    /** @return array<string, array<string>> */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
