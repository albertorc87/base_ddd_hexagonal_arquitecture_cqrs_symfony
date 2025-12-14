<?php

namespace App\User\User\Domain;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\Ulid;
use App\User\User\Domain\Event\UserCreated;
use App\User\User\Domain\Service\UserEmailUniquenessChecker;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserId;
use App\User\User\Domain\ValueObject\UserIsDeleted;
use App\User\User\Domain\ValueObject\UserIsEmailVerified;
use App\User\User\Domain\ValueObject\UserName;
use App\User\User\Domain\ValueObject\UserPasswordHash;

class User extends AggregateRoot
{
    private UserId $id;
    private UserEmail $email;
    private UserPasswordHash $password;
    private UserName $name;
    private UserIsEmailVerified $isEmailVerified;
    private UserIsDeleted $isDeleted;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;
    private ?\DateTimeImmutable $deletedAt;

    public function __construct(
        UserId $id,
        UserEmail $email,
        UserPasswordHash $password,
        UserName $name,
        UserIsEmailVerified $isEmailVerified,
        UserIsDeleted $isDeleted,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->isEmailVerified = $isEmailVerified;
        $this->isDeleted = $isDeleted;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->deletedAt = null;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): UserEmail
    {
        return $this->email;
    }

    public function password(): UserPasswordHash
    {
        return $this->password;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function isEmailVerified(): UserIsEmailVerified
    {
        return $this->isEmailVerified;
    }

    public function isDeleted(): UserIsDeleted
    {
        return $this->isDeleted;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function changePassword(UserPasswordHash $password): void
    {
        $this->password = $password;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeName(UserName $name): void
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function verifyEmail(): void
    {
        $this->isEmailVerified = UserIsEmailVerified::verified();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function unverifyEmail(): void
    {
        $this->isEmailVerified = UserIsEmailVerified::notVerified();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function delete(): void
    {
        $this->isDeleted = UserIsDeleted::deleted();
        $this->deletedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function undelete(): void
    {
        $this->isDeleted = UserIsDeleted::notDeleted();
        $this->deletedAt = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(
        UserId $id,
        UserEmail $email,
        UserPasswordHash $password,
        UserName $name,
        UserEmailUniquenessChecker $emailUniquenessChecker,
    ): self {
        $emailUniquenessChecker->ensureEmailIsUnique($email);

        $user = new self($id, $email, $password, $name, UserIsEmailVerified::notVerified(), UserIsDeleted::notDeleted());

        $user->record(new UserCreated(
            $id->value(),
            $email->value(),
            $name->value(),
            Ulid::random()->value(),
            (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ));

        return $user;
    }
}
