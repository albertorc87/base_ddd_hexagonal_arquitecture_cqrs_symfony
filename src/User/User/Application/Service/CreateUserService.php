<?php

declare(strict_types=1);

namespace App\User\User\Application\Service;

use App\Shared\Domain\Bus\Event\EventBus;
use App\Shared\Domain\Ulid;
use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\Service\UserEmailUniquenessChecker;
use App\User\User\Domain\User;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserId;
use App\User\User\Domain\ValueObject\UserName;
use App\User\User\Domain\ValueObject\UserPasswordHash;

final class CreateUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserEmailUniquenessChecker $userEmailUniquenessChecker,
        private readonly EventBus $eventBus,
    ) {
    }

    public function __invoke(
        string $email,
        string $password,
        string $name,
    ): void {
        $user = User::create(
            new UserId(Ulid::random()->value()),
            new UserEmail($email),
            new UserPasswordHash(password_hash($password, PASSWORD_DEFAULT)),
            new UserName($name),
            $this->userEmailUniquenessChecker,
        );

        $this->userRepository->save($user);

        $domainEvents = $user->pullDomainEvents();
        $this->eventBus->publish(...$domainEvents);
    }
}
