<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\Processor;

use Mleczakm\MonologContextBundle\Ip\GeoLocation;
use Mleczakm\MonologContextBundle\Ip\GeoLocationResolverInterface;
use Mleczakm\MonologContextBundle\Processor\GeoLocationProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class GeoLocationProcessorTest extends TestCase
{
    public function testItLeavesRecordUntouchedWithoutRequest(): void
    {
        $processor = new GeoLocationProcessor(new RequestStack(), $this->createResolver(shouldBeCalled: false));

        $record = $this->createRecord();

        self::assertSame($record, $processor($record));
    }

    public function testItLeavesRecordUntouchedWhenResolverFindsNothing(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('https://example.com');
        $request->server->set('REMOTE_ADDR', '203.0.113.1');
        $requestStack->push($request);

        $resolver = new class () implements GeoLocationResolverInterface {
            public function resolve(string $ip): ?GeoLocation
            {
                return null;
            }
        };

        $processor = new GeoLocationProcessor($requestStack, $resolver);

        $record = $processor($this->createRecord());

        self::assertArrayNotHasKey('geo', $record->context);
    }

    public function testItAppendsResolvedLocation(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('https://example.com');
        $request->server->set('REMOTE_ADDR', '203.0.113.1');
        $requestStack->push($request);

        $processor = new GeoLocationProcessor($requestStack, $this->createResolver());

        $record = $processor($this->createRecord());

        self::assertSame(
            ['country_code' => 'PL', 'country_name' => 'Poland', 'city' => 'Warsaw'],
            $record->context['geo'],
        );
    }

    private function createResolver(bool $shouldBeCalled = true): GeoLocationResolverInterface
    {
        return new class ($shouldBeCalled) implements GeoLocationResolverInterface {
            public function __construct(
                private readonly bool $shouldBeCalled,
            ) {
            }

            public function resolve(string $ip): GeoLocation
            {
                if (!$this->shouldBeCalled) {
                    \PHPUnit\Framework\Assert::fail('Resolver should not have been called.');
                }

                \PHPUnit\Framework\Assert::assertSame('203.0.113.1', $ip);

                return new GeoLocation(countryCode: 'PL', countryName: 'Poland', city: 'Warsaw');
            }
        };
    }

    private function createRecord(): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            'app',
            Level::Info,
            'a message',
        );
    }
}
