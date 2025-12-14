<?php

namespace App\User\User\Infrastructure\HTTP\V1;

use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Infrastructure\Api\SymfonyApiResponse;
use App\User\User\Application\Command\CreateUserCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $command = new CreateUserCommand(
            $data['email'],
            $data['password'],
            $data['name'],
        );

        $this->commandBus->dispatch($command);

        return SymfonyApiResponse::createSuccessResponse(null, 'User created successfully', Response::HTTP_CREATED);
    }
}
