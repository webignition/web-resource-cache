::

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
