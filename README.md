# Asychronous HTTP Retriever

Service for retrieving HTTP resources asynchronously. Self-hosted within a lovely collection of [docker containers](https://en.wikipedia.org/wiki/Docker_(software)).

**Short description**:<br> 
Send a `POST` request containing `url` and `callback` values.
Content for the given `url` will be retrieved *eventually* and `POSTed` back to the specified `callback` url.

**Why?**<br>
Pretty much every modern programming ecosystem provides a means for making HTTP requests and handling the resulting responses.
You already get synchronous HTTP out the box, possibly asynchronous HTTP as well.

Using whatever HTTP functionality your programming ecosystem provides is fine most of the time.

Want to retrieve the content of arbitrary urls often? No, you probably don't. But if you do, you quickly run into edge cases.
Temporary [service unavailability](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/503),
intermittent [internal server errors](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500), 
unpredictable [HTTP 429](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429) responses.

To reliably retrieve an arbitrary HTTP resource, you need to able to retry after a given period. 
You introduce state (remembering *what* to retrieve) and you need something to handle doing so *at the right time*
(some form of delayable background job processing).

You could re-write the means for doing so for every application you create that needs to retrieve
resources over HTTP. Or you could not. Up to you really.

**Production readiness**<br>
Not production ready

## Requirements

You'll need `docker` and `docker-compose` present on the host you want to run this on.

Developed and tested against `docker` 18.06.1-ce and `docker-compose` 1.22.0.

## Installation

The [simple installation guide](/docs/simple-installation.md) provides an overview on how install and new instance.

The [namespaced installation guide](/docs/namespaced-installation.md) provides instructions on how to create
an isolated, namespaced installation that can be run alongside other live instances.

Read the namespaced installation guide if you want, in the future, to be able to upgrade with zero downtime 
(that's a good idea).

## Configuration

There are some configuration values you must set before installing. There are some configuration values
that can only be set before installing. 

Please read [the configuration guide](/docs/configuration.md).

## Upgrading with zero downtime (preferred)

Refer to the [multiple live instances](/docs/multiple-live-instances.md) guide for an example of how to
use two instances to achieve an upgrade without any lack of availability for applications using the service.

Short version: do not upgrade a live instance. Create a second new instance, configure applications to use the 
second instance and remove the old instance.

## Upgrading a running instance

Do not do this. It is possible and will eventually work. Application errors are likely if database migrations
are involved. Just don't.
