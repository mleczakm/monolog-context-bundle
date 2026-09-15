<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\Processor;

use Mleczakm\MonologContextBundle\Processor\TagProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class TagProcessorTest extends TestCase
{
    public function testItAppendsTagsWithoutOverwritingExisting(): void
    {
        $processor = new TagProcessor(['environment' => 'prod', 'app_version' => '1.2.3']);

        $record = new LogRecord(
            new \DateTimeImmutable(),
            'app',
            Level::Info,
            'a message',
            extra: ['tags' => ['server' => 'web-1']],
        );

        $result = $processor($record);

        self::assertSame(
            [
                'server' => 'web-1',
                'environment' => 'prod',
                'app_version' => '1.2.3',
            ],
            $result->extra['tags'],
        );
    }
}
