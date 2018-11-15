======================
Request-Response Cycle
======================

Your application :doc:`requests a resource </requesting-a-resource>`.

This request includes the ``url`` of the
resource you want to retrieve, the ``callback`` URL where you want the resource to be sent when it has been retrieved
and, optionally, a set of ``headers`` to be sent with the retrieval request.

The response you receive includes a json-encoded string.
That's the :ref:`request ID <requesting-a-resource-success-request>`. You'll want to make a note of that somewhere.

.. include:: includes/ascii-diagram/request.rst

Your request to retrieve a resource has been put into a queue. The request will probably be handled quite quickly but
not instantly. Some time will pass before your request has completed.

… let's wait. Something will happen eventually …

Your request completed and was successful. That's good.

A json-encoded :doc:`response object </callback-responses>` is sent in a ``POST`` request to the ``callback`` URL
that you specified in your request.

.. include:: includes/ascii-diagram/callback.rst
