<?php

namespace App\Shared\Domain;

class Ulid implements \Stringable
{
    final public function __construct(protected string $value)
    {
        $this->ensureIsValidUuid($value);
    }

    final public static function random(): self
    {
        // Alfabeto seguro (Base32 sin caracteres confusos: 0, O, I, L, U)
        $alphabet = '123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $alphabetLength = strlen($alphabet);

        // Obtener timestamp con microsegundos
        $microtime = microtime(true);
        $timestamp = (int) ($microtime * 1000000); // Convertir a microsegundos como entero

        // Parte del timestamp (10 caracteres)
        $timestampPart = '';
        $tempTimestamp = $timestamp;
        for ($i = 0; $i < 10; ++$i) {
            $timestampPart = $alphabet[$tempTimestamp % $alphabetLength].$timestampPart;
            $tempTimestamp = (int) ($tempTimestamp / $alphabetLength);
        }

        // Parte aleatoria (16 caracteres)
        $randomPart = '';
        for ($i = 0; $i < 16; ++$i) {
            $randomPart .= $alphabet[random_int(0, $alphabetLength - 1)];
        }

        return new static($timestampPart.$randomPart);
    }

    final public function value(): string
    {
        return $this->value;
    }

    final public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return $this->value();
    }

    private function ensureIsValidUuid(string $id): void
    {
        if (!self::isValidUlid($id)) {
            throw new \InvalidArgumentException(sprintf('<%s> does not allow the value <%s>.', self::class, $id));
        }
    }

    private static function isValidUlid(string $id): bool
    {
        // Debe tener exactamente 26 caracteres
        if (26 !== strlen($id)) {
            return false;
        }

        // Alfabeto seguro (Base32 sin caracteres confusos: 0, O, I, L, U)
        $alphabet = '123456789ABCDEFGHJKMNPQRSTVWXYZ';

        // Verificar que todos los caracteres estén en el alfabeto permitido
        // strspn devuelve la longitud de la porción inicial que contiene solo caracteres permitidos
        return 26 === strspn($id, $alphabet);
    }
}
