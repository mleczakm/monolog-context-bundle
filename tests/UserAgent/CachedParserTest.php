<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\UserAgent;

use Mleczakm\MonologContextBundle\UserAgent\Browser;
use Mleczakm\MonologContextBundle\UserAgent\CachedParser;
use Mleczakm\MonologContextBundle\UserAgent\ParserInterface;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class CachedParserTest extends TestCase
{
    public function testItOnlyCallsTheDecoratedParserOnce(): void
    {
        $inner = new class () implements ParserInterface {
            public int $calls = 0;

            public function parse(string $userAgent): Browser
            {
                ++$this->calls;

                return new Browser('Firefox');
            }
        };

        $cache = new InMemoryCache();
        $parser = new CachedParser($cache, $inner);

        $first = $parser->parse('some-agent-string');
        $second = $parser->parse('some-agent-string');

        self::assertEquals($first, $second);
        self::assertSame(1, $inner->calls);
    }
}

final class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $this->items[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->items[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->items = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}
