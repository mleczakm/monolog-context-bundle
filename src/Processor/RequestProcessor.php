<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Processor;

use Mleczakm\MonologContextBundle\RequestId\RequestIdStorage;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Appends request id, method, URI, route and client IP to the log record's
 * context under the "request" key.
 */
final class RequestProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly RequestIdStorage $requestIdStorage,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getMainRequest();

        if ($request === null) {
            return $record;
        }

        $context = $record->context;
        $context['request'] = array_filter([
            'id' => $this->requestIdStorage->getId(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'route' => $request->attributes->get('_route'),
            'ip' => $request->getClientIp(),
        ], static fn (mixed $value): bool => $value !== null);

        return $record->with(context: $context);
    }
}
