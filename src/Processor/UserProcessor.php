<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Appends the currently authenticated user's identifier, roles and email
 * (when available) to the log record's context under the "user" key.
 */
final class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        try {
            $user = $this->tokenStorage->getToken()?->getUser();
        } catch (SessionNotFoundException) {
            // TokenStorageInterface is commonly decorated with a session-usage-tracking
            // storage that touches the session on every getToken() call. That throws
            // here whenever a log call happens outside of a session-capable request
            // (stateless contexts, component rendering in tests, etc.) - logging must
            // never fail just because no session exists yet.
            return $record;
        }

        if (!$user instanceof UserInterface) {
            return $record;
        }

        $data = [
            'identifier' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'class' => $user::class,
        ];

        if (method_exists($user, 'getEmail')) {
            $data['email'] = $user->getEmail();
        }

        $context = $record->context;
        $context['user'] = $data;

        return $record->with(context: $context);
    }
}
