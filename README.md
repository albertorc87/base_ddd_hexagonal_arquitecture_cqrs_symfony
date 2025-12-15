# Tutorial de proyecto de ejemplo con DDD, Arquitectura Hexagonal y CQRS

Proyecto de ejemplo que implementa una API RESTful con **Domain Driven Design (DDD)**, **Arquitectura Hexagonal** y **CQRS** utilizando el framework **Symfony 7**.

El proyecto consiste en una herramienta básica de gestión de usuarios diseñada para aprender y aplicar estos conceptos arquitectónicos avanzados.

## Requisitos

- **Docker** (recomendado) - La forma más fácil de ejecutar el proyecto
- O alternativamente:
  - PHP 8.4
  - MySQL
  - RabbitMQ (para gestionar eventos)
  - Mailhog (servicio de pruebas para enviar emails)

## Instalación

1. Clona el repositorio o haz un fork del proyecto
2. Cambia a la rama `base`:
   ```bash
   git checkout base
   ```
3. Si usas Docker (recomendado):
   ```bash
   docker-compose up -d
   ```
   O usando el Makefile:
   ```bash
   make up
   ```

4. (Opcional) Si quieres añadir variables de entorno personalizadas, crea un archivo `.env.dev.local` que sobrescribirá las variables de `.env` y `.env.dev`.

## Tutorial completo

Para seguir el tutorial completo paso a paso, consulta el archivo [TUTORIAL.md](./TUTORIAL.md) o en mi web [https://cosasdedevs.com/posts/api-restfull-symfony-7-ddd-arquitectura-hexagonal-cqrs/](https://cosasdedevs.com/posts/api-restfull-symfony-7-ddd-arquitectura-hexagonal-cqrs/).