::

    +-------------+                                                             +--------------+
    |             |                     POST http://callback.example.com/       |              |
    |             |                     {                                       |              |
    |             |                       "request_id": "118e35f631be802c41b…", |              |
    |             |                       "status": "success",                  |              |
    |             |                       "headers": {                          |              |
    |             |                         "content-type": "text/html;"        |              |
    | Your        |                       },                                    |              |
    | callback    |                       "content": "PGRvY3R5cGUgaHRtbD4="     | Asynchronous |
    | handler     |                     }                                       | HTTP         |
    |             |                                                             | Retriever    |
    |             | <---------------------------------------------------------+ |              |
    +-------------+                                                             +--------------+
