FROM php:7.2-fpm

ARG APP_ENV
ARG APP_SECRET
ARG CALLBACK_ALLOWED_HOSTS
ARG RETRIEVER_TIMEOUT_SECONDS

ENV APP_SECRET=$APP_SECRET
ENV APP_ENV=$APP_ENV
ENV CALLBACK_ALLOWED_HOSTS=$CALLBACK_ALLOWED_HOSTS
ENV RETRIEVER_TIMEOUT_SECONDS=$RETRIEVER_TIMEOUT_SECONDS

RUN mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini-development.bak
RUN mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

RUN apt-get -qq update && apt-get install -y \
        librabbitmq-dev \
        libssh-dev \
        libmemcached-dev \
        wget \
        git \
        libzip-dev \
        unzip \
        zip \
     --no-install-recommends
RUN pecl -q install amqp
RUN pecl -q install memcached
RUN pecl install xdebug
RUN docker-php-ext-configure zip --with-libzip > /dev/null
RUN docker-php-ext-install zip > /dev/null
RUN docker-php-ext-enable amqp > /dev/null
RUN docker-php-ext-enable memcached > /dev/null
RUN docker-php-ext-enable xdebug
RUN docker-php-ext-install pdo_mysql > /dev/null
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV DOCKERIZE_VERSION v1.2.0
RUN wget -q https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

WORKDIR /app

CMD dockerize -wait tcp://rabbitmq:5672 -timeout 30s -wait tcp://mysql:3306 -timeout 30s php-fpm
