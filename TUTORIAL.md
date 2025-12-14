# Tutorial DDD + arquitectura hexagonal + cqrs con Symfony 7

En este tutorial quiero explicar cómo implementar una API RESTFULL con DDD + arquitectura hexagonal + cqrs utilizando el framework Symfony 7.

El proyecto va a consistir en una herramienta básica de gestión de usuarios. No vamos a profundizar en la parte del security de Symfony ya que el tutorial no va de esto sino de interiorizar DDD, arquitectura hexagonal y cqrs.

Para seguir este tutorial no es necesario haber trabajado en ningún proyecto de estas características pero si tienes pocos conocimientos o ninguno sobre los temas a tratar, leed estos artículos.

- [Aprende DDD (Domain Driven Design) - Parte 1](https://cosasdedevs.com/posts/aprende-ddd-domain-driven-design-parte-1/)
- [Aprende DDD (Domain Driven Design) - Parte 2](https://cosasdedevs.com/posts/aprende-ddd-domain-driven-design-parte-2/)
- [¿Qué es CQRS?](https://cosasdedevs.com/posts/que-es-cqrs/)
- [Introducción a Arquitectura Hexagonal](https://cosasdedevs.com/posts/introduccion-arquitectura-hexagonal/)
- [Guía Completa: Principios SOLID](https://cosasdedevs.com/posts/guia-completa-principios-solid/)
- [Guía: Aprende a trabajar con APIs](https://cosasdedevs.com/posts/guia-aprende-trabajar-con-apis/)

Para empezar con el tutorial, puedes clonar el proyecto o hacer un fork, como quieras pero recuerda cambiarte a la rama llamada base:

```bash
git checkout base
```

## Requisitos

- La forma más fácil es que tengáis docker instalado pero si no lo queréis instalar o no podéis porque vuestra máquina no lo permite, necesitaréis instalar PHP 8.4, MySQL, Rabbitmq para gestionar eventos y Mailhog para usarlo como servicio de pruebas para enviar emails.

## Ejecutar docker

Solo tenemos que lanzar el docker-compose up y listo.

## Makefile

En el proyecto, hay un archivo Makefile para lanzar los comandos más fácilmente pero si no estáis en Linux, podéis entrar en él y copiar el comando directamente. En este caso, en vez de docker-compose up podemos usar make up y haría exactamente lo mismo.

## Crear archivo .env.dev.local

El archivo .env.dev ya tiene todas las variables que necesitamos pero si queréis reaprovechar el proyecto y queréis tener variables de entorno que no queréis que se suban, debéis crear un archivo .env.dev.local y añadirlas ahí. Todo lo que añadáis en este archivo va a sustituir lo que hay en el .env y .env.dev.

## Crear nuestra primera ruta

Symfony enruta por atributos en el controlador, en principio no sería un problema de acoplación ya que la parte de infrastructura al final es la parte externa, pero a mí me gusta tener todas las rutas organizadas por un yaml en lugar de desperdigadas en cada controlador así que lo vamos a cambiar:

Abrimos el archivo config/routes.yaml y reemplazamos todo su contenido por el siguiente:

```yaml
user_routes:
    resource: routes/User/user.yaml
    type: yaml
    prefix: /v1
```

Como ves, he añadido el prefijo v1, así, si en un futuro actualizamos la api a una nueva versión, podremos hacer que convivan las dos el tiempo necesario. Esto lo explico mejor en mi libro para aprender a trabajar con APIs que si no lo has leído te lo recomiendo https://cosasdedevs.com/posts/guia-aprende-trabajar-con-apis/

Ahora creamos el archivo config/routes/User/user.yaml, esto lo separo por módulo la carpeta y agregado para el archivo. Una vez hecho esto, añadimos el siguiente contenido:

```yaml
create_user:
    path: /users
    controller: App\User\User\Infrastructure\HTTP\V1\CreateUserController
    methods:  [POST]
```

Ahora vamos a crear nuestro primer controlador, ya os ha hecho spoiler, esa es la ruta src/User/User/Infrastructure/HTTP/V1/CreateUserController.php

Aquí ya estamos aplicando arquitectura hexagonal; un controlador sería un adaptador primario ya que son puntos de entrada a nuestra aplicación tomando una tecnología externa como es una petición http y traduce una puerta al puerto de entrada del dominio.

y le añadimos el siguiente código:

```php
<?php


namespace App\User\User\Infrastructure\HTTP\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CreateUserController extends AbstractController
{
    public function __invoke(Request $request): JsonResponse
    {
        return $this->json([
            'message' => 'User created successfully',
        ], Response::HTTP_CREATED);
    }
}
```

Ahora podemos utilizar una herramienta como postman o curl para hacer una petición y ver que todo funciona correctamente, debe ser tipo post y la url es la siguiente http://localhost:8080/v1/users

La carpeta src/Controller ya la podemos eliminar, las carpetas Repository y Entity las dejamos de momento hasta que cambiemos ciertas configuraciones. Estas se crean al instalar la librería para el ORM.

En esta parte ya puedes ver que estamos aplicando DDD + Arquitectura Hexagonal. Hemos creado una frontera por el modelo de User creando la carpeta src/User. Dentro de ella, creamos una segunda carpeta llamada User donde irán nuestros modelos, agregados, value objects. ¿Por qué duplicamos el nombre y no usamos directamente el primer nivel de User? Esto es asi porque dentro del mismo bounded context podemos tener distintos módulos relacionados.

Por ejemplo, podríamos añadir dentro de User el módulo de Role ya que está directamente relacionado con la gestión de usuarios. ¿Qué no encajaría aquí? Pues por ejemplo un módulo Pedidos ya que aunque usuario hace un pedido, no está relacionado con la gestión de usuarios.

La explicación corta y clara de qué es un bounded context es que es un espacio donde un modelo tiene un significado concreto y no entra en conflicto con otros modelos.

Una cosa que me gusta a mí hacer, y que también te recomiendo hacer, es estandarizar la respuesta de nuestra api, así que vamos a crear un adaptador de salida para ello y así de paso vamos a crear un segundo bounded context que será el de Shared y ahí tendremos todos los elementos compartidos con los demás contextos.

Como vamos a depender de una librería externa lo añadiremos dentro de la capa de infrastructura.

Creamos el archivo src/Shared/Infrastructure/Api/SymfonyApiResponse.php

Shared será un módulo donde tendremos toda las clases compartidas.

Después, la carpeta Infrastructure; aquí es donde guardamos los adaptadores de entrada o salida: En este caso será uno de salida, para dar respuesta a nuestro usuario siempre con el mismo formato.

Api, para organizar todas las posibles clases que necesitemos para todo lo que esté relacionado con la Api.

Y por último creamos el archivo SymfonyApiResponse.php, con el siguiente contenido:

```php
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
     * @param mixed $data Los datos a incluir en la respuesta (generalmente un array o DTO).
     * @param int $status El código de estado HTTP (por defecto 200 OK).
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
     * @param string $message Mensaje de error a mostrar.
     * @param int $status Código de estado HTTP del error (ej. 400, 404).
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
```

Como ves, esta clase tiene dos tipos de respuesta, una para las positivas y otra para las erróneas y el usuario siempre recibirá el mismo formato de respuesta.

La magia de esto es que si el día de mañana symfony cambia la clase de la respuesta o la renombra, solo tenemos que editar este archivo y ya tenemos todos los controladores migrados.

## Crear el dominio de User

Ahora vamos a crear el dominio para User. Para ello vamos a valernos de los Value Object que por si no lo recuerdas, es una clase que contiene un valor primitivo y en la que podemos realizar las validaciones pertinentes.

Como todos los contextos van a compartir el campo id, vamos a crear una clase compartida para validarlos y generarlos. Los tipos de ids que vamos a utilizar son los llamados de tipo Ulid.

Estos son similares al tipo Uuid pero son más cortos y ordenables, además descartan los caracteres que pueden confundirse como el cero y la o. Podríamos usar una clase existente pero ya que es muy sencillo de generarlos, he creado mi propia clase. Para ello vamos a crear el siguiente archivo src/Shared/Domain/Ulid.php.

```php
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
        if (! self::isValidUlid($id)) {
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
```

Ahora crearemos unos cuantos value objects genéricos para aprovecharlos posteriormente.

```php
## src\Shared\Infrastructure\Api\Domain\ValueObject\BooleanValueObject.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class BooleanValueObject
{
    public function __construct(protected bool $value)
    {
    }

    final public function value(): bool
    {
        return $this->value;
    }
}

## src\Shared\Infrastructure\Api\Domain\ValueObject\DecimalValueObject.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class DecimalValueObject
{
    public function __construct(protected float $value)
    {
    }

    final public function value(): float
    {
        return $this->value;
    }

    final public function isBiggerThan(self $other): bool
    {
        return $this->value() > $other->value();
    }
}

## src\Shared\Infrastructure\Api\Domain\ValueObject\StringValueObject.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class StringValueObject
{
    public function __construct(protected string $value)
    {
    }

    final public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value();
    }
}

## src\Shared\Infrastructure\Api\Domain\ValueObject\EmailValueObject.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class EmailValueObject extends StringValueObject
{
    public function __construct(string $value)
    {
        $this->validate($value);
        parent::__construct($value);
    }

    private function validate(string $value): void
    {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(sprintf('Invalid email format: %s', $value));
        }
    }
}

## src\Shared\Infrastructure\Api\Domain\ValueObject\IntValueObject.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class IntValueObject
{
    public function __construct(protected int $value)
    {
    }

    final public function value(): int
    {
        return $this->value;
    }

    final public function isBiggerThan(self $other): bool
    {
        return $this->value() > $other->value();
    }
}

## src\Shared\Infrastructure\Api\Domain\ValueObject\PasswordValueObject.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

abstract class PasswordValueObject extends StringValueObject
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 20;

    public function __construct(string $value)
    {
        $this->validate($value);
        parent::__construct($value);
    }

    private function validate(string $value): void
    {
        $isValid = true;

        if (strlen($value) < self::MIN_LENGTH || strlen($value) > self::MAX_LENGTH) {
            $isValid = false;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $isValid = false;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $isValid = false;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $value)) {
            $isValid = false;
        }

        if (!$isValid) {
            $message = sprintf(
                'Password must be between %d and %d characters long, contain at least one uppercase letter, contain at least one number, and contain at least one symbol',
                self::MIN_LENGTH,
                self::MAX_LENGTH
            );
            throw new \InvalidArgumentException($message);
        }
    }
}

# src\Shared\Infrastructure\Api\Domain\ValueObject\UlidValueObject.php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Ulid;

class UlidValueObject extends Ulid
{
}
```

Ahora vamos a crear los value objects que después usaremos en el dominio de User, que serán para el id, email, si el usuario ha sido borrado (usaremos borrados lógicos), si el email está verificado, el nombre, password y el password hasheado.

```php
<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\EmailValueObject;

final class UserEmail extends EmailValueObject
{
}

<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\UlidValueObject;

final class UserId extends UlidValueObject
{
}

<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\BooleanValueObject;

final class UserIsDeleted extends BooleanValueObject
{
    public static function deleted(): self
    {
        return new self(true);
    }

    public static function notDeleted(): self
    {
        return new self(false);
    }

    public function isDeleted(): bool
    {
        return true === $this->value();
    }

    public function isNotDeleted(): bool
    {
        return false === $this->value();
    }
}

<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\BooleanValueObject;

final class UserIsEmailVerified extends BooleanValueObject
{

    public static function verified(): self
    {
        return new self(true);
    }

    public static function notVerified(): self
    {
        return new self(false);
    }

    public function isVerified(): bool
    {
        return true === $this->value();
    }

    public function isNotVerified(): bool
    {
        return false === $this->value();
    }
}

<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;

final class UserName extends StringValueObject
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 100;

    public function __construct(string $value)
    {
        $this->validate($value);
        parent::__construct($value);
    }

    private function validate(string $value): void
    {
        if (strlen($value) < self::MIN_LENGTH || strlen($value) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Name must be between %d and %d characters long', self::MIN_LENGTH, self::MAX_LENGTH)
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\PasswordValueObject;

final class UserPassword extends PasswordValueObject
{
}

<?php

declare(strict_types=1);

namespace App\User\User\Domain\ValueObject;

use App\Shared\Domain\ValueObject\StringValueObject;

final class UserPasswordHash extends StringValueObject
{
}

```

Crear la clase para user

```php
<?php

namespace App\User\User\Domain;

use DateTimeImmutable;
use App\User\User\Domain\ValueObject\UserId;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserPasswordHash;
use App\User\User\Domain\ValueObject\UserName;
use App\User\User\Domain\ValueObject\UserIsEmailVerified;
use App\User\User\Domain\ValueObject\UserIsDeleted;

class User {

    private UserId $id;
    private UserEmail $email;
    private UserPasswordHash $password;
    private UserName $name;
    private UserIsEmailVerified $isEmailVerified;
    private UserIsDeleted $isDeleted;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;

    public function __construct(
        UserId $id,
        UserEmail $email,
        UserPasswordHash $password,
        UserName $name,
        UserIsEmailVerified $isEmailVerified,
        UserIsDeleted $isDeleted,
    )
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->name = $name;
        $this->isEmailVerified = $isEmailVerified;
        $this->isDeleted = $isDeleted;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->deletedAt = null;
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): UserEmail
    {
        return $this->email;
    }

    public function password(): UserPasswordHash
    {
        return $this->password;
    }

    public function name(): UserName
    {
        return $this->name;
    }
    
    public function isEmailVerified(): UserIsEmailVerified
    {
        return $this->isEmailVerified;
    }

    public function isDeleted(): UserIsDeleted
    {
        return $this->isDeleted;
    }
    
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
    
    public function changePassword(UserPasswordHash $password): void
    {
        $this->password = $password;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function changeName(UserName $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function verifyEmail(): void
    {
        $this->isEmailVerified = UserIsEmailVerified::verified();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function unverifyEmail(): void
    {
        $this->isEmailVerified = UserIsEmailVerified::notVerified();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function delete(): void
    {
        $this->isDeleted = UserIsDeleted::deleted();
        $this->deletedAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function undelete(): void
    {
        $this->isDeleted = UserIsDeleted::notDeleted();
        $this->deletedAt = null;
        $this->updatedAt = new DateTimeImmutable();
    }
}
```

## Crear los mappings

Ahora vamos a crear el mapping para la base de datos. Como nosotros lo tenemos configurado en el docker ya está creada, si no estás usando docker, entra al contenedor y lanza php bin/console doctrine:database:create

Después debemos editar el archivo de configuración de doctrine el cual se encuentra en config/packages/doctrine.yaml y añadiremos el siguiente contenido en la sección orm:

```yaml
    orm:
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        identity_generation_preferences:
            Doctrine\DBAL\Platforms\mysqlPlatform: identity
        auto_mapping: true
        mappings:
            User:
                type: xml
                is_bundle: false
                dir: '%kernel.project_dir%/src/User/User/Infrastructure/Persistence/Doctrine/Entity'
                prefix: 'App\User\User\Domain'
                alias: User
```

Para el caso de los ids, para usarlo en los mappings, tenemos que crear el archivo src/Shared/Infrastructure/Persistence/Doctrine/UlidType.php el cual nos servirá de base para todos los ids que declaremos.

```php
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
```

Luego para el id user, tenemos que crear el src/User/User/Infrastructure/Persistence/Doctrine/UserIdType.php que extiende de la clase que acabamos de crear:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Infrastructure\Persistence\Doctrine;

use App\Shared\Infrastructure\Persistence\Doctrine\UlidType;
use App\User\User\Domain\ValueObject\UserId;

final class UserIdType extends UlidType
{
    public function getName(): string
    {
        return 'user_id';
    }

    protected function getValueObjectClass(): string
    {
        return UserId::class;
    }
}
```

### Añadir el type a doctrine yaml

Una vez hecho esto, debemos añadir la siguiente información dentro de la sección dbal del archivo de configuración de doctrine config\packages\doctrine.yaml.

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

        # IMPORTANT: You MUST configure your server version,
        # either here or in the DATABASE_URL env var (see .env file)
        #server_version: '16'

        profiling_collect_backtrace: '%kernel.debug%'
        use_savepoints: true

        # Custom Types para Value Objects
        types:
            user_id: App\User\User\Infrastructure\Persistence\Doctrine\UserIdType
```

Ahora vamos a crear los mappings. Aquí no me voy a explayar ya que no va de esto el tutorial. Simplemente os lo dejo aquí y solo debéis crearlos en la carpeta src\User\User\Infrastructure\Persistence\Doctrine\Entity

```xml
# User.orm.xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\User\User\Domain\User" table="user">
        <id name="id" type="user_id" column="id" length="26">
            <generator strategy="NONE"/>
        </id>

        <embedded name="email" class="App\User\User\Domain\ValueObject\UserEmail" use-column-prefix="false"/>
        <embedded name="password" class="App\User\User\Domain\ValueObject\UserPasswordHash" use-column-prefix="false"/>
        <embedded name="name" class="App\User\User\Domain\ValueObject\UserName" use-column-prefix="false"/>
        <embedded name="isEmailVerified" class="App\User\User\Domain\ValueObject\UserIsEmailVerified" use-column-prefix="false"/>
        <embedded name="isDeleted" class="App\User\User\Domain\ValueObject\UserIsDeleted" use-column-prefix="false"/>
        <field name="createdAt" type="datetime_immutable" column="created_at"/>
        <field name="updatedAt" type="datetime_immutable" column="updated_at" nullable="true"/>
        <field name="deletedAt" type="datetime_immutable" column="deleted_at" nullable="true"/>

        <unique-constraints>
            <unique-constraint name="user_email_unique" columns="email"/>
        </unique-constraints>
    </entity>

</doctrine-mapping>
# ValueObject.UserEmail.orm.xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <embeddable name="App\User\User\Domain\ValueObject\UserEmail">
        <field name="value" type="string" column="email" length="255"/>
    </embeddable>

</doctrine-mapping>
# ValueObject.UserIsDeleted.orm.xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <embeddable name="App\User\User\Domain\ValueObject\UserIsDeleted">
        <field name="value" type="boolean" column="is_deleted"/>
    </embeddable>

</doctrine-mapping>
# ValueObject.UserIsEmailVerified.orm.xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <embeddable name="App\User\User\Domain\ValueObject\UserIsEmailVerified">
        <field name="value" type="boolean" column="is_email_verified"/>
    </embeddable>

</doctrine-mapping>
# ValueObject.UserName.orm.xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <embeddable name="App\User\User\Domain\ValueObject\UserName">
        <field name="value" type="string" column="name" length="255"/>
    </embeddable>

</doctrine-mapping>
# ValueObject.UserPasswordHash.orm.xml
<?xml version="1.0" encoding="utf-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <embeddable name="App\User\User\Domain\ValueObject\UserPasswordHash">
        <field name="value" type="string" column="password" length="255"/>
    </embeddable>

</doctrine-mapping>

```

Ahora para probar que todo funciona, entramos en la terminal de nuestro docker y podemos usar el comando docker compose exec php bash o make bash si podéis usar el Makefile. Dentro de ella lanza el siguiente comando: 

php bin/console doctrine:schema:validate

Si todo ha ido bien debería apareceros un mensaje como este:

Mapping
-------

                                                                                                                        
 [OK] The mapping files are correct.                                                                                    
                                                                                                                        

Database
--------

                                                                                                                        
 [ERROR] The database schema is not in sync with the current mapping file.                                              
                                                                                                                        
El siguiente paso es lanzar el comando que crea la migración. Esto es un archivo con el código SQL para crear los cambios que no se hayan aplicado hasta ahora en la base de datos:

php bin/console doctrine:migrations:diff

Por último, usaremos el siguiente comando para crear la tabla:

php bin/console doctrine:migrations:migrate

Ahora podéis conectaros con algún cliente que sirva para MySQL y confirmar la existencia de la tabla.

También podemos borrar ya las carpetas Entity y Repository.

## Camino hacia el servicio

Ahora vamos a encaminarnos hacia la parte de crear el usuario. En esta parte vamos a usar CQRS. Si no lo recuerdas, CQRS se basa en separar las consultas (recuperar información) de los comandos (insertar, actualizar y borrar). Normalmente se utiliza para usar bases de datos más rápidas en las consultas (como NoSQL) y de tipo más estable en los comandos. Como tampoco necesitamos complicarnos de más en este ejemplo, solo vamos a usar MySQL, pero os dejo la explicación para que entendáis el concepto.

Para gestionar los comandos, primero crearemos unas interfaces que luego usaremos para nuestros comandos.

Primero creamos la carpeta src\Shared\Domain\Bus\Command.

Y dentro de ella estará el archivo Command.php

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Command;

interface Command
{
}
```

Después crearemos un archivo llamado CommandBus.php

```php
<?php

declare(strict_types=1);

namespace Udemy\Shared\Domain\Bus\Command;

interface CommandBus
{
    public function dispatch(Command $command): void;
}
```

Y por último crearemos el archivo CommandHandler.php

```php
<?php

declare(strict_types=1);

namespace Udemy\Shared\Domain\Bus\Command;

interface CommandHandler
{
    public function handle(Command $command): void;
}
```

El siguiente paso es crear el siguiente directorio src\User\User\Domain\Repository en el cual crearemos un archivo llamado UserRepository.php. Este será una interfaz con los métodos que usaremos para trabajar con la base de datos.

```php
<?php

declare(strict_types=1);

namespace App\User\User\Domain\Repository;

use App\User\User\Domain\User;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function findByEmail(UserEmail $email): ?User;

    public function findById(UserId $id): ?User;

    public function findAll(): array;
}
```

Después creamos el archivo src/User/User/Infrastructure/Persistence/Doctrine/DoctrineUserRepository.php el cual ya sí contiene los métodos con los que trabajaremos con la base de datos con Doctrine.

```php
<?php

declare(strict_types=1);

namespace App\User\User\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\User;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserId;

final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findByEmail(UserEmail $email): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email.value' => $email->value()]);
    }

    public function findById(UserId $id): ?User
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->find($id->value());
    }

    public function findAll(): array
    {
        return $this->entityManager
            ->getRepository(User::class)
            ->findAll();
    }
}


```

Una vez hecho esto, creamos el siguiente archivo src/User/User/Application/Service/CreateUserService.php. Aquí es la primera vez que usamos la capa de aplicación, la cual recuerda que es la que hace de nexo de unión entre el dominio y la infrastructura.

```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Service;

use App\Shared\Domain\Ulid;
use App\User\User\Domain\User;
use App\User\User\Domain\ValueObject\UserIsDeleted;
use App\User\User\Domain\ValueObject\UserIsEmailVerified;
use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\ValueObject\UserId;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserName;
use App\User\User\Domain\ValueObject\UserPasswordHash;

final class CreateUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(
        string $email,
        string $password,
        string $name,
    ): void {

        $user = new User(
            new UserId(Ulid::random()->value()),
            new UserEmail($email),
            new UserPasswordHash(password_hash($password, PASSWORD_DEFAULT)), // Hash temporal, solo para hashear
            new UserName($name),
            UserIsEmailVerified::notVerified(),
            UserIsDeleted::notDeleted(),
        );

        $this->userRepository->save($user);
    }
}

```

Como ves, usamos la inyección de dependencias. No usamos DoctrineUserRepository, así si cambia el servicio de base de datos a otro ORM o utilizamos un sistema distinto en nuestros tests, solo tenemos que cambiar la configuración.

Después simplemente creamos una instancia del usuario y lo persistimos en la base de datos.

Ahora abrimos el archivo config/services.yaml, para inyectar el servicio al final del todo añadimos las siguientes dos líneas para inyectar la dependencia:

```yaml
    # User Repository configuration
    App\User\User\Domain\Repository\UserRepository:
        alias: App\User\User\Infrastructure\Persistence\Doctrine\DoctrineUserRepository
```

De tal modo que quedaría así:

```yaml
parameters:

services:
    # default configuration for services in *this* file
    _defaults:
        autowire: true      # Automatically injects dependencies in your services.
        autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.

    # makes classes in src/ available to be used as services
    # this creates a service per class whose id is the fully-qualified class name
    App\:
        resource: '../src/'

    # add more service definitions when explicit configuration is needed
    # please note that last definitions always *replace* previous ones

    # User Repository configuration
    App\User\User\Domain\Repository\UserRepository:
        alias: App\User\User\Infrastructure\Persistence\Doctrine\DoctrineUserRepository
```

Ahora vamos a crear el comando en concreto para la creación del usuario. Para ello creamos la carpeta src\User\User\Application\Command y el archivo CreateUserCommand.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;

final class CreateUserCommand implements Command
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $name,
    ) {
    }
}

```

Después en esta misma carpeta crearemos el Handler llamado CreateUserCommandHandler.php:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Command;

use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Bus\Command\CommandHandler;
use App\User\User\Application\Service\CreateUserService;

final class CreateUserCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly CreateUserService $CreateUserService
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
```

Después volvemos al archivo config/services.yaml y añadimos las siguientes líneas:

```yaml

    App\User\User\Application\Command\CreateUserCommandHandler:
        tags:
            - { name: 'messenger.message_handler', handles: 'App\User\User\Application\Command\CreateUserCommand' }
```

De esta manera, Symfony podrá gestionar el mensaje ya que usaremos un componente de la librería symfony/messenger para gestionar los mensajes.

Ahora vamos a crear un archivo llamado src/Shared/Infrastructure/Bus/Command/SymfonyCommandBus.php

```php
<?php

namespace App\Shared\Infrastructure\Bus\Command;

use Symfony\Component\Messenger\MessageBusInterface;
use App\Shared\Domain\Bus\Command\Command;
use App\Shared\Domain\Bus\Command\CommandBus;

final class SymfonyCommandBus implements CommandBus
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function dispatch(Command $command): void
    {
        $this->messageBus->dispatch($command);
    }
}
```

Y lo registramos en el config/services.yaml

```yaml
    App\Shared\Domain\Bus\Command\CommandBus:
        class: App\Shared\Infrastructure\Bus\Command\SymfonyCommandBus
