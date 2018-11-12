# Configuration

Configuration is provided through a collection of environment variables. These can be set on the host itself 
or defined in `docker/.env`. I'm assuming the use of a `.env` file to be consumed by `docker-compose`.

## Creating your configuration file

Copy the relevant `.env.dist` to `.env`.

```bash
cp docker/.env.dist docker/.env
```

## Configuration you must set

Things are not going to work nicely if you don't set these.

`ASYNC_HTTP_RETRIEVER_DATABASE_DATA_PATH`<br>
The path *on the host* for MySQL to store data.<br>
Set this to any meaningful directory that already exists. 
Do not set this to `/var/lib/mysql` if your host is running a MySQL instance.

This must be set before installing.

## Configuration you should set

Things will work if you don't set these, however setting is recommended as some
of these values are sensitive.

`ASYNC_HTTP_RETRIEVER_EXPOSED_PORT`<br>
Port to expose for the application. Set to any suitable unused port number.

`ASYNC_HTTP_RETRIEVER_MYSQL_ROOT_PASSWORD`<br>
The root password for the MySQL instance.<br>
Set to a random value and forget about it.

This must be set before installing if you want to set it.

`ASYNC_HTTP_RETRIEVER_DATABASE_USER`<br>
DB user for the application to use.<br>
Set to a random value and forget about it.

This must be set before installing if you want to set it.

`ASYNC_HTTP_RETRIEVER_DATABASE_PASSWORD`<br>
DB password for the application to use.<br>
Set to a random value and forget about it.

This must be set before installing if you want to set it.

`ASYNC_HTTP_RETRIEVER_RABBITMQ_USER`<br>
Username for the rabbit-mq service. Set to any meaningful value.

This must be set before installing if you want to set it.

`ASYNC_HTTP_RETRIEVER_RABBITMQ_PASS`<br>
Password for the rabbit-mq service. Set to any meaningful value.

This must be set before installing if you want to set it.

## Configuration you can optionally set

Set these if you like, things will work just fine if you don't.

`ASYNC_HTTP_RETRIEVER_APP_SECRET`<br>
Private token used within the application.<br>
Set to whatever you like.

`ASYNC_HTTP_RETRIEVER_CALLBACK_ALLOWED_HOSTS`<br>
Used to limit the host names allowed in callback URLs.<br>
Defaults to `*` which allows all host names.

`ASYNC_HTTP_RETRIEVER_RETRIEVER_TIMEOUT_SECONDS`<br>
Timeout in seconds for when retrieving HTTP resources.<br>
Defaults to `30` seconds.<br>
Set to any positive integer. Set to 0 for no timeout (probably a bad idea).

`ASYNC_HTTP_RETRIEVER_DATABASE_NAME`<br>
Name of the application database.

This must be set before installing if you want to set it.

`ASYNC_HTTP_RETRIEVER_DATABASE_USER`<br>
DB user for the application.

This must be set before installing if you want to set it.

`ASYNC_HTTP_RETRIEVER_RABBITMQ_MANAGEMENT_EXPOSED_PORT`<br>
Exposed port of the rabbit-mq management interface.
