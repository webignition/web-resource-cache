===============
Getting Started
===============

------------
Requirements
------------

.. include:: includes/requirements.rst

.. _getting-started-getting-the-code:

----------------
Getting the Code
----------------

.. code-block:: bash

    git clone git@github.com:webignition/async-http-retriever.git

.. _getting-started-creating-your-configuration:

---------------------------
Creating Your Configuration
---------------------------

Configuration is provided through a collection of environment variables. These can be set on the host itself
or defined in ``docker/.env``. I'm assuming the use of a ``.env`` file to be consumed by ``docker-compose``.

There are some configuration values you must set before installing. There are some configuration values
that can only be set before installing.

.. code-block:: bash

    cd /var/www/async-http-retriever/instance-x
    cp docker/.env.dist docker/.env

Edit your ``docker/.env`` file as needed. Refer to the :doc:`configuration guide </configuration>`

.. _getting-started-installation:

------------
Installation
------------

You've got the code and you've set your configuration via environment variables. Now to install.

The following :ref:`simple installation guide <getting-started-simple-installation>` briefly covers how to install an instance.

If you ever want to perform zero-downtime upgrades (yes, yes, do you) or if you ever want to run multiple instances
on the same host, you want to create an :ref:`isolated installation <getting-started-isolated-installation>`.

.. _getting-started-simple-installation:

~~~~~~~~~~~~~~~~~~~
Simple Installation
~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    # Create a directory for the application and get the code
    mkdir -p /var/www/async-http-retriever/instance-x
    cd /var/www/async-http-retriever/instance-x
    git clone git@github.com:webignition/async-http-retriever.git .

    # Change to the docker directory as that is where all the fun happens
    cd docker

    # Build container images
    docker-compose up -d --build

    # Install third-party dependencies
    docker-compose exec -T app-web ./bin/console composer install

    # Create database schema
    docker-compose exec -T app-web ./bin/console doctrine:migrations:migrate --no-interaction

    # Restart containers
    # (will have failed due to lack of third-party dependencies and lack of database schema)
    docker-compose down && docker-compose up -d

.. _getting-started-isolated-installation:

~~~~~~~~~~~~~~~~~~~~~
Isolated Installation
~~~~~~~~~~~~~~~~~~~~~

.. _configuration guide: /configuration.html

An isolated instance:

- has container names unique to itself
- has service port numbers unique to itself
- will not interfere with other instances on the same host
- will not be interfered by other instances on the same host

Isolated installations are preferred.

We can isolate instances through the use of:

- the `docker-compose project name argument`_
- the ``ID`` environment variable
- the ``ASYNC_HTTP_RETRIEVER_EXPOSED_PORT`` environment variable
- the ``ASYNC_HTTP_RETRIEVER_RABBITMQ_MANAGEMENT_EXPOSED_PORT`` environment variable

An isolated instance has a name. This can be whatever is meaningful to you. For this example, we'll opt for the name
``instance-x``.

.. code-block:: sh

    ##########
    # GET CODE
    ##########

    # Create a directory for the application and get the code
    mkdir -p /var/www/async-http-retriever/instance-x
    cd /var/www/async-http-retriever/instance-x
    git clone git@github.com:webignition/async-http-retriever.git .

    # Create a directory for the MySQL data files for this instance
    mkdir -p /var/docker-mysql/async-http-retriever-x

    # Change to the docker directory as that is where all the fun happens
    cd docker

    # CONFIGURE

    # Create the configuration
    cp .env.dist .env

    # We need to set the MySQL data path on the host
    sed -i 's|MYSQL_DATA_PATH_ON_HOST|/var/docker-mysql/async-http-retriever-x|g' .env

    # Set a non-default rabbit-mq management interface port
    sed -i 's|15672|25672|g' .env

    # Set a non-default application port
    sed -i 's|8001|8002|g' .env

    # INSTALL

    ID=instance-x docker-compose -p instance-x up -d --build
    ID=instance-x docker-compose -p instance-x exec -T app-web composer install
    ID=instance-x docker-compose -p instance-x exec -T app-web ./bin/console doctrine:migrations:migrate --no-interaction
    ID=instance-x docker-compose -p instance-x down
    ID=instance-x docker-compose -p instance-x up -d

You must pass in the ``ID`` environment variable and the project name when calling ``docker-compose`` commands:

.. code-block:: sh

    # List containers
    ID=instance-x docker-compose -p instance-x ps

    # ssh into the app-web container
    ID=instance-x docker-compose -p instance-x exec app-web /bin/bash

.. _docker-compose project name argument: https://docs.docker.com/compose/reference/overview/