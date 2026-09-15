<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Ip;

interface GeoLocationResolverInterface
{
    public function resolve(string $ip): ?GeoLocation;
}
