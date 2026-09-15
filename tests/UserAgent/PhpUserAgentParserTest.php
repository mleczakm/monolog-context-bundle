<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\UserAgent;

use Mleczakm\MonologContextBundle\UserAgent\PhpUserAgentParser;
use PHPUnit\Framework\TestCase;

final class PhpUserAgentParserTest extends TestCase
{
    public function testItParsesAKnownUserAgent(): void
    {
        $parser = new PhpUserAgentParser();

        $browser = $parser->parse(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/128.0',
        );

        self::assertSame('Firefox', $browser->name);
        self::assertSame('128.0', $browser->version);
        self::assertSame('Windows', $browser->platform);
    }
}
