<?php

declare(strict_types=1);

namespace App\User\User\Infrastructure\Persistence\Doctrine;

use App\Shared\Infrastructure\Persistence\Doctrine\UlidType;
use App\User\User\Domain\ValueObject\UserId;

final class UserIdType extends UlidType
{
    public function getName(): string
    {
        return 'user_id';
    }

    protected function getValueObjectClass(): string
    {
        return UserId::class;
    }
}
