<?php

declare(strict_types=1);

namespace App\User\User\Domain\Repository;

use App\User\User\Domain\User;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function findByEmail(UserEmail $email): ?User;

    public function findById(UserId $id): ?User;

    public function findAll(): array;
}
