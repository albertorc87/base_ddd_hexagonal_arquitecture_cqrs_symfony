<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\EventListener;

use App\Shared\Infrastructure\Api\SymfonyApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

final class ExceptionListener
{
    public function __construct(
        private readonly SymfonyApiResponse $symfonyApiResponse,
        private readonly LoggerInterface $logger,
        private readonly bool $isDebug,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Manejo de HandlerFailedException (Symfony Messenger)
        if ($exception instanceof HandlerFailedException) {
            $wrappedExceptions = $exception->getWrappedExceptions();

            foreach ($wrappedExceptions as $wrappedException) {
                // Si hay una DomainException, la devolvemos como error 400
                if ($wrappedException instanceof \DomainException) {
                    $response = $this->symfonyApiResponse->createErrorResponse(
                        $wrappedException->getMessage(),
                        Response::HTTP_BAD_REQUEST
                    );
                    $event->setResponse($response);

                    return;
                }
            }
        }

        // Manejo de DomainException directa
        if ($exception instanceof \DomainException) {
            $response = $this->symfonyApiResponse->createErrorResponse(
                $exception->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
            $event->setResponse($response);

            return;
        }

        // Manejo de excepciones HTTP (404, 403, etc.)
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() ?: Response::$statusTexts[$statusCode] ?? 'An error occurred';

            $response = $this->symfonyApiResponse->createErrorResponse(
                $message,
                $statusCode
            );
            $event->setResponse($response);

            return;
        }

        // Manejo de excepciones no controladas
        // Log del error completo para el desarrollador
        $this->logger->error('Unhandled exception', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        // Mensaje genérico para el usuario
        $message = 'An unexpected error occurred. Please try again later.';

        // En modo debug, mostrar el mensaje real de la excepción
        if ($this->isDebug) {
            $message = $exception->getMessage();
        }

        $response = $this->symfonyApiResponse->createErrorResponse(
            $message,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $event->setResponse($response);
    }
}
