<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class DecimalValueObject
{
    public function __construct(protected float $value)
    {
    }

    final public function value(): float
    {
        return $this->value;
    }

    final public function isBiggerThan(self $other): bool
    {
        return $this->value() > $other->value();
    }
}
