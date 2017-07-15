<?php
/**
 * Created by PhpStorm.
 * User: Frost Wong <frostwong@gmail.com>
 * Date: 15/07/2017
 * Time: 20:21
 */

namespace Tests\SubtlePHP\Middlewares\Guzzle;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use SubtlePHP\Middlewares\Guzzle\Logger;
use PHPUnit\Framework\TestCase;
use SubtlePHP\Middlewares\Guzzle\UriReplacer;

class LoggerTest extends TestCase
{
    public function testLoggerWithoutConst()
    {
        $logger = new Logger();
        $replacer = new UriReplacer();
        $handler = HandlerStack::create();
        $handler->push($logger);
        $handler->push($replacer);
        $client = new Client(['base_uri' => 'http://gank.io', 'handler' => $handler,]);
        $request = new Request('GET', '/api/data/Android/{per_page}/{page}');
        $response = $client->send($request,  ['attributes' => [
            'page' => 1,
            'per_page' => 10,
        ]]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getBody(), true)['error']);
        $logFile = __DIR__ . '/../../../../src/Middlewares/Guzzle/guzzle-' . (new DateTime())->format('Y-m-d') . '.log';
        $this->assertFileExists($logFile);
        shell_exec('rm ' . $logFile);
    }

    public function testLoggerWithConst()
    {
        define('LOG_DIR', __DIR__ . '/aaa');
        $logger = new Logger();
        $replacer = new UriReplacer();
        $handler = HandlerStack::create();
        $handler->push($logger);
        $handler->push($replacer);
        $client = new Client(['base_uri' => 'http://gank.io', 'handler' => $handler,]);
        $request = new Request('GET', '/api/data/Android/{per_page}/{page}');
        $response = $client->send($request,  ['attributes' => [
            'page' => 1,
            'per_page' => 10,
        ]]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getBody(), true)['error']);
        $logFile = LOG_DIR . '/guzzle-' . (new DateTime())->format('Y-m-d') . '.log';
        $this->assertFileExists($logFile);
        shell_exec('rm -r -f '  . LOG_DIR);
    }
}
