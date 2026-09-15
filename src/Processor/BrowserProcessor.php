<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Processor;

use Mleczakm\MonologContextBundle\UserAgent\ParserInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Parses the current request's User-Agent header and appends the result to
 * the log record's context under the "browser" key.
 */
final class BrowserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ParserInterface $parser,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $userAgent = $this->requestStack->getMainRequest()?->headers->get('User-Agent');

        if ($userAgent === null || $userAgent === '') {
            return $record;
        }

        $context = $record->context;
        $context['browser'] = $this->parser->parse($userAgent)->toArray();

        return $record->with(context: $context);
    }
}