```


Cambiar el controlador

```php
<?php


namespace App\User\User\Infrastructure\HTTP\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Infrastructure\Api\SymfonyApiResponse;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\User\Application\Command\CreateUserCommand;

class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus
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

        try {
            $this->commandBus->dispatch($command);
        } catch (\Exception $e) {
            return SymfonyApiResponse::createErrorResponse(
                'Error to create user',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        return SymfonyApiResponse::createSuccessResponse(null, 'User created successfully',Response::HTTP_CREATED);
    }
}
```

Listo. Lanzad una petición por Postman o cualquier otro servicio enviando el email, password y name y debería crearos el usuario sin problema 💪.

Una vez hecho esto, vamos a mejorarlo y de paso vemos nuevos conceptos sobre lo que estamos tratando. Nos faltan dos cosas en el servicio para crear el usuario que sería comprobar si el email del usuario ya está en uso y luego enviar un email para confirmar el usuario.

Pregunta ¿Podríamos añadir ambas cosas en CreateUserService?

Noooooo.

El problema para el primer caso que es el que vamos a resolver primero es que un email sea único es una regla de negocio por lo que debería estar en el dominio y no en la aplicación. Esto lo vamos a resolver con los servicios de dominio.

Para ello, primero vamos a crear un archivo llamado src/User/User/Domain/Service/UserEmailUniquenessChecker.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Domain\Service;

use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\ValueObject\UserEmail;

final class UserEmailUniquenessChecker
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function ensureEmailIsUnique(UserEmail $email): void
    {
        $existingUser = $this->userRepository->findByEmail($email);

        if ($existingUser !== null) {
            throw new \DomainException(
                sprintf('User with email "%s" already exists', $email->value())
            );
        }
    }
}
```

