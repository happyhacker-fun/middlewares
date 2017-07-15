# Do what needs to be done when you do a request with guzzle

## 1. Decide whether to retry when guzzle response is not OK

A service of 100 percent availability does not exist. So we must decide when to retry when a failure occurs.

Guzzle is a great HTTP client for PHP users and it provides a flexible mechanism to customize your request. When deciding when to retry, 
just tell guzzle whether to retry depending on response and when to retry after the 'failed' request.

## 2. Log both request and response (only when their bodies are readable for human)

## 3. Define your base_uri and pattern once with '{}' marks and UriReplacer will do the rest.

### Installation

`composer require subtlephp/middlewares`


### Prerequisites

Logger middleware depends on 2 constants, `REQUEST_ID` and `LOG_DIR`, but they are not must.
`REQUEST_LOG` equals `''` and `LOG_DIR` equals `__DIR__` by default.

### Usage

```php
<?php

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use SubtlePHP\Middlewares\Guzzle\Retry;
use SubtlePHP\Middlewares\Guzzle\UriReplacer;
use SubtlePHP\Middlewares\Guzzle\Logger;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$handlerStack = new HandlerStack();
$maxRetryTimes = 2;
$delay = 100;

$retryMiddleware = Middleware::retry(Retry::decider($maxRetryTimes), Retry::delay($delay));
$middlewares = [
    new UriReplacer(),
    new Logger(),
    $retryMiddleware,
];
$handlerStack->push($retryMiddleware);
$base_uri = 'http://gank.io';
$pattern = '/api/data/Android/{per_page}/{page}';
$client = new Client(['base_uri' => $base_uri, 'handler' => $handlerStack, [
    'attributes' => [
        'page' => 1,
        'per_page' => 10,
    ],
]]);
$request = new Request('GET', $pattern);
$response = $client->send($request);

```
