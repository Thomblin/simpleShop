FROM php:5-apache

RUN apt-get update && \
    apt-get install -y mysql-client

RUN docker-php-ext-install mysqli