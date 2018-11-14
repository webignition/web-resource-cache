=====================
Requesting a Resource
=====================

----------------
Making a Request
----------------

~~~~~~~~~~
Parameters
~~~~~~~~~~

Send a ``POST`` request to your instance. Include the ``url`` of the resource to be retrieved
and the ``callback`` URL to where the response should be sent.

You can optionally provide a ``headers`` parameter defining headers to send when retrieving the resource.

The collection of headers can contain whatever keys and values you need to satisfy a request. This might include
specifying the `User-Agent`_, passing along `Authorization`_ or setting `Cookies`_.

=============  ======================================================  =======
 Name          Description                                             Example
=============  ======================================================  =======
``url``        URL of the resource to be retrieved                     ``http://example.com/``
``callback``   URL to which the resource should be sent                ``http://callback.example.com/``
``headers``    Key:value collection sent when retrieving the resource
=============  ======================================================  =======

~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Curl Example Without Headers
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d "url=http://example.com/&callback=http://callback.example.com"

    "118e35f631be802c41bec5c9dfb0f415"

~~~~~~~~~~~~~~~~~~~~~~~~~
Curl Example With Headers
~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d "url=http://example.com/&callback=http://c.example.com/&headers[k1]=v1&headers[k2]=v2"

    "dd7011cb26b7435d1ab901b4caec5f01"

--------------------------
Understanding The Response
--------------------------

~~~~~~~~~~~~~~~~~~~~~~~~
Successful Request (200)
~~~~~~~~~~~~~~~~~~~~~~~~

The response body (``"118e35f631be802c41bec5c9dfb0f415"`` in the first example, ``"dd7011cb26b7435d1ab901b4caec5f01"``
in the second example) is a json-encoded request ID.

The request ID is unique to the combination of ``url`` and ``headers``.

Store the request ID in *your* application. The request ID is sent with the requested resource to the given
``callback`` URL. Use the request ID to map the response you receive to the request that you made.

~~~~~~~~~~~~~~~~~
Bad Request (400)
~~~~~~~~~~~~~~~~~

Your request will receive a ``HTTP 400`` response if:

- ``url`` is empty
- ``callback`` is empty
- ``callback`` is not valid (which depends on your configuration for allowed callback host names)

---------
Callbacks
---------

A request to retrieve a resource will be followed up (*eventually*) by a ``POST`` request to the given
``callback`` URL.

The body of the request is a `application/json`-encoded `response object`_.

.. _User-Agent: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
.. _Authorization: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Authorization
.. _Cookies: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cookie
.. _response object: /callback-responses.html
