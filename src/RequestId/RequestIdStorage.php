<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\RequestId;

/**
 * Holds the current request's id, generating it lazily so it also works
 * outside of the HTTP kernel (e.g. console commands).
 */
final class RequestIdStorage
{
    private ?string $id = null;

    public function __construct(
        private readonly RequestIdGeneratorInterface $generator,
    ) {
    }

    public function getId(): string
    {
        return $this->id ??= $this->generator->generate();
    }

    public function set(string $id): void
    {
        $this->id = $id;
    }

    public function reset(): void
    {
        $this->id = null;
    }
}
