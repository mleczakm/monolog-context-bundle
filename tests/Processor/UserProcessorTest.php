<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\Processor;

use Mleczakm\MonologContextBundle\Processor\UserProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class UserProcessorTest extends TestCase
{
    public function testItLeavesRecordUntouchedWithoutToken(): void
    {
        $processor = new UserProcessor(new TokenStorage());

        $record = $this->createRecord();

        self::assertSame($record, $processor($record));
    }

    public function testItAppendsAuthenticatedUser(): void
    {
        $tokenStorage = new TokenStorage();
        $user = new InMemoryUser('jdoe', null, ['ROLE_ADMIN']);
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $processor = new UserProcessor($tokenStorage);

        $record = $processor($this->createRecord());

        self::assertSame(
            [
                'identifier' => 'jdoe',
                'roles' => ['ROLE_ADMIN'],
                'class' => InMemoryUser::class,
            ],
            $record->context['user'],
        );
    }

    public function testItAppendsEmailWhenUserExposesIt(): void
    {
        $tokenStorage = new TokenStorage();
        $user = new class () implements \Symfony\Component\Security\Core\User\UserInterface {
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'jdoe@example.com';
            }

            public function getEmail(): string
            {
                return 'jdoe@example.com';
            }
        };
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        $processor = new UserProcessor($tokenStorage);

        $record = $processor($this->createRecord());

        self::assertSame('jdoe@example.com', $record->context['user']['email']);
    }

    public function testItLeavesRecordUntouchedWhenTokenStorageHasNoSession(): void
    {
        // A session-usage-tracking TokenStorageInterface decorator throws this
        // whenever getToken() is called outside a session-capable request
        // (stateless contexts, component rendering in tests, etc.). Logging
        // must never fail just because no session exists yet.
        $tokenStorage = new class () implements TokenStorageInterface {
            public function getToken(): ?TokenInterface
            {
                throw new SessionNotFoundException('There is currently no session available.');
            }

            public function setToken(?TokenInterface $token): void
            {
            }
        };

        $processor = new UserProcessor($tokenStorage);

        $record = $this->createRecord();

        self::assertSame($record, $processor($record));
    }

    private function createRecord(): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            'app',
            Level::Info,
            'a message',
        );
    }
}
