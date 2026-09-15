<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Ip;

final class GeoLocation
{
    public function __construct(
        public readonly ?string $countryCode = null,
        public readonly ?string $countryName = null,
        public readonly ?string $city = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
    ) {
    }

    /** @return array<string, string|float> */
    public function toArray(): array
    {
        return array_filter(
            [
                'country_code' => $this->countryCode,
                'country_name' => $this->countryName,
                'city' => $this->city,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }
}
