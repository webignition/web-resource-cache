#!/usr/bin/env bash

# Wait for mysql to be accepting connections
echo "Waiting for mysql to accept connections ..."
while ! mysql -uroot -p$WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD --host web-resource-cache-mysql-host --port 33066 -e 'SELECT 1'; do
    sleep 1
done

echo "Waiting for phpmyadmin to be up ..."
while ! curl -I http://localhost:8080; do
    sleep 1
done

echo "Waiting for rabbitmq management interface to be up ..."
while ! curl -I http://localhost:15672; do
    sleep 1
done

echo "Waiting for nginx to be up ..."
while ! curl -I http://localhost:8001/composer.json; do
    sleep 1
done