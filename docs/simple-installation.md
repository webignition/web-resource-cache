# Installation

## Get code
```bash
git clone git@github.com:webignition/async-http-retriever.git
cd async-http-retriever
```

## Configure

Configuration is provided through a collection of environment variables. These can be set on the host itself 
or defined in `docker/.env`. I'm assuming the use of a `.env` file to be consumed by `docker-compose`.

There are some configuration values you must set before installing. There are some configuration values
that can only be set before installing. 

Please read [the configuration guide](/docs/configuration.md).

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
