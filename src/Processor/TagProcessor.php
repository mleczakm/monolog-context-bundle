<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Appends static key/value tags (e.g. server name, app version, commit hash)
 * to every log record's extra data under the "tags" key.
 */
final class TagProcessor implements ProcessorInterface
{
    /**
     * @param array<string, string> $tags
     */
    public function __construct(
        private readonly array $tags,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;
        $extra['tags'] = [...($extra['tags'] ?? []), ...$this->tags];

        return $record->with(extra: $extra);
    }
}
