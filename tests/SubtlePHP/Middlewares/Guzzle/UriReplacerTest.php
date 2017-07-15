<?php
/**
 * Created by PhpStorm.
 * User: Frost Wong <frostwong@gmail.com>
 * Date: 15/07/2017
 * Time: 17:30
 */

namespace Tests\SubtlePHP\Middlewares\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use SubtlePHP\Middlewares\Guzzle\UriReplacer;

class UriReplacerTest extends TestCase
{
    public function testReplacePatternWithAttributesInOptionsOfRequest()
    {
        $replacer = new UriReplacer();
        $handler = HandlerStack::create();
        $handler->push($replacer);
        $client = new Client(['base_uri' => 'http://gank.io', 'handler' => $handler,]);
        $response = $client->request('GET', '/api/data/Android/{per_page}/{page}', ['attributes' => [
            'page' => 1,
            'per_page' => 10,
        ]]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getBody(), true)['error']);
    }


    public function testReplacePatternWithAttributesInOptionsOfClient()
    {
        $replacer = new UriReplacer();
        $handler = HandlerStack::create();
        $handler->push($replacer);
        $client = new Client(['base_uri' => 'http://gank.io', 'handler' => $handler, ['attributes' => [
            'page' => 1,
            'per_page' => 10,
        ]]]);
        $request = new Request('GET', '/api/data/Android/{per_page}/{page}');
        try {
            $response = $client->send($request);
        } catch (ClientException $e) {
            $response = $e->getResponse();
        }
        $this->assertEquals(404, $response->getStatusCode());
    }


    public function testReplacePatternWithAttributesInOptionsOfSendMethod()
    {
        $replacer = new UriReplacer();
        $handler = HandlerStack::create();
        $handler->push($replacer);
        $client = new Client(['base_uri' => 'http://gank.io', 'handler' => $handler,]);
        $request = new Request('GET', '/api/data/Android/{per_page}/{page}');
        $response = $client->send($request,  ['attributes' => [
            'page' => 1,
            'per_page' => 10,
        ]]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getBody(), true)['error']);
    }
}
