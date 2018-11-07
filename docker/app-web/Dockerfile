FROM php:7.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends librabbitmq-dev libssh-dev libmemcached-dev wget git zip
RUN pecl install amqp
RUN docker-php-ext-enable amqp
RUN pecl install memcached
RUN docker-php-ext-enable memcached
RUN docker-php-ext-install pdo_mysql
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV DOCKERIZE_VERSION v1.2.0
RUN wget https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

WORKDIR /app
#RUN curl --silent --show-error https://getcomposer.org/installer | php
#RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD dockerize -wait tcp://rabbitmq:5672 -timeout 30s -wait tcp://mysql:3306 -timeout 30s php-fpm
