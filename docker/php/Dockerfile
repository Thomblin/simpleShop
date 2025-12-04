FROM php:8.4-apache

RUN apt-get update && \
    apt-get install -y default-mysql-client

RUN docker-php-ext-install mysqli

# Install Xdebug for code coverage
RUN pecl install xdebug && \
    docker-php-ext-enable xdebug

# Configure Xdebug for code coverage
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini