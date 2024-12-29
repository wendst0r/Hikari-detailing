FROM php:latest

RUN apt-get update

# Install Postgre PDO
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html/public"]