Como ves, creamos una clase con un método para validar si el email ya está en uso:

Ahora abrimos el dominio de User el cual se encuentra en src\User\User\Domain\User.php y creamos este nuevo método:

```php
use App\User\User\Domain\Service\UserEmailUniquenessChecker;

# Código ya existente

public static function create(
        UserId $id,
        UserEmail $email,
        UserPasswordHash $password,
        UserName $name,
        UserEmailUniquenessChecker $emailUniquenessChecker
    ): self {
        $emailUniquenessChecker->ensureEmailIsUnique($email);

        $user = new self($id, $email, $password, $name, $avatarUrl);
        return $user;
    }
```

En vez de crear el usuario mediante una instancia de User como hacíamos hasta ahora, lo hacemos desde una función estática y ahí validamos que no esté el email en uso, de esta manera lo aislamos de la capa de aplicación y lo tendremos disponible si necesitamos crear un usuario desde otro servicio distinto.

Ahora nuestro servicio debería quedar de la siguiente forma:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Service;

use App\Shared\Domain\Ulid;
use App\User\User\Domain\User;
use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\ValueObject\UserId;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserName;
use App\User\User\Domain\ValueObject\UserPasswordHash;
use App\User\User\Domain\Service\UserEmailUniquenessChecker;

final class CreateUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserEmailUniquenessChecker $userEmailUniquenessChecker,
    ) {
    }

    public function __invoke(
        string $email,
        string $password,
        string $name,
    ): void {

        $user = User::create(
            new UserId(Ulid::random()->value()),
            new UserEmail($email),
            new UserPasswordHash(password_hash($password, PASSWORD_DEFAULT)),
            new UserName($name),
            $this->userEmailUniquenessChecker,
        );

        $this->userRepository->save($user);
    }
}

