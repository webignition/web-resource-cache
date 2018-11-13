# Callback responses

A request to retrieve a resource will be followed up (*eventually*) by a `POST` request to the given
`callback` URL.

The body of the request is a `application/json`-encoded response object.

## Response object properties

| Name | Purpose | Example |
| :--- | :--- | :--- |
| `request_id` | Unique identifier, returned when [requesting a resource](/docs/requesting-a-resource.md) | `118e35f631be802c41bec5c9dfb0f415` |
| `status` | Whether the resource could be retrieved.<br><br>`success`: retrieval succeeded<br>`failed`: retrieval failed | `success` |
| `failure_type` | For `status=failed` responses only.<br><br>`http`: HTTP error<br>`curl`: curl error<br>`unknown`: unknown error  | `http` |
| `status_code` | For `status=failed,failure_type=http,curl` responses only.<br><br>The [HTTP status code](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status) or [curl code](https://curl.haxx.se/libcurl/c/libcurl-errors.html) encountered | `404` |
| `headers` | For `status=success` responses only.<br><br>The retrieved resource headers. | `{"content-type": "text/html; charset=utf-8" }` |
| `content` | For `status=success` responses only.<br><br>The retrieved resource content. | `<doctype html><html><body></body></html>` |

### Success example

```json
{
  "request_id": "118e35f631be802c41bec5c9dfb0f415",
  "status": "success",
  "headers": {
    "content-type": "text/html; charset=utf-8",
    "content-length": 40,
    "cache-control": "public, max-age=60"
  },
  "content": "<doctype html><html><body></body></html>"
}
```

### HTTP failure example (404 Not Found)

```json
{
  "request_id": "118e35f631be802c41bec5c9dfb0f415",
  "status": "failed",
  "failure_type": "http",
  "status_code": 404
}
```

### Curl failure example (operation timed out)

```json
{
  "request_id": "118e35f631be802c41bec5c9dfb0f415",
  "status": "failed",
  "failure_type": "curl",
  "status_code": 28
}
```

### Unknown failure example

```json
{
  "request_id": "118e35f631be802c41bec5c9dfb0f415",
  "status": "failed",
  "failure_type": "unknown"
}
```
