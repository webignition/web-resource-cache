# Asychronous HTTP Retriever

Service for retrieving HTTP resources asychronously. Self-hosted within a lovely collection of [docker containers](https://en.wikipedia.org/wiki/Docker_(software)).

**Short description**:<br> 
Send a `POST` request containing `url` and `callback` values.
Content for the given `url` will be retrieved *eventually* and `POSTed` back to the specified `callback` url.

**Why?**<br>
Pretty much every modern programming ecosystem provides a means for making HTTP requests and handling the resulting responses.
You already get sychronous HTTP out the box, possibly asychronous HTTP as well.

Using whatever HTTP functionality your progamming ecosystem provides is fine most of the time.

Want to retrieve the content of arbitrary urls often? No, you probably don't. But if you do, you quickly run into edge cases.
Temporary [service unavailability](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/503),
intermittent [internal server errors](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500), 
unpredictable [HTTP 429](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429) responses.

To reliably retrieve an arbitrary HTTP resource, you need to able to retry after a given period. 
You introduce state (remembering *what* to retrieve) and you need something to handle doing so *at the right time*
(some form of delayable background job processing).

**Production readiness**<br>
Not production ready

## Requirements

You'll need `docker` and `docker-compose` present on the host you want to run this on.

Developed and tested against `docker` 18.06.1-ce and `docker-compose` 1.22.0.

## Installation

### Get code
```bash
git clone git@github.com:webignition/async-http-retriever.git
cd async-http-retriever
```

### Configure

Configuration is provided through a collection of environment variables. These can be set on the host itself 
or defined in `docker/.env`. I'm assuming the use of a `.env` file to be consumed by `docker-compose`.

#### Creating your configuration file

Copy the relevant `.env.dist` to `.env` and have a look.

```bash
cp docker/.env.dist docker/.env
cat .env

ASYNC_HTTP_RETRIEVER_APP_ENV=prod
ASYNC_HTTP_RETRIEVER_APP_SECRET=secret
ASYNC_HTTP_RETRIEVER_CALLBACK_ALLOWED_HOSTS=*
ASYNC_HTTP_RETRIEVER_RETRIEVER_TIMEOUT_SECONDS=30
ASYNC_HTTP_RETRIEVER_MYSQL_ROOT_PASSWORD=root
ASYNC_HTTP_RETRIEVER_MYSQL_EXPOSED_PORT=33066
ASYNC_HTTP_RETRIEVER_PHPMYADMIN_EXPOSED_PORT=8080
ASYNC_HTTP_RETRIEVER_DATABASE_NAME=async_http_retriever
ASYNC_HTTP_RETRIEVER_DATABASE_USER=async_http_retriever_db_user
ASYNC_HTTP_RETRIEVER_DATABASE_PASSWORD=secret
ASYNC_HTTP_RETRIEVER_DATABASE_DATA_PATH=/var/lib/mysql
ASYNC_HTTP_RETRIEVER_RABBITMQ_USER=guest
ASYNC_HTTP_RETRIEVER_RABBITMQ_PASS=guest
ASYNC_HTTP_RETRIEVER_RABBITMQ_MANAGEMENT_EXPOSED_PORT=15672
ASYNC_HTTP_RETRIEVER_EXPOSED_PORT=8001
```

#### Configuration you must set

Things aren't going to work nicely if you don't set these.

| Environment variable | Purpose | Recommendation |
| --- | --- | --- |
| <sub>`ASYNC_HTTP_RETRIEVER_MYSQL_ROOT_PASSWORD`</sub> | The root password for the MySQL instance. | Set to a random value and forget about it. |
| <sub>`ASYNC_HTTP_RETRIEVER_DATABASE_PASSWORD`</sub> | DB password for the application to use. | Set to a random value and forget about it. |
| <sub>`ASYNC_HTTP_RETRIEVER_DATABASE_DATA_PATH`</sub> | The path **on the host** for MySQL to store data. | Set this to any meaningful directory that already exists. Do not set this to `/var/lib/mysql` if your host is running a MySQL instance. |
| <sub>`ASYNC_HTTP_RETRIEVER_RABBITMQ_USER`</sub> | Username for the rabbit-mq service. | Set to a random value and forget about it. |
| <sub>`ASYNC_HTTP_RETRIEVER_RABBITMQ_PASS`</sub> | Password for the rabbit-mq service. | Set to a random value and forget about it. |
| <sub>`ASYNC_HTTP_RETRIEVER_EXPOSED_PORT`</sub> | Port to expose for the application. | Set to any suitable unused port number. |

#### Configuration you can optionally set

Set these if you like, things will work just fine if you don't.

| Environment variable | Purpose | Recommendation |
| --- | --- | --- |
| <sub>`ASYNC_HTTP_RETRIEVER_APP_SECRET`</sub> | Private token used within the application. | Set to whatever you like. |
| <sub>`ASYNC_HTTP_RETRIEVER_CALLBACK_ALLOWED_HOSTS`</sub> | Used to limit the hostnames allowed in callback urls. | Foo. |
| <sub>`ASYNC_HTTP_RETRIEVER_RETRIEVER_TIMEOUT_SECONDS`</sub> | Timeout value for when retrieving HTTP resources. | Foo. |
| <sub>`ASYNC_HTTP_RETRIEVER_PHPMYADMIN_EXPOSED_PORT`</sub> | Exposed port of the phpmyadmin instance (if opted for). | Foo. |
| <sub>`ASYNC_HTTP_RETRIEVER_DATABASE_NAME`</sub> | Name of the database the application uses. | Leave as-is for single-instance uses. |
| <sub>`ASYNC_HTTP_RETRIEVER_DATABASE_USER`</sub> | DB user for the application. | `--` |
| <sub>`ASYNC_HTTP_RETRIEVER_RABBITMQ_MANAGEMENT_EXPOSED_PORT`</sub> | Exposed port of the rabbit-mq management interface. | `--` |

## Install

You've got the code and you've set your configuration via environment variables. Now to install.

```bash
cd docker

# Build container images
docker-compose up -d --build

# Install third-party dependencies
docker-compose exec -T app-web ./bin/console composer install

# Create database schema
docker-compose exec -T app-web ./bin/console doctrine:migrations:migrate --no-interaction

# Restart containers (will have failed due to lack of third-party dependencies and lack of database schema)
docker-compose down && docker-compose up -d
```
