FROM php:8.4-apache

RUN apt-get update && \
    apt-get install -y default-mysql-client unzip git

RUN docker-php-ext-install mysqli

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Xdebug for code coverage
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# Configure Xdebug for code coverage
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini