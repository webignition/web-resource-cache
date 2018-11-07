#!/usr/bin/env bash

"${WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD:=}"

# Wait for mysql to be accepting connections
echo "Waiting for mysql to accept connections ..."
while ! mysql -uroot -p${WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD} --host web-resource-cache-mysql-host --port 33066 -e 'SELECT 1'; do
    sleep 1
done
