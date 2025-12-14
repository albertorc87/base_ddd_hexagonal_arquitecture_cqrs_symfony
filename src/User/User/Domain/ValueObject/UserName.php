<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;

final class UserName extends StringValueObject
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 100;

    public function __construct(string $value)
    {
        $this->validate($value);
        parent::__construct($value);
    }

    private function validate(string $value): void
    {
        if (strlen($value) < self::MIN_LENGTH || strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(sprintf('Name must be between %d and %d characters long', self::MIN_LENGTH, self::MAX_LENGTH));
        }
    }
}
