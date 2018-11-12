# Asychronous HTTP Retriever

Service for retrieving HTTP resources asynchronously. Self-hosted within a lovely collection of [docker containers](https://en.wikipedia.org/wiki/Docker_(software)).

**Short description**:<br> 
Send a `POST` request containing `url` and `callback` values.
Content for the given `url` will be retrieved *eventually* and `POSTed` back to the specified `callback` url.

**Why?**<br>
Pretty much every modern programming ecosystem provides a means for making HTTP requests and handling the resulting responses.
You already get synchronous HTTP out the box, possibly asychronous HTTP as well.

Using whatever HTTP functionality your progamming ecosystem provides is fine most of the time.

Want to retrieve the content of arbitrary urls often? No, you probably don't. But if you do, you quickly run into edge cases.
Temporary [service unavailability](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/503),
intermittent [internal server errors](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/500), 
unpredictable [HTTP 429](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429) responses.

To reliably retrieve an arbitrary HTTP resource, you need to able to retry after a given period. 
You introduce state (remembering *what* to retrieve) and you need something to handle doing so *at the right time*
(some form of delayable background job processing).

**Production readiness**<br>
Not production ready

## Requirements

You'll need `docker` and `docker-compose` present on the host you want to run this on.

Developed and tested against `docker` 18.06.1-ce and `docker-compose` 1.22.0.

## Installation

Please read [the installation guide](/INSTALL.md).

## Configuration

There are some configuration values you must set before installing. There are some configuration values
that can only be set before installing. 

Please read [the configuration guide](/CONFIG.md).
