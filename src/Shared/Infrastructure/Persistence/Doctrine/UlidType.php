<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Domain\ValueObject\UlidValueObject;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Tipo base para Custom Types de Value Objects que extienden UlidValueObject.
 */
abstract class UlidType extends StringType
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = $column['length'] ?? 26;

        return $platform->getStringTypeDeclarationSQL($column);
    }

    /**
     * Convierte el valor de la base de datos (string) al Value Object del dominio.
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?UlidValueObject
    {
        if (null === $value) {
            return null;
        }

        $valueObjectClass = $this->getValueObjectClass();

        return new $valueObjectClass((string) $value);
    }

    /**
     * Convierte el Value Object del dominio a su representación en la base de datos (string).
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof UlidValueObject) {
            return $value->value();
        }

        if (is_string($value)) {
            return $value;
        }

        throw new \InvalidArgumentException(sprintf('Expected UlidValueObject or string, got %s', gettype($value)));
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    /**
     * Retorna la clase del Value Object que este tipo maneja.
     */
    abstract protected function getValueObjectClass(): string;
}
