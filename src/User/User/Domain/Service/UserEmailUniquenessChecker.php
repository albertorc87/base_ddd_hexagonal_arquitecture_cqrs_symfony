<?php

declare(strict_types=1);

namespace App\User\User\Domain\Service;

use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\ValueObject\UserEmail;

final class UserEmailUniquenessChecker
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function ensureEmailIsUnique(UserEmail $email): void
    {
        $existingUser = $this->userRepository->findByEmail($email);

        if (null !== $existingUser) {
            throw new \DomainException(sprintf('User with email "%s" already exists', $email->value()));
        }
    }
}
