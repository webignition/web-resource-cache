#!/usr/bin/env bash

docker-compose up -d --build

echo "Waiting for mysql to accept connections ..."
while ! mysql -uroot -p${WEB_RESOURCE_CACHE_MYSQL_ROOT_PASSWORD} --host web-resource-cache-mysql-host --port 33066 -e 'SELECT 1'; do
    sleep 1
done

docker-compose ps
docker logs web-resource-cache-app-web
docker logs web-resource-cache-app-cli
docker-compose exec -T app-web ./bin/console doctrine:migrations:migrate --no-interaction
