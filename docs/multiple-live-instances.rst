=======================
Multiple Live Instances
=======================

You would normally have a single live instance of the application per host.

There are use cases for having multiple live instances, such as when upgrading with zero downtime.

We can isolate instances through the use of:

- the `docker-compose project name argument`_
- the ``ID`` environment variable
- the ``ASYNC_HTTP_RETRIEVER_EXPOSED_PORT`` environment variable
- the ``ASYNC_HTTP_RETRIEVER_RABBITMQ_MANAGEMENT_EXPOSED_PORT`` environment variable

------------------------------------------------------------------
Example: Using Two Instances to Allow Zero-downtime When Upgrading
------------------------------------------------------------------

A zero-downtime upgrade cannot be guaranteed when upgrading an existing instance. We can achieve zero downtime
for applications that use an existing instance by creating a second instance and using that instead. The first
instance can then be removed.

Scenario:

- create instance-x (first instance)
- configure instance-x and get it running
- configure relevant applications on the host to use instance-x
- use instance-x for a while
- determine that an upgrade is needed
- create instance-y (second instance)
- configure instance-y and get it running
- configure relevant applications on the host to use instance-y
- remove instance-x

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Creating the First Instance (instance-x)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    ##########
    # GET CODE
    ##########

    # Start at the current user's home directory (just a starting point for this example)
    cd ~

    # Create a directory for the instance, change to that directory and retrieve the code
    mkdir -p /var/www/async-http-retriever-x
    cd /var/www/async-http-retriever-x
    git clone git@github.com:webignition/async-http-retriever.git .

    # Create a directory for the MySQL data files for this instance
    mkdir -p /var/docker-mysql/async-http-retriever-x

    # Change to the docker directory as that is where all the fun happens
    cd docker

    ###########
    # CONFIGURE
    ###########

    # Create the configuration
    cp .env.dist .env

    # We need to set the MySQL data path on the host
    # The first instance can retain the default configuration
    sed -i 's|MYSQL_DATA_PATH_ON_HOST|/var/docker-mysql/async-http-retriever-x|g' .env

    #########
    # INSTALL
    #########

    ID=instance-x docker-compose -p instance-x up -d --build
    ID=instance-x docker-compose -p instance-x exec -T app-web composer install
    ID=instance-x docker-compose -p instance-x exec -T app-web ./bin/console doctrine:migrations:migrate --no-interaction
    ID=instance-x docker-compose -p instance-x down
    ID=instance-x docker-compose -p instance-x up -d

~~~~~~~~~~~
Time Passes
~~~~~~~~~~~

You configure relevant applications to use instance-x and do so until you determine that an upgrade is needed.

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Creating the Second Instance (instance-y)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    ##########
    # GET CODE
    ##########

    # Start at the current user's home directory
    # (not required, just a starting point for this example)
    cd ~

    # Create a directory for the instance, change to that directory and retrieve the code
    mkdir -p /var/www/async-http-retriever-y
    cd /var/www/async-http-retriever-y
    git clone git@github.com:webignition/async-http-retriever.git .

    # Create a directory for the MySQL data files for this instance
    mkdir -p /var/docker-mysql/async-http-retriever-y

    # Change to the docker directory as that is where all the fun happens
    cd docker

    ###########
    # CONFIGURE
    ###########

    # Create the configuration
    cp .env.dist .env

    # We need to set the MySQL data path on the host
    sed -i 's|MYSQL_DATA_PATH_ON_HOST|/var/docker-mysql/async-http-retriever-y|g' .env

    # Set the rabbit-mq management port to not be the same as instance-x
    sed -i 's|15672|25672|g' .env

    # Set the application port to not be the same as instance-x
    sed -i 's|8001|8002|g' .env

    #########
    # INSTALL
    #########

    ID=instance-y docker-compose -p instance-y up -d --build
    ID=instance-y docker-compose -p instance-y exec -T app-web composer install
    ID=instance-y docker-compose -p instance-y exec -T app-web ./bin/console doctrine:migrations:migrate --no-interaction
    ID=instance-y docker-compose -p instance-y down
    ID=instance-y docker-compose -p instance-y up -d

~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Configure Applications to Use instance-y
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Well, I can't really tell you what you need there. But you know.

Let's assume that you configure all relevant applications to use instance-y and that, once done,
no applications are using instance-x.

~~~~~~~~~~~~~~~~~~~~~~~~~~~
Removing the First Instance
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    # Change to the docker directory as that is where all the fun happens
    cd /var/www/async-http-retriever-y/docker

    # Stop and remove containers
    ID=instance-x docker-compose -p instance-x down

    # Remove MySQL data files
    rm -rf /var/docker-mysql/async-http-retriever-x

.. _docker-compose project name argument: https://docs.docker.com/compose/reference/overview/
