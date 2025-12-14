<?php

declare(strict_types=1);

namespace App\User\User\Infrastructure\Persistence\Doctrine;

use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\User;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findByEmail(UserEmail $email): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email.value' => $email->value()]);
    }

    public function findById(UserId $id): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->find($id->value());
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findAll();
    }
}
