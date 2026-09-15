<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\RequestId;

use Symfony\Component\Uid\Uuid;

final class UuidRequestIdGenerator implements RequestIdGeneratorInterface
{
    public function generate(): string
    {
        return Uuid::v4()->toRfc4122();
    }
}
