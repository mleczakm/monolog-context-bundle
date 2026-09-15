<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\UserAgent;

use Psr\SimpleCache\CacheInterface;

final class CachedParser implements ParserInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ParserInterface $parser,
    ) {
    }

    public function parse(string $userAgent): Browser
    {
        $key = 'monolog_context.browser.' . md5($userAgent);

        $cached = $this->cache->get($key);

        if ($cached instanceof Browser) {
            return $cached;
        }

        $browser = $this->parser->parse($userAgent);
        $this->cache->set($key, $browser);

        return $browser;
    }
}
