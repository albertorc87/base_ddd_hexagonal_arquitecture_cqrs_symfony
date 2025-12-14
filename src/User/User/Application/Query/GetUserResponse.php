<?php

declare(strict_types=1);

namespace App\User\User\Application\Query;

use App\Shared\Domain\Bus\Query\Response;
use App\Shared\Domain\DTO\DTO;
use App\User\User\Domain\DTO\UserDTO;

final class GetUserResponse implements Response
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $name,
        private readonly bool $isEmailVerified,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toDTO(): DTO
    {
        return UserDTO::fromPrimitives(
            $this->id,
            $this->email,
            $this->name,
            $this->isEmailVerified,
            $this->createdAt,
            $this->updatedAt,
        );
    }
}
