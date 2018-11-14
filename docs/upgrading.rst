=========
Upgrading
=========

Upgrading a live instance is not recommended. Doing so can put your instance into an odd state resulting
in service-unavailability the applications making use of your instance.

Upgrading with zero downtime is achievable. We will do that.

-----------------------
Zero-downtime Upgrading
-----------------------

We can achieve zero downtime for applications that use an existing instance by creating a second instance and using
that instead. The first instance can then be removed.

Pre-requisites:

- you have an existing :ref:`isolated instance <getting-started-isolated-installation>` named ``instance-x``.
- you have determined that an upgrade is needed

Scenario:

- create instance-y (second instance)
- configure instance-y and get it running
- configure relevant applications on the host to use instance-y
- remove instance-x

---------------------------------------------
Creating the Second Instance (``instance-y``)
---------------------------------------------

.. code-block:: sh

    ##########
    # GET CODE
    ##########

    # Create a directory for the application and get the code
    mkdir -p /var/www/async-http-retriever/instance-y
    cd /var/www/async-http-retriever/instance-y
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
    sed -i 's|15672|35672|g' .env

    # Set the application port to not be the same as instance-x
    sed -i 's|8001|8003|g' .env

    #########
    # INSTALL
    #########

    ID=instance-y docker-compose -p instance-y up -d --build
    ID=instance-y docker-compose -p instance-y exec -T app-web composer install
    ID=instance-y docker-compose -p instance-y exec -T app-web ./bin/console doctrine:migrations:migrate --no-interaction
    ID=instance-y docker-compose -p instance-y down
    ID=instance-y docker-compose -p instance-y up -d

--------------------------------------------
Configure Applications to Use ``instance-y``
--------------------------------------------

You managed to configure relevant applications to use ``instance-x``. Do the same but for ``instance-y``.

You configure all relevant applications to use ``instance-y`` and that, once done,
no applications are using ``instance-x``.

---------------------------
Removing the First Instance
---------------------------

.. code-block:: sh

    # Change to the docker directory as that is where all the fun happens
    cd /var/www/async-http-retriever/instance-x/docker

    # Stop and remove containers
    ID=instance-x docker-compose -p instance-x down

    # Remove instance-x
    cd /var/www/async-http-retriever
    rm -rf cd /var/www/async-http-retriever/instance-x

    # Remove MySQL data files
    rm -rf /var/docker-mysql/async-http-retriever-x

.. _docker-compose project name argument: https://docs.docker.com/compose/reference/overview/
