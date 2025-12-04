FROM php:8.4-apache

RUN apt-get update && \
    apt-get install -y default-mysql-client

RUN docker-php-ext-install mysqli