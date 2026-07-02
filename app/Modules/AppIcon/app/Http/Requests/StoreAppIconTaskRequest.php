<?php

namespace Modules\AppIcon\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppIconTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'bundle_id' => [
                'required',
                'string',
                'regex:/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z][a-zA-Z0-9_]*)+$/',
            ],
        ];
    }
}
