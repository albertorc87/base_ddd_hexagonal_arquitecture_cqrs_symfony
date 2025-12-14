<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\BooleanValueObject;

final class UserIsDeleted extends BooleanValueObject
{
    public static function deleted(): self
    {
        return new self(true);
    }

    public static function notDeleted(): self
    {
        return new self(false);
    }

    public function isDeleted(): bool
    {
        return true === $this->value();
    }

    public function isNotDeleted(): bool
    {
        return false === $this->value();
    }
}