```

Antes de probar, modificamos el controller para ahora sí capturar los errores:

```php
<?php


namespace App\User\User\Infrastructure\HTTP\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Infrastructure\Api\SymfonyApiResponse;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\User\Application\Command\CreateUserCommand;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus
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

        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            // Symfony Messenger envuelve las excepciones en HandlerFailedException
            // Buscamos si hay una DomainException en las excepciones envueltas
            $wrappedExceptions = $e->getWrappedExceptions();
            foreach ($wrappedExceptions as $wrappedException) {
                if ($wrappedException instanceof \DomainException) {
                    return SymfonyApiResponse::createErrorResponse(
                        $wrappedException->getMessage(),
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
            
            throw $e;
        } catch (\Exception $e) {
            return SymfonyApiResponse::createErrorResponse(
                'Error to create user',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        return SymfonyApiResponse::createSuccessResponse(null, 'User created successfully',Response::HTTP_CREATED);
    }
}
```

Como Symfony nos encapsula dentro de HandlerFailedException la DomainException que lanzamos por el email ya existente, tuve que hacer así el proceso para ver si es una excepción controlada o no. Recuerda que es muy importante no enviar información comprometida al usuario porque eso puede afectar a la seguridad del proyecto.

Tener que controlar una excepción en cada controlador me parece ensuciar mucho el código así que vamos a añadir una mejora. Esto no tiene que ver con lo que estamos tratando directamente pero como me gusta hacer las cosas bien vamos a hacer una implementación para que cuando tengamos una excepción pase por un middleware, si es una excepción controlada como esta la devolvemos sin más y si es una excepción no controlada, le enviamos un mensaje genérico al usuario y ya nosotros podemos guardar en un logger, enviarla a un canal de slack o lo que sea para corregirla.

Creamos el archivo src/Shared/Infrastructure/Http/EventListener/ExceptionListener.php y añadimos el siguiente código:

```php
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
        private readonly bool $isDebug
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
```

Básicamente intercepta la excepción y ya decidimos nosotros qué mensaje enviar al usuario y si queremos guardar información en el log o no que por ejemplo en un error no controlado sería muy interesante para resolverlo.

Para que funcione, debemos añadirlo a config/services.yaml:

```yaml
    # Exception Listener configuration
    App\Shared\Infrastructure\Http\EventListener\ExceptionListener:
        arguments:
            $isDebug: '%kernel.debug%'
        tags:
            - { name: 'kernel.event_listener', event: 'kernel.exception', priority: -10 }
```

Editamos el controlador borrando el control de excepciones y como puedes ver ahora está mucho más limpio ¿verdad?

```php
<?php


namespace App\User\User\Infrastructure\HTTP\V1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Shared\Infrastructure\Api\SymfonyApiResponse;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\User\Application\Command\CreateUserCommand;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus
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

        return SymfonyApiResponse::createSuccessResponse(null, 'User created successfully',Response::HTTP_CREATED);
    }
}
```

Siguiente punto; queremos enviar un email para activar la cuenta, ¿Donde lo añadimos? ¿En el servicio de creación de usuario? Otra vez noooo. No podemos porque nos pasaría igual que con la validación del email, estamos rompiendo el principio de responsabilidad única. Entonces ¿Cómo resolvemos esto?

Bien, para eso tenemos los eventos. El evento se lanza de forma asíncrona por Rabbitmq y es independiente de la petición que hizo el usuario para crear su usuario, entonces, podemos responder antes al usuario y si por lo que sea hay un error al enviar el email, Rabbitmq lo detectará como erróneo y lo reintentará y según la configuración, podremos guardar los mensajes erróneos, resolver un fix y lanzar esos mensajes de nuevo.

Como no es un tutorial de Rabbitmq te explicaré brevemente qué es. RabbitMQ es un broker de mensajes de código abierto que implementa el protocolo AMQP (Advanced Message Queuing Protocol). Su función principal es recibir, almacenar y enviar mensajes entre diferentes aplicaciones o microservicios de forma asíncrona, actuando como un intermediario fiable.

Entonces nosotros enviamos un mensaje o este caso un evento para que ejecute el envio del email y ya RabbitMQ se encarga de ello.

Una vez dicho esto, vamos a crear las clases para gestionar los eventos. Para ello crearemos la carpeta src/Shared/Domain/Bus/Event/ y el primer archivo que vamos a crear se llamará DomainEvent.php y su contenido será el siguiente:

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

use App\Shared\Domain\Ulid;

abstract class DomainEvent
{
    private readonly string $eventId;
    private readonly string $occurredOn;

    public function __construct(
        private readonly string $aggregateId,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        $this->eventId = $eventId ?? Ulid::random()->value() ?? throw new \InvalidArgumentException('Either eventId or Ulid must be provided');
        $this->occurredOn = $occurredOn ?? (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
    }

    abstract public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn
    ): self;

    abstract public static function eventName(): string;

    abstract public function toPrimitives(): array;

    final public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    final public function eventId(): string
    {
        return $this->eventId;
    }

    final public function occurredOn(): string
    {
        return $this->occurredOn;
    }
}
```

