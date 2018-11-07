FROM php:7.2-cli

RUN apt-get update && apt-get install -y librabbitmq-dev libssh-dev libmemcached-dev supervisor wget
RUN pecl install amqp
RUN docker-php-ext-enable amqp
RUN pecl install memcached
RUN docker-php-ext-enable memcached
RUN docker-php-ext-install pdo_mysql

ENV DOCKERIZE_VERSION v1.2.0
RUN wget https://github.com/presslabs/dockerize/releases/download/$DOCKERIZE_VERSION/dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && tar -C /usr/local/bin -xzvf dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz \
    && rm dockerize-linux-amd64-$DOCKERIZE_VERSION.tar.gz

COPY supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY supervisor/conf.d/app.conf /etc/supervisor/conf.d/supervisord.conf

RUN mkdir -p var/log/supervisor

WORKDIR /app

CMD dockerize -wait tcp://rabbitmq:5672 -wait tcp://mysql:3306 supervisord -n
