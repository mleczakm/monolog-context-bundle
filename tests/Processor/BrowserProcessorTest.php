<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\Processor;

use Mleczakm\MonologContextBundle\Processor\BrowserProcessor;
use Mleczakm\MonologContextBundle\UserAgent\Browser;
use Mleczakm\MonologContextBundle\UserAgent\ParserInterface;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class BrowserProcessorTest extends TestCase
{
    public function testItLeavesRecordUntouchedWithoutUserAgent(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('https://example.com');
        $request->headers->remove('User-Agent');
        $requestStack->push($request);

        $processor = new BrowserProcessor($requestStack, $this->createParser());

        $record = $this->createRecord();

        self::assertSame($record, $processor($record));
    }

    public function testItAppendsParsedBrowser(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('https://example.com');
        $request->headers->set('User-Agent', 'some-agent-string');
        $requestStack->push($request);

        $processor = new BrowserProcessor($requestStack, $this->createParser());

        $record = $processor($this->createRecord());

        self::assertSame(
            ['name' => 'Firefox', 'version' => '128.0', 'platform' => 'Linux'],
            $record->context['browser'],
        );
    }

    private function createParser(): ParserInterface
    {
        return new class () implements ParserInterface {
            public function parse(string $userAgent): Browser
            {
                \PHPUnit\Framework\Assert::assertSame('some-agent-string', $userAgent);

                return new Browser('Firefox', '128.0', 'Linux');
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