Este archivo contiene el aggregateId que sería un identificador, para el caso que vamos a hacer más adelante sería el id de usuario, un id de evento y la fecha en la que ocurrió.

Después creamos el archivo EventBus.php.

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Event;

interface EventBus
{
    public function publish(DomainEvent ...$events): void;
}
```

Y por último ya en la carpeta src/Shared/Infrastructure/Bus/Event, el archivo SymfonyEventBus.php

```php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;
use App\Shared\Domain\Bus\Event\EventBus;
use Symfony\Component\Messenger\MessageBusInterface;

final class SymfonyEventBus implements EventBus
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->messageBus->dispatch($event);
        }
    }
}
```

Aquí como ves, el método publish se encarga de despachar los distintos eventos que tengamos.

El siguiente paso es crear unas clases para gestionar el envío de los emails.

Para ello, vamos a crear una carpeta llamada src\Shared\Domain\Service\Email que contendrá el archivo EmailAttachment.php y EmailMessage.php

```php
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
        private readonly ?string $contentType = null
    ) {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("File does not exist: {$path}");
        }

        if (! is_readable($path)) {
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
```

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service\Email;

final class EmailMessage
{
    /**
     * @param string[]          $to          Direcciones de email destinatarias
     * @param string            $from        Dirección de email del remitente
     * @param string            $fromName    Nombre del remitente
     * @param string            $subject     Asunto del email
     * @param string            $body        Cuerpo del email (puede ser HTML o texto plano)
     * @param string[]          $cc          Direcciones de email con copia (opcional)
     * @param string[]          $bcc         Direcciones de email con copia oculta (opcional)
     * @param EmailAttachment[] $attachments Archivos adjuntos (opcional)
     */
    public function __construct(
        private readonly array $to,
        private readonly string $from,
        private readonly string $fromName,
        private readonly string $subject,
        private readonly string $body,
        private readonly array $cc = [],
        private readonly array $bcc = [],
        private readonly array $attachments = [],
        private readonly bool $isHtml = true
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->to)) {
            throw new \InvalidArgumentException('At least one recipient (to) is required');
        }

        foreach ($this->to as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email address in 'to': {$email}");
            }
        }

        if (! filter_var($this->from, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email address in 'from': {$this->from}");
        }

        foreach ($this->cc as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email address in 'cc': {$email}");
            }
        }

        foreach ($this->bcc as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid email address in 'bcc': {$email}");
            }
        }
    }

    public function to(): array
    {
        return $this->to;
    }

    public function from(): string
    {
        return $this->from;
    }

    public function fromName(): string
    {
        return $this->fromName;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function cc(): array
    {
        return $this->cc;
    }

    public function bcc(): array
    {
        return $this->bcc;
    }

    /**
     * @return EmailAttachment[]
     */
    public function attachments(): array
    {
        return $this->attachments;
    }

    public function isHtml(): bool
    {
        return $this->isHtml;
    }
}
```

Y dentro de la carpeta src\Shared\Domain\Service crearemos un archivo llamado EmailService.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Service;

use App\Shared\Domain\Service\Email\EmailMessage;

interface EmailService
{
    /**
     * Envía un email.
     *
     * @param EmailMessage $emailMessage El mensaje de email a enviar
     *
     * @throws \RuntimeException Si ocurre un error al enviar el email
     */
    public function send(EmailMessage $emailMessage): void;
}
```

Para finalizar el sistema de envío de emails, crearemos el archivo src/Shared/Infrastructure/Email/SymfonyEmailService.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Email;

use App\Shared\Domain\Service\Email\EmailMessage;
use App\Shared\Domain\Service\EmailService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final class SymfonyEmailService implements EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {
    }

    public function send(EmailMessage $emailMessage): void
    {
        $email = (new Email())
            ->from(new Address($emailMessage->from(), $emailMessage->fromName()))
            ->to(...$this->convertToAddresses($emailMessage->to()))
            ->subject($emailMessage->subject());

        // Añadir CC si existe
        if (! empty($emailMessage->cc())) {
            $email->cc(...$this->convertToAddresses($emailMessage->cc()));
        }

        // Añadir BCC si existe
        if (! empty($emailMessage->bcc())) {
            $email->bcc(...$this->convertToAddresses($emailMessage->bcc()));
        }

        // Configurar el cuerpo del email (HTML o texto plano)
        if ($emailMessage->isHtml()) {
            $email->html($emailMessage->body());
        } else {
            $email->text($emailMessage->body());
        }

        // Añadir archivos adjuntos
        foreach ($emailMessage->attachments() as $attachment) {
            $email->attachFromPath(
                $attachment->path(),
                $attachment->name(),
                $attachment->contentType()
            );
        }

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to send email: {$e->getMessage()}", previous: $e);
        }
    }

    /**
     * Convierte un array de strings de email a un array de objetos Address.
     *
     * @param string[] $emails
     *
     * @return Address[]
     */
    private function convertToAddresses(array $emails): array
    {
        return array_map(
            fn (string $email) => new Address($email),
            $emails
        );
    }
}
```

Por último, añadimos la inyección de la dependencia en config/services.yaml.

```yaml
    # Email Service configuration
    App\Shared\Domain\Service\EmailService:
        class: App\Shared\Infrastructure\Email\SymfonyEmailService
```

Ahora vamos a crear el evento que queremos lanzar cuando se cree un usuario que se llamará src/User/User/Domain/Event/UserCreated.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final class UserCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        private readonly string $email,
        private readonly string $name,
        string $eventId,
        string $occurredOn
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'user.created';
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function toPrimitives(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn
    ): self {
        return new self(
            $aggregateId,
            $body['email'],
            $body['name'],
            $eventId,
            $occurredOn
        );
    }
}
```

Después creamos el archivo src/User/User/Application/EventHandler/SendUserConfirmationEmailHandler.php que ya será el que se encargue de enviar el email, el evento propiamente dicho:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\EventHandler;

use App\Shared\Domain\Service\Email\EmailMessage;
use App\Shared\Domain\Service\EmailService;
use App\User\User\Domain\Event\UserCreated;

final class SendUserConfirmationEmailHandler
{
    public function __construct(
        private readonly EmailService $emailService
    ) {
    }

    public function __invoke(UserCreated $event): void
    {
        $emailMessage = new EmailMessage(
            to: [$event->email()],
            from: 'noreply@example.com',
            fromName: 'Mi Aplicación',
            subject: 'Bienvenido - Confirma tu cuenta',
            body: $this->buildEmailBody($event->name()),
            isHtml: true
        );

        $this->emailService->send($emailMessage);
    }

    private function buildEmailBody(string $userName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f4f4f4; line-height: 1.6;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #667eea; padding: 40px 20px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: 600;">¡Bienvenido!</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="font-size: 18px; color: #333333; margin: 0 0 20px 0;">
                                Hola <strong style="color: #667eea;">{$userName}</strong>,
                            </p>
                            <p style="color: #666666; font-size: 16px; margin: 0 0 20px 0;">
                                Gracias por registrarte en nuestra aplicación. Estamos emocionados de tenerte con nosotros.
                            </p>
                            <p style="color: #666666; font-size: 16px; margin: 0 0 30px 0;">
                                Para completar tu registro y comenzar a disfrutar de todos nuestros servicios, por favor confirma tu cuenta haciendo clic en el botón de abajo.
                            </p>
                            <!-- Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="#" style="display: inline-block; padding: 14px 40px; background-color: #667eea; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 16px;">Confirmar mi cuenta</a>
                                    </td>
                                </tr>
                            </table>
                            <!-- Divider -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td style="padding: 30px 0;">
                                        <div style="height: 1px; background-color: #e0e0e0;"></div>
                                    </td>
                                </tr>
                            </table>
                            <!-- Footer -->
                            <p style="color: #999999; font-size: 14px; text-align: center; margin: 0; line-height: 1.5;">
                                Si no has solicitado esta cuenta, puedes ignorar este email de forma segura.<br>
                                Si tienes alguna pregunta, no dudes en <a href="#" style="color: #667eea; text-decoration: none;">contactarnos</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
```

