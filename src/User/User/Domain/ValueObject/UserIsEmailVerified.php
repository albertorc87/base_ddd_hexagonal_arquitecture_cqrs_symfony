<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\BooleanValueObject;

final class UserIsEmailVerified extends BooleanValueObject
{
    public static function verified(): self
    {
        return new self(true);
    }

    public static function notVerified(): self
    {
        return new self(false);
    }

    public function isVerified(): bool
    {
        return true === $this->value();
    }

    public function isNotVerified(): bool
    {
        return false === $this->value();
    }
}
