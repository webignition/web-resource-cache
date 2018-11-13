# Requesting a resource

## Parameters

Send a `POST` request to your instance. Include the `url` of the resource to be retrieved
and the `callback` URL to where the response should be sent. 

You can optionally provide a `headers` parameter defining headers to send when retrieving the resource.

The collection of headers can contain whatever keys and values you need to satisfy a request. This might include
specifying the [`User-Agent`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent),
passing along [`Authorization`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Authorization) or
setting [`Cookies`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cookie). 

| Name | Purpose | Required? | Example |
| :--- | :--- | :--- | :--- |
| `url` | URL of the resource to be retrieved | Yes | `http://example.com/` |
| `callback` | URL to which the resource should be sent | Yes | `http://callback.example.com/` |
| `headers` | Key:value collection sent when retrieving the resource| No | -- |

### Curl example without headers

```bash
curl -d "url=http://example.com/&callback=http://callback.example.com" -X POST http://localhost:8001/
"118e35f631be802c41bec5c9dfb0f415"
```

### Curl example with headers

```bash
curl -d "url=http://example.com/&callback=http://callback.example.com/&headers[key1]=value1&headers[key2]=value2" -X POST http://localhost:8001/
"4a1b037ac72a171dd1bc474a3b283aba"
```

# Understanding the response

## Successful response (200)

The response body (`"118e35f631be802c41bec5c9dfb0f415"` in the first example, `"4a1b037ac72a171dd1bc474a3b283aba"` 
in the second example) is a json-encoded request ID.

The request ID is unique to the combination of `url` and `headers`.

Store the request ID in *your* application. The request ID is POSTed with the response to the given `callback` URL.
Use the request ID to map the response you receive to the request that you made.

## Bad request (400)

Your request will receive a `HTTP 400` response if:

- `url` is empty
- `callback` is empty
- `callback` is not valid (which depends on your configuration for allowed callback host names)

## Callbacks

A request to retrieve a resource will be followed up (*eventually*) by a `POST` request to the given
`callback` URL.

The body of the request is a `application/json`-encoded [response object](/docs/callback-responses.md).