Y ahora vamos al archivo config/services.yaml y añadimos el SymfonyEventBus e indicamos el evento que se debe lanzar cuando se publique el evento de UserCreated:

```yaml

    # Event Bus configuration
    App\Shared\Domain\Bus\Event\EventBus:
        class: App\Shared\Infrastructure\Bus\Event\SymfonyEventBus

    # Event Handlers configuration
    App\User\User\Application\EventHandler\SendUserConfirmationEmailHandler:
        tags:
            - { name: 'messenger.message_handler', handles: 'App\User\User\Domain\Event\UserCreated' }
```

También vamos a crear una clase en la carpeta src/Shared/Domain/Aggregate/ llamada AggregateRoot.php que será una clase compartida por todas las clases de dominio y tendrá las funciones para añadir eventos y devolverlos.

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Aggregate;

use App\Shared\Domain\Bus\Event\DomainEvent;

abstract class AggregateRoot
{
    private array $domainEvents = [];

    final public function pullDomainEvents(): array
    {
        $domainEvents = $this->domainEvents;
        $this->domainEvents = [];

        return $domainEvents;
    }

    final protected function record(DomainEvent $domainEvent): void
    {
        $this->domainEvents[] = $domainEvent;
    }
}
```

Modificar User para que extienda de agregateroot, en el metodo create añadimos el evento

```php
use App\Shared\Domain\Ulid;
use App\User\User\Domain\Event\UserCreated;
use App\Shared\Domain\Aggregate\AggregateRoot;
...
class User extends AggregateRoot {
    ...
    public static function create(
        UserId $id,
        UserEmail $email,
        UserPasswordHash $password,
        UserName $name,
        UserEmailUniquenessChecker $emailUniquenessChecker
    ): self {
        $emailUniquenessChecker->ensureEmailIsUnique($email);

        $user = new self($id, $email, $password, $name, UserIsEmailVerified::notVerified(), UserIsDeleted::notDeleted());
        
        $user->record(new UserCreated(
            $id->value(),
            $email->value(),
            $name->value(),
            Ulid::random()->value(),
            (new DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        ));
        
        return $user;
    }
```

Como ves, ahora cada vez que creamos un usuario, guardamos el evento UserCreated para ser lanzado más adelante, si necesitásemos más eventos al crear el usuario, podríamos añadirlos sin problema.

Por último, editamos el archivo src\User\User\Application\Service\CreateUserService.php para que justo después de crear el usuario, se publiquen nuestros eventos.

```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Service;

use App\Shared\Domain\Ulid;
use App\User\User\Domain\User;
use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\ValueObject\UserId;
use App\User\User\Domain\ValueObject\UserEmail;
use App\User\User\Domain\ValueObject\UserName;
use App\User\User\Domain\ValueObject\UserPasswordHash;
use App\User\User\Domain\Service\UserEmailUniquenessChecker;
use App\Shared\Domain\Bus\Event\EventBus;

final class CreateUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserEmailUniquenessChecker $userEmailUniquenessChecker,
        private readonly EventBus $eventBus,
    ) {
    }

