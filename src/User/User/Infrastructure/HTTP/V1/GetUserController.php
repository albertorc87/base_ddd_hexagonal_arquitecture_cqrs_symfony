<?php

declare(strict_types=1);

namespace App\User\User\Infrastructure\HTTP\V1;

use App\Shared\Domain\Bus\Query\QueryBus;
use App\Shared\Infrastructure\Api\SymfonyApiResponse;
use App\User\User\Application\Query\GetUserQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetUserController extends AbstractController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        $query = new GetUserQuery($id);

        $userResponse = $this->queryBus->ask($query);

        if (null === $userResponse) {
            return SymfonyApiResponse::createErrorResponse(
                'User not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return SymfonyApiResponse::createSuccessResponse(
            $userResponse->toDTO()->toArray(),
            'User retrieved successfully'
        );
    }
}
