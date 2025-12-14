<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Query;

use App\Shared\Domain\Bus\Query\Query;
use App\Shared\Domain\Bus\Query\QueryBus;
use App\Shared\Domain\Bus\Query\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class SymfonyQueryBus implements QueryBus
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function ask(Query $query): ?Response
    {
        $envelope = $this->messageBus->dispatch($query);

        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);

        if (null === $handledStamp) {
            return null;
        }

        $result = $handledStamp->getResult();

        if ($result instanceof Response) {
            return $result;
        }

        return null;
    }
}
