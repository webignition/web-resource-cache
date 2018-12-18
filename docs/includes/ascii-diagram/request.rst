::

    +--------------+                                                            +--------------+
    |              |                                                            |              |
    |              | POST http://localhost:8001/                                |              |
    |              | url=http://example.com/                                    |              |
    |              | callback=http://callback.example.com/                      |              |
    |              | headers={                                                  |              |
    |              |     "User-Agent":"Chrome, honest"                          |              |
    |              | }                                                          |              |
    |              | parameters={                                               |              |
    |              |     "cookies": {                                           |              |
    |              |     }                                                      |              |
    |              | }                                                          |              |
    |              |                                                            |              |
    |              |                                                            |              |
    |              |                                                            | Asynchronous |
    | Your         |                                                            | HTTP         |
    | application  | +--------------------------------------------------------> | retriever    |
    |              |                                                            |              |
    |              |                                                            |              |
    |              |                                                            |              |
    |              |                                                            |              |
    |              |                       HTTP 200 OK                          |              |
    |              |                       Content-Type: application/json       |              |
    |              |                                                            |              |
    |              |                       "118e35f631be802c41bec5c9dfb0f415"   |              |
    |              | <--------------------------------------------------------+ |              |
    +--------------+                                                            +--------------+
    +
