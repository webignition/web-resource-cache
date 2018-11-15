# Asynchronous HTTP Retriever

Service for retrieving HTTP resources asynchronously. Self-hosted within a lovely collection of 
[docker containers](https://en.wikipedia.org/wiki/Docker_(software)).

Send a `POST` request containing `url`, `callback` and (optionally) `header` values. Content for the given
`url` will be retrieved *eventually* and sent in a `POST` request to the specified `callback` url.

## Requirements

You'll need `docker` and `docker-compose` present on the host you want to run this on.

Developed and tested against `docker` 18.06.1-ce and `docker-compose` 1.22.0.

## Documentation

- [Documentation home][documentation-home]
- [Overview][documentation-overview]
- [Getting started][documentation-getting-started]
    - [Getting the code][documentation-getting-the-code]
    - [Creating your configuration][documentation-creating-your-configuration]
    - [Installation][documentation-installation]
- [Request-Response Cycle][documentation-request-response-cycle]
- [Requesting a Resource][documentation-requesting-a-resource]
- [Callback Responses][documentation-callback-responses]
- [Upgrading][documentation-upgrading]
- [Configuration][documentation-configuration]

## Reporting An Issue, Creating a Feature Request

[Report a bug/issue/fault][create-bug] if something does not work the way it should.

[Create a feature request][create-feature-request] if something new is needed.

## Developing

Feel free to fork and make whatever changes you like.

### Create a Development Installation

To run a development copy:
 
- [get the code][documentation-getting-the-code]
- [create your configuration][documentation-creating-your-configuration]
- create a [simple installation][documentation-simple-installation]

### Branching Conventions

**Branch from master**<br>
Always branch from `master`.
 
**Naming**<br>
Append the issue number to the branch name.<br>
Example: `remove-md-documentation-290`.

If there is no existing issue that you are addressing, first [report a bug][create-bug] or 
[create a feature request][create-feature-request].

### Testing

To run the full test suite, refer to the [travis-ci build script][travis-build-script] `script` entry.
Run the full test suite in the same manner as the travis-ci build.

To execute an individual test or set of tests from your host:

```bash
cd docker
docker-compose exec -T --env APP_ENV=test app-web \
./vendor/bin/phpunit tests/<path to test class>
```

To execute an individual test or set of tests from within your container:

```bash
cd docker
docker-compose exec app-web /bin/bash
APP_ENV=test ./vendor/bin/phpunit tests/<path to test class>
```

Ensure your development environment database is empty before running functional tests.

### Creating Pull Requests

Create pull requests against `master`.

A pull request must always reference an existing issue. The issue serves to document the matter being addressed.

Pull requests that change functionality must include new or updated tests that demonstrate the correctness of 
the change.

Always run the full test suite locally before creating a pull request.
Address any issues that arise before creating a pull request.

[documentation-home]: https://async-http-retriever.webignition.net/en/latest/
[documentation-overview]: https://async-http-retriever.webignition.net/en/latest/overview.html
[documentation-getting-started]: https://async-http-retriever.webignition.net/en/latest/getting-started.html
[documentation-getting-the-code]: https://async-http-retriever.webignition.net/en/latest/getting-started.html#getting-the-code
[documentation-creating-your-configuration]: https://async-http-retriever.webignition.net/en/latest/getting-started.html#creating-your-configuration
[documentation-installation]: https://async-http-retriever.webignition.net/en/latest/getting-started.html#installation
[documentation-simple-installation]: https://async-http-retriever.webignition.net/en/latest/getting-started.html#simple-installation
[documentation-request-response-cycle]: https://async-http-retriever.webignition.net/en/latest/request-response-cycle.html
[documentation-requesting-a-resource]: https://async-http-retriever.webignition.net/en/latest/requesting-a-resource.html
[documentation-callback-responses]: https://async-http-retriever.webignition.net/en/latest/callback-responses.html
[documentation-upgrading]: https://async-http-retriever.webignition.net/en/latest/upgrading.html
[documentation-configuration]: https://async-http-retriever.webignition.net/en/latest/configuration.html
[create-bug]: https://github.com/webignition/async-http-retriever/issues/new?labels=bug&template=issue-report.md
[create-feature-request]: https://github.com/webignition/async-http-retriever/issues/new?labels=enhancement&template=feature_request.md
[travis-build-script]: https://github.com/webignition/async-http-retriever/blob/master/.travis.yml