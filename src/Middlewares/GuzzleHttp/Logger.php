<?php
/**
 * Created by PhpStorm.
 * User: Frost Wong <frostwong@gmail.com>
 * Date: 2017/7/12
 * Time: 15:33
 */

namespace Bran\GuzzleMiddleware;


use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\TransferStats;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ygritte\ContentType;

class Logger
{
    /**
     * @var LoggerInterface
     */
    private static $rpcLogger;

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options)
        use ($handler) {
            $cost = 0;
            $options['on_stats'] = function (TransferStats $stats) use (&$cost) {
                $cost = $stats->getTransferTime();
            };
            $promise = $handler($request, $options);

            if (get_class($promise) === RejectedPromise::class) {
                $req = self::logRequest($request);
                $log = array_merge(['cost#' . $cost], $req, ['httpCode#0', 'reasonPhrase#connectFail', 'response#']);
                $line = implode('#|', $log);
                self::makeRpcLogger()->info($line);
            }

            return $promise->then(
                function (ResponseInterface $response) use ($request, $cost) {
                    $req = self::logRequest($request);
                    $res = self::logResponse($response);
                    $log = array_merge(['cost#' . $cost], $req, $res);
                    $line = implode('#|', $log);
                    if ((int)$response->getStatusCode() >= 500) {
                        self::makeRpcLogger()->error($line);
                    } else if ((int)$response->getStatusCode() >= 300) {
                        self::makeRpcLogger()->warning($line);
                    } else {
                        self::makeRpcLogger()->info($line);
                    }

                    return $response;
                }
            );
        };
    }

    protected static function logRequest(RequestInterface $r)
    {
        $arr = ['curl', '-X'];
        $arr[] = $r->getMethod();
        foreach ($r->getHeaders() as $name => $values) {
            foreach ((array)$values as $value) {
                $arr[] = '-H';
                $arr[] = "'$name:$value'";
            }
        }

        $contentType = $r->getHeader('Content-Type');
        if (ContentType::isReadable($contentType[0])) {
            $body = (string)$r->getBody();
            if ($body) {
                $arr[] = '-d';
                $arr[] = "'$body'";
            }
        }

        $uri = (string)$r->getUri();
        $arr[] = "'$uri'";

        return [
            'curl#' . implode(' ', $arr)
        ];
    }

    private static function makeRpcLogger()
    {
        if (!defined('REQUEST_ID')) {
            define('REQUEST_ID', Uuid::uuid4());
        }
        if (!self::$rpcLogger) {
            $handler = new RotatingFileHandler('rpc.log');
            $logger = new \Monolog\Logger('rpc');
            $formatterWithRequestId = new LineFormatter(
                '[%datetime%] [' . REQUEST_ID . "] %channel%.%level_name%: %message% %context% %extra%\n",
                LineFormatter::SIMPLE_DATE,
                false,
                true
            );
            $handler->setFormatter($formatterWithRequestId);
            $logger->pushHandler($handler);
            self::$rpcLogger = $logger;
        }

        return self::$rpcLogger;
    }

    protected static function logResponse(ResponseInterface $response)
    {
        $contentType = $response->getHeader('Content-Type');
        $data = [
            'httpCode#' . $response->getStatusCode(),
            'reasonPhrase' . $response->getReasonPhrase(),
        ];

        if (ContentType::isReadable($contentType[0])) {
            $data[] = 'response#' . (string)$response->getBody();
        } else {
            $data[] = 'response#';
        }

        return $data;
    }
}