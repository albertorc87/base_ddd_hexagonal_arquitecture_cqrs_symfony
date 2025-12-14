<?php

declare(strict_types=1);

namespace App\User\User\Application\Service;

use App\User\User\Application\Query\GetUserResponse;
use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\ValueObject\UserId;

final class GetUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(string $id): ?GetUserResponse
    {
        $userId = new UserId($id);
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            return null;
        }

        return new GetUserResponse(
            $user->id()->value(),
            $user->email()->value(),
            $user->name()->value(),
            $user->isEmailVerified()->value(),
            $user->createdAt(),
            $user->updatedAt(),
        );
    }
}
