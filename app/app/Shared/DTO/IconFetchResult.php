<?php

namespace App\Shared\DTO;

readonly class IconFetchResult
{
    /**
     * @param  array<string, string>  $errors
     */
    public function __construct(
        public ?string $appleIconUrl,
        public ?string $googleIconUrl,
        public array $errors = [],
    ) {}
}
