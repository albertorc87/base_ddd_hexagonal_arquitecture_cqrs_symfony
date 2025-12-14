<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

interface DTO
{
    public function toArray(): array;
}
