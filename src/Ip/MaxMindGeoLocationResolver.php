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
    /**
     * Not the database open state itself - just remembers that opening it
     * has failed before, to avoid retrying a known-missing/invalid file on
     * every call. A plain bool is safe to share across concurrent callers;
     * a cached Reader is not - see the note on openReader() below.
     */
    private bool $unavailable = false;

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
        $reader = $this->openReader();

        if ($reader === null) {
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
     * Opens a fresh Reader on every call rather than reusing one: this
     * resolver is a long-lived service under runtimes like Swoole, where one
     * instance serves many concurrent coroutines. GeoIp2/MaxMind's Reader is
     * not reentrant - it throws BadMethodCallException ("A lookup is already
     * in progress on this reader") if a second lookup starts on the same
     * instance before the first finishes, which a shared cached Reader hits
     * in production as soon as two coroutines' lookups interleave. Opening
     * the database only reads its (small) metadata section, not the whole
     * file, so doing this per call is cheap enough to trade for correctness.
     */
    private function openReader(): ?Reader
    {
        if ($this->unavailable) {
            return null;
        }

        try {
            return new Reader($this->databasePath);
        } catch (\Exception) {
            // Deliberately broad: GeoIp2\Database\Reader's own @throws only
            // documents InvalidDatabaseException, but its underlying
            // MaxMind\Db\Reader constructor also throws a plain
            // \InvalidArgumentException for a missing/unreadable file - this
            // boundary must swallow any failure to open the database, not an
            // enumerated subset of them.
            $this->unavailable = true;

            return null;
        }
    }
}
