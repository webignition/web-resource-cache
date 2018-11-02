#!/usr/bin/env bash

while ! mysqladmin -uroot -p$WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD --host web-resource-cache-mysql-host --port 33066 --count=1 --sleep=1 ping --silent; do
    sleep 1
done
