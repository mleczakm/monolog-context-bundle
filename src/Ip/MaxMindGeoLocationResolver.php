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
    private Reader|false|null $reader = null;

    public function __construct(
        private readonly string $databasePath,
    ) {
        if (!class_exists(Reader::class)) {
            throw new \LogicException(sprintf(
                '"%s" requires the "geoip2/geoip2" package. Run "composer require geoip2/geoip2".',
                self::class,
            ));
        }
    }

    public function resolve(string $ip): ?GeoLocation
    {
        $reader = $this->getReader();

        if ($reader === false) {
            return null;
        }

        try {
            $record = $reader->city($ip);
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

    /**
     * Opens the database lazily rather than in the constructor, and on the
     * first request only: this resolver is invoked from a Monolog processor,
     * which can run as a side effect of unrelated logging (e.g. during a
     * build-time cache:warmup, before a database mounted at deploy time
     * exists). A missing or invalid database must degrade to "no geo data"
     * rather than take down every code path that logs anything.
     */
    private function getReader(): Reader|false
    {
        if ($this->reader !== null) {
            return $this->reader;
        }

        try {
            return $this->reader = new Reader($this->databasePath);
        } catch (\Exception) {
            // Deliberately broad: GeoIp2\Database\Reader's own @throws only
            // documents InvalidDatabaseException, but its underlying
            // MaxMind\Db\Reader constructor also throws a plain
            // \InvalidArgumentException for a missing/unreadable file - this
            // boundary must swallow any failure to open the database, not an
            // enumerated subset of them.
            return $this->reader = false;
        }
    }
}
