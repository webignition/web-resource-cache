=============
Configuration
=============

Configuration is provided through a collection of environment variables. These can be set on the host itself
or defined in ``docker/.env``.

--------------------------------
Creating your configuration file
--------------------------------

Copy the relevant ``.env.dist`` to ``.env``.

.. code-block:: sh

    cp docker/.env.dist docker/.env

--------------------------
Configuration You Must Set
--------------------------

Things are not going to work nicely if you don't set these.

| ``ASYNC_HTTP_RETRIEVER_DATABASE_DATA_PATH``
| The path *on the host* for MySQL to store data.

Set this to any writable directory that already exists.
Do not set this to ``/var/lib/mysql`` if your host is running a MySQL instance.

This must be set before installing.

----------------------------
Configuration You Should Set
----------------------------

Things will work if you don't set these, however setting is recommended as some
of these values are sensitive.

| ``ASYNC_HTTP_RETRIEVER_EXPOSED_PORT``
| Port to expose for the application. Set to any suitable unused port number.

| ``ASYNC_HTTP_RETRIEVER_MYSQL_ROOT_PASSWORD``
| The root password for the MySQL instance. Set to any value and forget about it.

This must be set before installing if you want to set it.

| ``ASYNC_HTTP_RETRIEVER_DATABASE_USER``
| DB user for the application to use. Set to any value and forget about it.

This must be set before installing if you want to set it.

| ``ASYNC_HTTP_RETRIEVER_DATABASE_PASSWORD``
| DB password for the application to use. Set to any value and forget about it.

This must be set before installing if you want to set it.

| ``ASYNC_HTTP_RETRIEVER_RABBITMQ_USER``
| Username for the rabbit-mq service. Set to any meaningful value.

This must be set before installing if you want to set it.

| ``ASYNC_HTTP_RETRIEVER_RABBITMQ_PASS``
| Password for the rabbit-mq service. Set to any meaningful value.

This must be set before installing if you want to set it.

------------------------------------
Configuration You Can Optionally Set
------------------------------------

Set these if you like, things will work just fine if you don't.

| ``ASYNC_HTTP_RETRIEVER_CONSUMER_COUNT``
| Number of parallel message consumers. Defaults to 1. Ideally set higher.

| ``ASYNC_HTTP_RETRIEVER_APP_SECRET``
| Private token used within the application. Set to whatever you like.

| ``ASYNC_HTTP_RETRIEVER_CALLBACK_ALLOWED_HOSTS``
| Used to limit the host names allowed in callback URLs. Defaults to `*` which allows all host names.

| ``ASYNC_HTTP_RETRIEVER_RETRIEVER_TIMEOUT_SECONDS``
| Timeout in seconds for when retrieving HTTP resources. Defaults to `30` seconds.

Set to any positive integer. Set to 0 for no timeout (probably a bad idea).

| ``ASYNC_HTTP_RETRIEVER_DATABASE_NAME``
| Name of the application database.

This must be set before installing if you want to set it.

| ``ASYNC_HTTP_RETRIEVER_DATABASE_USER``
| DB user for the application.

This must be set before installing if you want to set it.

| ``ASYNC_HTTP_RETRIEVER_RABBITMQ_MANAGEMENT_EXPOSED_PORT``
| Exposed port of the rabbit-mq management interface.

| ``ASYNC_HTTP_RETRIEVER_HTTPBIN_EXPOSED_PORT``
| Port to expose for httpbin when using the dev configuration. Defaults to 7000.
