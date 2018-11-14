========
Overview
========

.. include:: includes/overview/introduction.rst

-----------------
Short Description
-----------------
Send a ``POST`` request containing ``url`` and ``callback`` values. Content for the given ``url`` will be retrieved
*eventually* and sent in a ``POST`` request to the specified ``callback`` url.

----
Why?
----

Pretty much every modern programming ecosystem provides a means for making HTTP requests and handling the resulting responses.
You already get synchronous HTTP out the box, possibly asynchronous HTTP as well.
Using whatever HTTP functionality your programming ecosystem provides is fine most of the time.

Want to retrieve the content of arbitrary urls often? No, you probably don't. But if you do, you periodically run into
failure cases.

We don't like failure cases. Temporary `service unavailability`_, intermittent `internal server errors`_,
unpredictable `rate limiting`_ responses.

To reliably retrieve an arbitrary HTTP resource, you need to able to retry after a given period for those odd cases
where a request failed *right now* but which could (maybe would) succeed *a little later*.
You introduce state (remembering *what* to retrieve) and you need something to handle doing so *at the right time*
(some form of delayable background job processing).

You could re-write the means for doing so for every application you create that needs to retrieve
resources over HTTP. Or you could not. Up to you really.

--------------------
Production Readiness
--------------------

Not production ready

.. _service unavailability: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/503
.. _internal server errors: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500
.. _rate limiting: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429
