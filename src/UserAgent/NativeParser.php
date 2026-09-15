<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\UserAgent;

/**
 * Parses the User-Agent header with PHP's native get_browser(), which requires
 * the "browscap" php.ini setting to point at a browscap.ini file (see browscap.org).
 */
final class NativeParser implements ParserInterface
{
    public function parse(string $userAgent): Browser
    {
        $info = @get_browser($userAgent, true);

        if (!is_array($info)) {
            return new Browser();
        }

        return new Browser(
            name: $info['browser'] ?? null,
            version: $info['version'] ?? null,
            platform: $info['platform'] ?? null,
            isMobile: (bool) ($info['ismobiledevice'] ?? false),
        );
    }
}
