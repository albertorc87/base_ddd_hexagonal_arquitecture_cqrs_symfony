<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class PasswordValueObject extends StringValueObject
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 20;

    public function __construct(string $value)
    {
        $this->validate($value);
        parent::__construct($value);
    }

    private function validate(string $value): void
    {
        $isValid = true;

        if (strlen($value) < self::MIN_LENGTH || strlen($value) > self::MAX_LENGTH) {
            $isValid = false;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $isValid = false;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $isValid = false;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            $isValid = false;
        }

        if (!$isValid) {
            $message = sprintf(
                'Password must be between %d and %d characters long, contain at least one uppercase letter, contain at least one number, and contain at least one symbol',
                self::MIN_LENGTH,
                self::MAX_LENGTH
            );
            throw new \InvalidArgumentException($message);
        }
    }
}
