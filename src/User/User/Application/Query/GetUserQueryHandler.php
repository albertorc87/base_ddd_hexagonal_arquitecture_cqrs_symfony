<?php

declare(strict_types=1);

namespace App\User\User\Application\Query;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Bus\Query\QueryHandler;
use App\Shared\Domain\Bus\Query\Response;
use App\User\User\Application\Service\GetUserService;

final class GetUserQueryHandler implements QueryHandler
{
    public function __construct(
        private readonly GetUserService $getUserService,
    ) {
    }

    public function __invoke(GetUserQuery $query): ?Response
    {
        return $this->handle($query);
    }

    public function handle(Query $query): ?Response
    {
        if (!$query instanceof GetUserQuery) {
            throw new \InvalidArgumentException('Query must be an instance of GetUserQuery');
        }

        return $this->getUserService->__invoke($query->id);
    }
}
