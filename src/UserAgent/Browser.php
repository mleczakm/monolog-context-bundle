<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\UserAgent;

final class Browser
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $version = null,
        public readonly ?string $platform = null,
        public readonly bool $isMobile = false,
    ) {
    }

    /** @return array<string, string|bool> */
    public function toArray(): array
    {
        return array_filter(
            [
                'name' => $this->name,
                'version' => $this->version,
                'platform' => $this->platform,
                'is_mobile' => $this->isMobile,
            ],
            static fn (mixed $value): bool => $value !== null && $value !== false,
        );
    }
}
