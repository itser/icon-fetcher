<?php

namespace Modules\AppIcon\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Modules\AppIcon\Models\AppIconTask */
class AppIconTaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bundle_id' => $this->bundle_id,
            'status' => $this->status->value,
            'apple_icon_url' => $this->apple_icon_url,
            'google_icon_url' => $this->google_icon_url,
            'errors' => $this->errors ?? [],
        ];
    }
}
