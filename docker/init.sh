#!/usr/bin/env bash

"${WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD:=root}"

docker-compose up -d

echo "Waiting for mysql to accept connections ..."
while ! mysql -uroot -p${WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD} --host web-resource-cache-mysql-host --port 33066 -e 'SELECT 1'; do
    sleep 1
done

docker exec web-resource-cache-app-web ./bin/console doctrine:migrations:migrate --no-interaction