    public function __invoke(
        string $email,
        string $password,
        string $name,
    ): void {

        $user = User::create(
            new UserId(Ulid::random()->value()),
            new UserEmail($email),
            new UserPasswordHash(password_hash($password, PASSWORD_DEFAULT)),
            new UserName($name),
            $this->userEmailUniquenessChecker,
        );

        $this->userRepository->save($user);

        $domainEvents = $user->pullDomainEvents();
        $this->eventBus->publish(...$domainEvents);
    }
}
```

Para usar RabbitMQ, debemos editar el archivo config/packages/messenger.yaml y añadir el siguiente código:

```yaml
framework:
    messenger:
        # Uncomment this (and the failed transport below) to send failed messages to this transport for later handling.
        # failure_transport: failed

        transports:
            # https://symfony.com/doc/current/messenger.html#transport-configuration
            # async: '%env(MESSENGER_TRANSPORT_DSN)%'
            # failed: 'doctrine://default?queue_name=failed'
            sync: 'sync://'
            async_events:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    delay: 1000
                    multiplier: 2
                    max_delay: 0
        routing:
            # Los comandos se ejecutan de forma síncrona
            'App\Shared\Domain\Bus\Command\Command': sync
            
            'App\Shared\Domain\Bus\Event\DomainEvent': async_events
            'App\User\User\Domain\Event\UserCreated': async_events

```

De esta forma, todos los eventos se publicarán de forma asíncrona y usando RabbitMQ.

Antes de probar, tenemos que poner en marcha el worker, para ello, desde la línea de comandos podéis lanzar make workers si estáis desde linux o docker compose exec php php bin/console messenger:consume async_events -vv, esperad a que se muestre algo así y entonces ya podéis crear un usuario:

```yaml

 [OK] Consuming messages from transport "async_events".                                                                 
                                                                                                                        
 // The worker will automatically exit once it has received a stop signal via the messenger:stop-workers command.       

 // Quit the worker with CONTROL-C.
