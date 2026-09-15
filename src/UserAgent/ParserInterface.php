<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\UserAgent;

interface ParserInterface
{
    public function parse(string $userAgent): Browser;
}
