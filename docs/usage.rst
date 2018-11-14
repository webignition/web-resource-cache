=====
Usage
=====

You've created either a `simple instance`_ or a `namespaced instance`_ (or perhaps `multiple instances`_
on the same host) and now you want to use the service.

Great, here's how.

---------------------
Requesting a Resource
---------------------

Send a ``POST`` request to your instance URL. Refer to the guide to `requesting a resource`_.

---------
Callbacks
---------

A request to retrieve a resource will be followed up (*eventually*) by a ``POST`` request to the given
``callback`` URL.

The body of the request is a json-encoded `response object`_.

.. _simple instance: /simple-installation.html
.. _namespaced instance: /namespaced-installation.html
.. _multiple instances: /multiple-live-instances.html
.. _requesting a resource: /requesting-a-resource.html
.. _response object: /callback-responses.html
