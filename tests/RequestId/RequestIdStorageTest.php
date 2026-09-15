<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\RequestId;

use Mleczakm\MonologContextBundle\RequestId\RequestIdGeneratorInterface;
use Mleczakm\MonologContextBundle\RequestId\RequestIdStorage;
use PHPUnit\Framework\TestCase;

final class RequestIdStorageTest extends TestCase
{
    public function testItGeneratesTheIdOnlyOnce(): void
    {
        $generator = new class () implements RequestIdGeneratorInterface {
            public int $calls = 0;

            public function generate(): string
            {
                return 'generated-' . ++$this->calls;
            }
        };

        $storage = new RequestIdStorage($generator);

        self::assertSame('generated-1', $storage->getId());
        self::assertSame('generated-1', $storage->getId());
        self::assertSame(1, $generator->calls);
    }

    public function testSetOverridesTheGeneratedId(): void
    {
        $storage = new RequestIdStorage(new class () implements RequestIdGeneratorInterface {
            public function generate(): string
            {
                return 'should-not-be-used';
            }
        });

        $storage->set('incoming-id');

        self::assertSame('incoming-id', $storage->getId());
    }

    public function testResetForcesRegeneration(): void
    {
        $generator = new class () implements RequestIdGeneratorInterface {
            public int $calls = 0;

            public function generate(): string
            {
                return 'generated-' . ++$this->calls;
            }
        };

        $storage = new RequestIdStorage($generator);

        self::assertSame('generated-1', $storage->getId());
        $storage->reset();
        self::assertSame('generated-2', $storage->getId());
    }
}
