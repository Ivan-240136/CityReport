FROM php:8.2-apache

# Instala las dependencias necesarias para PostgreSQL (PDO_PGSQL)
RUN apt-get update \
  && apt-get install -y libpq-dev \
  && docker-php-ext-install pdo pdo_pgsql \
  && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf /etc/apache2/conf-available/*.conf

COPY . /var/www/html

ENV PORT 10000
RUN sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf && \
    sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

CMD ["apache2-foreground"]
