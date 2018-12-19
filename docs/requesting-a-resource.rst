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

===============  ======================================================  =======
 Name            Description                                             Example
===============  ======================================================  =======
``url``          URL of the resource to be retrieved                     ``http://example.com``
``callback``     URL to which the resource should be sent                ``https://httpbin.org/post``
``headers``      JSON-encoded key:value pairs                            ``{"User-Agent":"Chrome, honest"}``
``parameters``   JSON-encoded parameters                                 ``{"cookies":{"domain": "…"}}``
===============  ======================================================  =======

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

----------------------------
Specifying Cookie Parameters
----------------------------

Including a ``Cookie`` header in your request for a resource will result in an equivalent ``Cookie`` header being
sent with the relevant HTTP request.

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d 'url=http://example.com/&headers={"Cookie":"key=value"}&callback=https://httpbin.org/post'

Cookies may contain sensitive information. The request for a resource may be redirected to another host. You do not
want to pass potentially-sensitive information to another host. No, you don't. Trust me.

Add to your ``parameters`` value a cookie parameters object:

.. code-block:: json

    {
      "cookie": {
        "domain": ".example.com",
        "path": "/"
      }
    }

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d 'url=http://example.com/&headers={"Cookie":"key=value"}&parameters={"cookies":{"domain":".example.com","path":"/"}}&callback=https://httpbin.org/post'

You must include ``domain`` and ``path`` values. It is up to you to choose the correct values for the resource
you are requesting.

Only requests against URLs that match the given ``domain`` and ``path`` values will have the relevant ``Cookie`` header
set. Cookie parameters prevent cookie data from being exposed where it should not be.

If you specify a ``Cookie`` header but do not specify cookie parameters, no cookies will be sent with the request
to retrieve the resource.

-----------------------------------
Specifying Authorization Parameters
-----------------------------------

Including an ``Authorization`` header in your request for a resource will result in an equivalent ``Authorization``
header being sent with the relevant HTTP request.

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d 'url=http://example.com/&headers={"Authorization":"Basic dXNlcm5hbWU6cGFzc3dvcmQ="}&callback=https://httpbin.org/post'

Authorization data is sensitive. The request for a resource may be redirected to another host. You do not
want to pass sensitive information to another host.

Add to your ``parameters`` value a HTTP authorization object:

.. code-block:: json

    {
      "http-authorization": {
        "host": "example.com",
      }
    }

.. code-block:: sh

    curl -X POST http://localhost:8001/ \
         -d 'url=http://example.com/&headers={"Authorization":"Basic dXNlcm5hbWU6cGFzc3dvcmQ="}&parameters={"http-authorization":{"host":"example.com"}}&callback=https://httpbin.org/post'

You must include a ``host`` value. This should be identical to the host in the URL of the resource that you are
requesting.

If you specify an ``Authorization`` header but do not specify HTTP authorization parameters, no authorization header
will be set on the request to retrieve the resource.

--------------------------
Understanding The Response
--------------------------

.. _requesting-a-resource-success-request:

~~~~~~~~~~~~~~~~~~~~~~~~
Successful Request (200)
~~~~~~~~~~~~~~~~~~~~~~~~

The response body (``"118e35f631be802c41bec5c9dfb0f415"`` in the very first example) is a json-encoded request ID.

The request ID is unique to the combination of ``url``, ``headers`` and ``parameters``.

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
