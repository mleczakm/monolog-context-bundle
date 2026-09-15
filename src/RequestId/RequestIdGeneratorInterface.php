<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\RequestId;

interface RequestIdGeneratorInterface
{
    public function generate(): string;
}
