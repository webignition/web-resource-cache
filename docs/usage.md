# Usage

You've created either a [simple instance](/docs/simple-installation.md) or a 
[namespaced instance](/docs/namespaced-installation.md) (or perhaps 
[multiple instances](/docs/multiple-live-instances.md) on the same host) and now you want
to use the service.

Great, here's how.

## Requesting a resource

Send a `POST` request to your instance URL. Refer to the guide to [requesting a resource](/docs/requesting-a-resource.md). 

## Callbacks

A request to retrieve a resource will be followed up (*eventually*) by a `POST` request to the given
`callback` URL.

The body of the request is a `application/json`-encoded [response object](/docs/callback-responses.md).

