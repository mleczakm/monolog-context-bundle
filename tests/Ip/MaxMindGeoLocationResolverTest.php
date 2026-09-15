<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\Ip;

use Mleczakm\MonologContextBundle\Ip\MaxMindGeoLocationResolver;
use PHPUnit\Framework\TestCase;

final class MaxMindGeoLocationResolverTest extends TestCase
{
    public function testConstructorDoesNotOpenTheDatabaseEagerly(): void
    {
        // A missing database must not throw here: this resolver is invoked from
        // a Monolog processor, which can run as a side effect of unrelated
        // logging (e.g. a build-time cache:warmup) before the real database -
        // mounted separately at deploy time - exists. Throwing at construction
        // would take down every code path that logs anything.
        $resolver = new MaxMindGeoLocationResolver('/nonexistent/GeoLite2-City.mmdb');

        self::assertInstanceOf(MaxMindGeoLocationResolver::class, $resolver);
    }

    public function testResolveDegradesToNullWhenTheDatabaseIsMissing(): void
    {
        $resolver = new MaxMindGeoLocationResolver('/nonexistent/GeoLite2-City.mmdb');

        self::assertNull($resolver->resolve('203.0.113.1'));
        // Calling it again must not re-attempt (and re-fail) opening the file.
        self::assertNull($resolver->resolve('203.0.113.2'));
    }

    public function testResolveDegradesToNullWhenTheDatabaseIsInvalid(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'not-an-mmdb');
        self::assertNotFalse($path);
        file_put_contents($path, 'this is not a valid mmdb file');

        try {
            $resolver = new MaxMindGeoLocationResolver($path);

            self::assertNull($resolver->resolve('203.0.113.1'));
        } finally {
            unlink($path);
        }
    }
}
