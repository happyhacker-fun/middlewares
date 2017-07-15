<?php
/**
 * Created by PhpStorm.
 * User: Frost Wong <frostwong@gmail.com>
 * Date: 15/07/2017
 * Time: 21:07
 */

namespace Tests\SubtlePHP\Middlewares\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use SubtlePHP\Middlewares\Guzzle\Logger;
use SubtlePHP\Middlewares\Guzzle\Retry;
use SubtlePHP\Middlewares\Guzzle\UriReplacer;

class RetryTest extends TestCase
{
    public function testRetryOnTimeout()
    {
        $logger = new Logger();
        $replacer = new UriReplacer();
        $start = time();
        $retry = Middleware::retry(Retry::decider(5), Retry::delay(1000));
        $handler = HandlerStack::create();
        $handler->push($logger);
        $handler->push($replacer);
        $handler->push($retry);
        $client = new Client(['base_uri' => 'http://gank.io', 'handler' => $handler, 'timeout' => '0.01']);
        $request = new Request('GET', '/api/data/Android/{per_page}/{page}');
        try {
            $response = $client->send($request, ['attributes' => [
                'page' => 1,
                'per_page' => 10,
            ]]);
        } catch (ConnectException $e) {
            $response = $e->getResponse();
            $this->assertNull($response);
        }
        $this->assertNull($response);

        $end = time();
        $this->assertGreaterThan(5, $end - $start);
    }
}
