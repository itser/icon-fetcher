<?php

namespace Modules\AppIcon\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\AppIcon\Enums\AppIconTaskStatus;

class AppIconTask extends Model
{
    protected $fillable = [
        'bundle_id',
        'status',
        'apple_icon_url',
        'google_icon_url',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppIconTaskStatus::class,
            'errors' => 'array',
        ];
    }
}
