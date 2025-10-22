FROM php:8.2-apache

# Instalar extensiones requeridas (PostgreSQL)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Copiar archivos del proyecto
WORKDIR /var/www/html
COPY . .

# Exponer puerto web
EXPOSE 80
