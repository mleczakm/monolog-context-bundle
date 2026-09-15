<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\UserAgent;

/**
 * Parses the User-Agent header with the "donatj/phpuseragentparser" package.
 */
final class PhpUserAgentParser implements ParserInterface
{
    public function __construct()
    {
        if (!function_exists('parse_user_agent')) {
            throw new \LogicException(sprintf(
                '"%s" requires the "donatj/phpuseragentparser" package. Run "composer require donatj/phpuseragentparser".',
                self::class,
            ));
        }
    }

    public function parse(string $userAgent): Browser
    {
        [
            'browser' => $browserName,
            'version' => $browserVersion,
            'platform' => $platform,
        ] = parse_user_agent($userAgent);

        return new Browser(
            name: $browserName !== '' ? $browserName : null,
            version: $browserVersion !== '' ? $browserVersion : null,
            platform: $platform !== '' ? $platform : null,
        );
    }
}
