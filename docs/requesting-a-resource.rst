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
``url``        URL of the resource to be retrieved                     ``http://example.com``
``callback``   URL to which the resource should be sent                ``https://httpbin.org/post``
``headers``    JSON-encoded key:value pairs                            ``{"User-Agent":"Chrome, honest"}``
=============  ======================================================  =======

~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Curl Example Without Headers
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d 'url=http://example.com/&callback=https://httpbin.org/post'

    "118e35f631be802c41bec5c9dfb0f415"

~~~~~~~~~~~~~~~~~~~~~~~~~
Curl Example With Headers
~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d 'url=http://example.com/&callback=https://httpbin.org/post&headers={"User-Agent":"Chrome"}'

    "ea8a4d4eb1840d0bec6284658a8ef064"

--------------------------
Understanding The Response
--------------------------

.. _requesting-a-resource-success-request:

~~~~~~~~~~~~~~~~~~~~~~~~
Successful Request (200)
~~~~~~~~~~~~~~~~~~~~~~~~

The response body (``"118e35f631be802c41bec5c9dfb0f415"`` in the first example, ``"ea8a4d4eb1840d0bec6284658a8ef064"``
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

.. _User-Agent: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent
.. _Authorization: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Authorization
.. _Cookies: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cookie
.. _response object: /callback-responses.html
