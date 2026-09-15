<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\RequestId;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Resets the request id at the start of every main request, adopting an
 * incoming "X-Request-Id" header when present so it can be correlated
 * across services. This also keeps state from leaking between requests
 * when the container is reused across requests (e.g. worker runtimes).
 */
final class RequestIdListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestIdStorage $storage,
        private readonly string $headerName = 'X-Request-Id',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->storage->reset();

        $incomingId = $event->getRequest()->headers->get($this->headerName);

        if ($incomingId !== null && $incomingId !== '') {
            $this->storage->set($incomingId);
        }
    }
}
