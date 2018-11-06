FROM php:7.2-fpm

RUN apt-get update

#RUN docker-php-ext-install mysql mysqli pdo pdo_mysql
#RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y librabbitmq-dev libssh-dev libmemcached-dev
RUN pecl install amqp
RUN docker-php-ext-enable amqp
RUN pecl install memcached
RUN docker-php-ext-enable memcached
RUN docker-php-ext-install mysqli pdo pdo_mysql

#RUN apt-get install -y zlib1g-dev libpq-dev git libicu-dev libxml2-dev \
#    && docker-php-ext-configure intl \
#    && docker-php-ext-install intl \
#    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
#    && docker-php-ext-install pdo pdo_pgsql pgsql \
#    && docker-php-ext-install zip xml

#RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

# Set timezone
#RUN rm /etc/localtime
#RUN ln -s /usr/share/zoneinfo/Europe/Berlin /etc/localtime
#RUN "date"

WORKDIR /app