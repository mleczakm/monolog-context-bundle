<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\Processor;

use Mleczakm\MonologContextBundle\Processor\RequestProcessor;
use Mleczakm\MonologContextBundle\RequestId\RequestIdGeneratorInterface;
use Mleczakm\MonologContextBundle\RequestId\RequestIdStorage;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class RequestProcessorTest extends TestCase
{
    public function testItLeavesRecordUntouchedWithoutRequest(): void
    {
        $processor = new RequestProcessor(new RequestStack(), $this->createRequestIdStorage());

        $record = $this->createRecord();

        self::assertSame($record, $processor($record));
    }

    public function testItAppendsRequestData(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('https://example.com/foo?bar=1', 'POST');
        $request->attributes->set('_route', 'foo_route');
        $requestStack->push($request);

        $processor = new RequestProcessor($requestStack, $this->createRequestIdStorage());

        $record = $processor($this->createRecord());

        self::assertSame('req-1', $record->context['request']['id']);
        self::assertSame('POST', $record->context['request']['method']);
        self::assertSame('/foo?bar=1', $record->context['request']['uri']);
        self::assertSame('foo_route', $record->context['request']['route']);
    }

    private function createRequestIdStorage(): RequestIdStorage
    {
        return new RequestIdStorage(new class () implements RequestIdGeneratorInterface {
            public function generate(): string
            {
                return 'req-1';
            }
        });
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
