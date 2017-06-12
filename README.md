## Decide when to retry when guzzle response is not OK


A service of 100 percent availability does not exist. So we must decide when to retry when a failure occurs.

Guzzle is a great HTTP client for PHP users and it provides a flexible mechanism to customize your request. When deciding when to retry, 
just tell guzzle whether to retry depending on response and when to retry after the 'failed' request.


### Installation

`composer require subtlephp/guzzle-retry-middleware`

### Usage

```php
<?php

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use SubtlePHP\Middlewares\GuzzleHttp\Retry;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$handlerStack = new HandlerStack();
$maxRetryTimes = 2;
$delay = 100;

$retryMiddleware = Middleware::retry(Retry::decider($maxRetryTimes), Retry::delay($delay));
$handlerStack->push($retryMiddleware);

$client = new Client(['base_uri' => 'https://google.com', 'handler' => $handlerStack]);
$request = new Request('GET', '/ncr');
$response = $request->send();
// do what you want below
```
