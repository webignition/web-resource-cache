FROM php:7.2-fpm

RUN apt-get update

RUN apt-get update && apt-get install -y librabbitmq-dev libssh-dev libmemcached-dev
RUN pecl install amqp
RUN docker-php-ext-enable amqp
RUN pecl install memcached
RUN docker-php-ext-enable memcached
RUN docker-php-ext-install pdo_mysql

WORKDIR /app
