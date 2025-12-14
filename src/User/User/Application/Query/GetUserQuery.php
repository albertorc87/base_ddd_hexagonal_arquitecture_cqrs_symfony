<?php

declare(strict_types=1);

namespace App\User\User\Application\Query;

use App\Shared\Domain\Bus\Query\Query;

final class GetUserQuery implements Query
{
    public function __construct(
        public readonly string $id,
    ) {
    }
}
