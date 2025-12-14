<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class SymfonyApiResponse
{
    /**
     * Crea una respuesta de éxito con la estructura de datos estandarizada.
     *
     * @param mixed $data   los datos a incluir en la respuesta (generalmente un array o DTO)
     * @param int   $status el código de estado HTTP (por defecto 200 OK)
     */
    public static function createSuccessResponse(mixed $data = null, string $message = 'ok', int $status = Response::HTTP_OK): JsonResponse
    {
        $responsePayload = [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ];

        return new JsonResponse($responsePayload, $status);
    }

    /**
     * Crea una respuesta de error con la estructura estandarizada.
     *
     * @param string $message mensaje de error a mostrar
     * @param int    $status  Código de estado HTTP del error (ej. 400, 404).
     */
    public static function createErrorResponse(string|array $message, int $status): JsonResponse
    {
        $responsePayload = [
            'status' => 'error',
            'message' => $message,
        ];

        return new JsonResponse($responsePayload, $status);
    }
}
