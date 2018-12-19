==================
Callback Responses
==================

A request to retrieve a resource will be followed up (*eventually*) by a ``POST`` request to the given
``callback`` URL.

The body of the request is a json-encoded response object.

--------------------------
Response Object Properties
--------------------------

================  ======================================================  =======
 Name             Description                                             Example
================  ======================================================  =======
``request_id``    Unique `request identifier`_                            ``118e35f631be802c41bec5c9dfb0f415``
``status``        Whether the resource could be retrieved                 ``success`` or ``failed``
``failure_type``  If ``status=failed``                                    ``http``, ``curl`` or ``unknown``
``status_code``   | If ``status=failed``                                  | ``failure_type=http``: ``404``, ``500`` …
                  | and (``failure_type=http`` or ``failure_type=curl``)  | ``failure_type=curl``: ``6``, ``28`` …
``context``       | Array of additional information                       |
                  | If ``status=failed``                                  |
                  | and ``failure_type=http`` and ``status_code=301``     |
                  |                                                       |
``headers``       | Response headers if ``status=success``                ``{"content-type": "text/html"}``
``content``       | Base64-encoded response body                          ``PGRvY3R5cGUgaHRtbD4=``
                  | in cases where ``status=success``
================  ======================================================  =======

------------------------
Success Response Example
------------------------

.. code-block:: json

    {
      "request_id": "118e35f631be802c41bec5c9dfb0f415",
      "status": "success",
      "headers": {
        "content-type": "text/html; charset=utf-8",
        "content-length": 40,
        "cache-control": "public, max-age=60"
      },
      "content": "PGRvY3R5cGUgaHRtbD48aHRtbD48Ym9keT48L2JvZHk+PC9odG1sPg=="
    }

------------------------------------
HTTP Failure Example (404 Not Found)
------------------------------------

.. code-block:: json

    {
      "request_id": "118e35f631be802c41bec5c9dfb0f415",
      "status": "failed",
      "failure_type": "http",
      "status_code": 404
    }

--------------------------
HTTP Failure Example (301)
--------------------------

.. code-block:: json

    {
      "request_id": "118e35f631be802c41bec5c9dfb0f415",
      "status": "failed",
      "failure_type": "http",
      "status_code": 404,
      "context": {
        "too_many_redirects": true,
        "is_redirect_loop": true,
        "history": [
            "http://example.com",
            "http://example.com",
            "http://example.com",
            "http://example.com",
            "http://example.com"
        ]
      }
    }

------------------------------------------
Curl Failure Example (Operation Timed Out)
------------------------------------------

.. code-block:: json

    {
      "request_id": "118e35f631be802c41bec5c9dfb0f415",
      "status": "failed",
      "failure_type": "curl",
      "status_code": 28
    }

-----------------------
Unknown Failure Example
-----------------------

.. code-block:: json

    {
      "request_id": "118e35f631be802c41bec5c9dfb0f415",
      "status": "failed",
      "failure_type": "unknown"
    }

.. _request identifier: /requesting-a-resource.html#understanding-the-response
