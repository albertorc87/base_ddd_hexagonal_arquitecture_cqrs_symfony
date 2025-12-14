<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service\Email;

final class EmailAttachment
{
    /**
     * @param string      $path        Ruta al archivo en el sistema de archivos
     * @param string|null $name        Nombre del archivo adjunto (opcional, por defecto usa el nombre del archivo)
     * @param string|null $contentType Tipo MIME del archivo (opcional, se detecta automáticamente si no se proporciona)
     */
    public function __construct(
        private readonly string $path,
        private readonly ?string $name = null,
        private readonly ?string $contentType = null,
    ) {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException("File does not exist: {$path}");
        }

        if (!is_readable($path)) {
            throw new \InvalidArgumentException("File is not readable: {$path}");
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    public function name(): ?string
    {
        return $this->name ?? basename($this->path);
    }

    public function contentType(): ?string
    {
        return $this->contentType;
    }
}
