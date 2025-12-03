FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y \
    git \
    zip \
    unzip \
    libicu-dev \
    libzip-dev \
    librabbitmq-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    curl \
    libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    intl \
    opcache \
    pdo \
    pdo_mysql \
    zip \
    bcmath \
    mbstring \
    xml \
    curl \
    exif \
    sockets \
    gd \
    && pecl install amqp apcu \
    && docker-php-ext-enable amqp apcu \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer


WORKDIR /var/www/html

# Copiar archivos del proyecto (excluyendo lo que está en .dockerignore)
COPY . .

# Ejecutar composer install si no existe vendor (durante el build)
RUN if [ ! -d "vendor" ]; then composer install --no-interaction --prefer-dist --optimize-autoloader; fi

# Script de entrada para ejecutar composer install al iniciar el contenedor
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Configuraciones recomendadas de PHP para Symfony
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory.ini \
    && echo "upload_max_filesize=64M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/timezone.ini \
    && echo "max_execution_time=300" > /usr/local/etc/php/conf.d/execution.ini \
    && echo "max_input_time=300" >> /usr/local/etc/php/conf.d/execution.ini

# Configuración de OPcache para desarrollo (valida cambios cada 2 segundos)
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini