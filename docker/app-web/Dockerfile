FROM php:7.2-fpm

RUN apt-get update && apt-get install -y librabbitmq-dev libssh-dev libmemcached-dev wget
RUN pecl install amqp
RUN docker-php-ext-enable amqp
RUN pecl install memcached
RUN docker-php-ext-enable memcached
RUN docker-php-ext-install pdo_mysql

ENV DOCKERIZE_VERSION v1.2.0
RUN wget https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

WORKDIR /app

CMD dockerize -wait tcp://rabbitmq:5672 -timeout 30s -wait tcp://mysql:3306 -timeout 30s php-fpm
