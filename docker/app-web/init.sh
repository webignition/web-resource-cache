#!/usr/bin/env bash

APP_ENV="${APP_ENV:-prod}"

if [[ "prod" == ${APP_ENV} ]]; then
    echo "Production, removing xdebug"
    rm /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
fi
