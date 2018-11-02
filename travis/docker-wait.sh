#!/usr/bin/env bash

while ! mysql -uroot -p$WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD --host web-resource-cache-mysql-host --port 33066 -e 'status'; do
    sleep 1
done