```

Cread el usuario y si todo ha ido bien, en la consola donde ejecutasteis el worker veréis que se lanzó el evento y si vais a http://localhost:8025/ podréis ver el email.

Ya en esta última parte del tutorial, vamos a crear un endpoint esta vez que cumpla con Query de CQRS que sería para devolver datos. Al igual que hicimos con el Command, vamos a crear las clases base que necesitaremos. Para ello, creamos la carpeta y dentro crearemos los siguientes archivos:

src/Shared/Domain/Bus/Query/Query.php

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Query;

interface Query
{
}
```

src/Shared/Domain/Bus/Query/QueryHandler.php

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Query;

interface QueryHandler
{
}
```
src/Shared/Domain/Bus/Query/Response.php

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Query;

use App\Shared\Domain\DTO\DTO;

interface Response
{
    public function toDTO(): DTO;
}
```
src/Shared/Domain/Bus/Query/QueryBus.php

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\Bus\Query;

interface QueryBus
{
    public function ask(Query $query): ?Response;
}
```

Ahora creamos la carpeta src/Shared/Domain/DTO/ y dentro de ella crearemos un archivo llamado DTO.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\Shared\Domain\DTO;

interface DTO
{
    public function toArray(): array;
}
```

Esta clase nos servirá de base para los distintos DTOs que creemos. Un DTO viene de las siglas Data Transfer Object y lo utilizaremos para serializar una clase, por ejemplo la del dominio User, convertir sus datos a un tipo primitivo y de esta forma poderlos transmitir entre distintas aplicaciones, en nuestro caso sería para poder devolver por la api los datos en primitivo y así los pueda interpretar nuestro Front u otro Back.

El siguiente paso es añadir el nuevo endpoint. Para ello vamos a config\routes\User\user.yaml y lo añadimos:

```yaml
create_user:
    path: /users
    controller: App\User\User\Infrastructure\HTTP\V1\CreateUserController
    methods:  [POST]

get_user:
    path: /users/{id}
    controller: App\User\User\Infrastructure\HTTP\V1\GetUserController
    methods:  [GET]
```

Ahora vamos a crear la carpeta src/User/User/Application/Query/ y dentro de ella añadiremos los siguientes archivos:

src/User/User/Application/Query/GetUserQuery.php
```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Query;

use App\Shared\Domain\Bus\Query\Query;

final class GetUserQuery implements Query
{
    public function __construct(
        public readonly string $id,
    ) {
    }
}
```

src/User/User/Application/Query/GetUserQueryHandler.php
```php
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
        private readonly GetUserService $getUserService
    ) {
    }

    public function __invoke(GetUserQuery $query): ?Response
    {
        return $this->handle($query);
    }

    public function handle(Query $query): ?Response
    {
        if (! $query instanceof GetUserQuery) {
            throw new \InvalidArgumentException('Query must be an instance of GetUserQuery');
        }

        return $this->getUserService->__invoke($query->id);
    }
}
```

src/User/User/Application/Query/GetUserResponse.php
```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Query;

use App\Shared\Domain\Bus\Query\Response;
use App\Shared\Domain\DTO\DTO;
use App\User\User\Domain\DTO\UserDTO;

final class GetUserResponse implements Response
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $name,
        private readonly bool $isEmailVerified,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toDTO(): DTO
    {
        return UserDTO::fromPrimitives(
            $this->id,
            $this->email,
            $this->name,
            $this->isEmailVerified,
            $this->createdAt,
            $this->updatedAt,
        );
    }
}
```

Ahora creamos el archivo src\User\User\Domain\DTO\UserDTO.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Domain\DTO;

use App\Shared\Domain\DTO\DTO;

final class UserDTO implements DTO
{
    public function __construct(
        private readonly string $id,
        private readonly string $email,
        private readonly string $name,
        private readonly bool $isEmailVerified,
        private readonly \DateTimeImmutable $createdAt,
        private readonly \DateTimeImmutable $updatedAt,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public static function fromPrimitives(
        string $id,
        string $email,
        string $name,
        bool $isEmailVerified,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $email,
            $name,
            $isEmailVerified,
            $createdAt,
            $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'email' => $this->email(),
            'name' => $this->name(),
            'isEmailVerified' => $this->isEmailVerified(),
            'createdAt' => $this->createdAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
```

Y por último crearemos el servicio para retornar el usuario. Para ello creamos el archivo src/User/User/Application/Service/GetUserService.php con el siguiente contenido:

```php
<?php

declare(strict_types=1);

namespace App\User\User\Application\Service;

use App\User\User\Application\Query\GetUserResponse;
use App\User\User\Domain\Repository\UserRepository;
use App\User\User\Domain\ValueObject\UserId;

final class GetUserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function __invoke(string $id): ?GetUserResponse
    {
        $userId = new UserId($id);
        $user = $this->userRepository->findById($userId);

        if (null === $user) {
            return null;
        }

        return new GetUserResponse(
            $user->id()->value(),
            $user->email()->value(),
            $user->name()->value(),
            $user->isEmailVerified()->value(),
            $user->createdAt(),
            $user->updatedAt(),
        );
    }
}
```

También debemos crear el QueryBus en infrastructura para Symfony como hicimos con el comando así que creamos el archivo src\Shared\Infrastructure\Bus\Query\SymfonyQueryBus.php que tendrá el siguiente contenido:

```php
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
        private readonly MessageBusInterface $messageBus
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
```

Como hicimos con el comando, abrimos el archivo config/services.yaml y añadimos el Handler del user y el QueryBus de Symfony:

```yaml
    # Query Bus configuration
    App\Shared\Domain\Bus\Query\QueryBus:
        class: App\Shared\Infrastructure\Bus\Query\SymfonyQueryBus

    # Query Handlers configuration
    App\User\User\Application\Query\GetUserQueryHandler:
        tags:
            - { name: 'messenger.message_handler', handles: 'App\User\User\Application\Query\GetUserQuery' }
```

Abrimos el archivo config\packages\messenger.yaml e indicamos que Query se ejecutará de manera síncrona:

```yaml
        routing:
            # Los comandos se ejecutan de forma síncrona
            'App\Shared\Domain\Bus\Command\Command': sync
            
            # Las queries se ejecutan de forma síncrona
            'App\Shared\Domain\Bus\Query\Query': sync
```

Por último, crearemos el controlador. Para ello creamos el archivo src\User\User\Infrastructure\HTTP\V1\GetUserController.php con el siguiente contenido:

```php
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
        private readonly QueryBus $queryBus
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
```

Para probarlo, puedes entrar en tu base de datos, vas a la tabla de usuarios y escoges uno de los ids creados, luego solo debes hacer una petición GET con Postman, Curl o la herramienta que prefieras a la url http://localhost:8080/v1/users/{id_user} y ya debería funcionar todo correctamente.