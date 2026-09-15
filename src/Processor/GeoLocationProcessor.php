<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Processor;

use Mleczakm\MonologContextBundle\Ip\GeoLocationResolverInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Resolves the current request's client IP to a country/city and appends it
 * to the log record's context under the "geo" key.
 */
final class GeoLocationProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly GeoLocationResolverInterface $resolver,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $ip = $this->requestStack->getMainRequest()?->getClientIp();

        if ($ip === null) {
            return $record;
        }

        $location = $this->resolver->resolve($ip);

        if ($location === null) {
            return $record;
        }

        $context = $record->context;
        $context['geo'] = $location->toArray();

        return $record->with(context: $context);
    }
}
