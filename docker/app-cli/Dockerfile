FROM php:7.2-cli

RUN apt-get update && apt-get install -y librabbitmq-dev libssh-dev libmemcached-dev supervisor
RUN pecl install amqp
RUN docker-php-ext-enable amqp
RUN pecl install memcached
RUN docker-php-ext-enable memcached
RUN docker-php-ext-install pdo_mysql

COPY supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY supervisor/conf.d/app.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /app

CMD ["/usr/bin/supervisord"]
