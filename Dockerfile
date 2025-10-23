FROM php:8.2-apache

RUN apt-get update \
  && apt-get install -y --no-install-recommends libpq-dev \
  && docker-php-ext-install pdo pdo_pgsql \
  && a2enmod rewrite \
  && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
  /etc/apache2/sites-available/*.conf /etc/apache2/conf-available/*.conf

RUN printf "<Directory /var/www/html>\n    AllowOverride All\n</Directory>\n" \
  > /etc/apache2/conf-available/allow-override.conf && a2enconf allow-override

COPY . /var/www/html

CMD ["bash", "-lc", "\
  PORT=${PORT:-10000}; \
  sed -i \"s/^Listen 80/Listen ${PORT}/\" /etc/apache2/ports.conf; \
  sed -i \"s/:80>/:${PORT}>/\" /etc/apache2/sites-available/000-default.conf; \
  echo 'ServerName 0.0.0.0' >> /etc/apache2/apache2.conf; \
  apache2-foreground \
"]
