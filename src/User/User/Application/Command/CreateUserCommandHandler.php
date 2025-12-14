<?php

declare(strict_types=1);

namespace App\User\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\User\User\Application\Service\CreateUserService;

final class CreateUserCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly CreateUserService $CreateUserService,
    ) {
    }

    public function __invoke(CreateUserCommand $command): void
    {
        $this->handle($command);
    }

    public function handle(Command $command): void
    {
        if (!$command instanceof CreateUserCommand) {
            throw new \InvalidArgumentException('Command must be an instance of CreateUserCommand');
        }

        $this->CreateUserService->__invoke(
            $command->email,
            $command->password,
            $command->name,
        );
    }
}
