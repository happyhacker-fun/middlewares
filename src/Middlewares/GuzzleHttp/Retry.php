<?php
/**
 * Created by PhpStorm.
 * User: Frost Wong <frostwong@gmail.com>
 * Date: 12/06/2017
 * Time: 13:46
 */

namespace SubtlePHP\Middlewares\GuzzleHttp;


use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Retry middleware for guzzlehttp/guzzle
 *
 * @package SubtlePHP\Middlewares\GuzzleHttp
 */
class Retry
{
    /**
     * Retry only when connecting failed (ConnectException is threw) or http code is 503 (Service Unavailable)
     *
     * @param int $maxTimes
     * @return \Closure
     */
    public static function decider($maxTimes = 2)
    {
        return function ($retries, Request $request, Response $response = null, RequestException $exception = null) use ($maxTimes) {
            if ($retries >= $maxTimes) {
                return false;
            }

            if ($exception instanceof ConnectException) {
                return true;
            }

            if ($response && (int)$response->getStatusCode() === 503) {
                return true;
            }

            return false;
        };
    }

    /**
     * Time(ms) to delay before next try.
     *
     * @param int $delay
     * @return \Closure
     */
    public static function delay($delay = 100)
    {
        return function ($retries) use ($delay) {
            return $retries * $delay;
        };
    }
}