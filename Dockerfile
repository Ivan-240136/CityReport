FROM php:8.2-apache

RUN apt-get update && \
  apt-get install -y \
    libpq-dev \
    libmariadb-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    curl \
  && docker-php-ext-install pdo pdo_pgsql pdo_mysql curl \
  && a2enmod rewrite && \
  apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo 'precedence ::ffff:0:0/96  100' >> /etc/gai.conf

ENV APACHE_DOCUMENT_ROOT /var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock* ./

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist || composer install --no-dev --optimize-autoloader --no-interaction

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

ENV PORT 10000
RUN sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf && \
    sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

EXPOSE ${PORT}

CMD ["apache2-foreground"]

