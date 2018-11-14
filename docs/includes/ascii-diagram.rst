::

    +---------------+                                                            +-----------------+
    |               |                                                            |                 |
    |               | POST http://127.0.0.1:8001/                                |                 |
    |               | url=http://example.com/                                    |                 |
    |               | callback=http://callback.example.com/                      |                 |
    |               | headers[user-agent]='Chrome, honest'                       | Asynchronous    |
    | Your          |                                                            | HTTP            |
    | application   | +--------------------------------------------------------> | retriever       |
    |               |                                                            |                 |
    +---------------+                                                            +-----------------+

                       ... some time passes ...


    +---------------+                                                            +-----------------+
    |               |                    POST http://callback.example.com/       |                 |
    |               |                    {                                       |                 |
    |               |                      "request_id": "118e35f631be802c41b…", |                 |
    |               |                      "status": "success",                  |                 |
    |               |                      "headers": {                          |                 |
    |               |                        "content-type": "text/html;"        |                 |
    | Your          |                      },                                    |                 |
    | callback      |                      "content": "<doctype html>……</html>"  | Asynchronous    |
    | handler       |                    }                                       | HTTP            |
    |               |                                                            | Retriever       |
    |               | <--------------------------------------------------------+ |                 |
    +---------------+                                                            +-----------------+
