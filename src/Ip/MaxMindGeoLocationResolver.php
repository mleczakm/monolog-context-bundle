<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Ip;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use MaxMind\Db\Reader\InvalidDatabaseException;

/**
 * Resolves IPs to a country/city using a local MaxMind GeoLite2/GeoIP2 "mmdb"
 * database. Download a free GeoLite2 database from
 * https://dev.maxmind.com/geoip/geolite2-free-geolocation-data after
 * registering for a (free) MaxMind account.
 */
final class MaxMindGeoLocationResolver implements GeoLocationResolverInterface
{
    private readonly Reader $reader;

    public function __construct(string $databasePath)
    {
        if (!class_exists(Reader::class)) {
            throw new \LogicException(sprintf(
                '"%s" requires the "geoip2/geoip2" package. Run "composer require geoip2/geoip2".',
                self::class,
            ));
        }

        $this->reader = new Reader($databasePath);
    }

    public function resolve(string $ip): ?GeoLocation
    {
        try {
            $record = $this->reader->city($ip);
        } catch (AddressNotFoundException|InvalidDatabaseException) {
            return null;
        }

        return new GeoLocation(
            countryCode: $record->country->isoCode,
            countryName: $record->country->name,
            city: $record->city->name,
            latitude: $record->location->latitude,
            longitude: $record->location->longitude,
        );
    }
}
