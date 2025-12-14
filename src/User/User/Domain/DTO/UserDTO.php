<?php

declare(strict_types=1);

namespace App\User\User\Domain\DTO;

use App\Shared\Domain\DTO\DTO;

final class UserDTO implements DTO
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

    public static function fromPrimitives(
        string $id,
        string $email,
        string $name,
        bool $isEmailVerified,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $email,
            $name,
            $isEmailVerified,
            $createdAt,
            $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'email' => $this->email(),
            'name' => $this->name(),
            'isEmailVerified' => $this->isEmailVerified(),
            'createdAt' => $this->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
