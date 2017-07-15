<?php
/**
 * Created by PhpStorm.
 * User: Frost Wong <frostwong@gmail.com>
 * Date: 2017/7/12
 * Time: 15:33
 */

namespace SubtlePHP\Middlewares\Guzzle;

use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\TransferStats;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Ygritte\ContentType;

/**
 * Logger middleware for guzzlehttp/guzzle
 * @package SubtlePHP\Middlewares\Guzzle
 */
class Logger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $logDir;

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
                $req = $this->logRequest($request);
                $log = array_merge(['cost#' . $cost], $req, ['httpCode#0', 'reasonPhrase#connectFail', 'response#']);
                $line = implode('#|', $log);
                $this->makeRpcLogger()->info($line);
            }

            return $promise->then(
                function (ResponseInterface $response) use ($request, $cost) {
                    $req = $this->logRequest($request);
                    $res = $this->logResponse($response);
                    $log = array_merge(['cost#' . $cost], $req, $res);
                    $line = implode('#|', $log);
                    if ((int)$response->getStatusCode() >= 500) {
                        $this->makeRpcLogger()->error($line);
                    } else if ((int)$response->getStatusCode() >= 300) {
                        $this->makeRpcLogger()->warning($line);
                    } else {
                        $this->makeRpcLogger()->info($line);
                    }

                    return $response;
                }
            );
        };
    }

    private function logRequest(RequestInterface $request)
    {
        $arr = ['curl', '-X'];
        $arr[] = $request->getMethod();
        foreach ($request->getHeaders() as $name => $values) {
            foreach ((array)$values as $value) {
                $arr[] = '-H';
                $arr[] = "'$name:$value'";
            }
        }


        if (ContentType::isRequestReadable($request)) {
            $body = (string)$request->getBody();
            if ($body) {
                $arr[] = '-d';
                $arr[] = "'$body'";
            }
        }

        $uri = (string)$request->getUri();
        $arr[] = "'$uri'";

        return [
            'curl#' . implode(' ', $arr)
        ];
    }

    private function makeRpcLogger()
    {
        if (!defined('REQUEST_ID')) {
            define('REQUEST_ID', '');
        }

        if (!defined('LOG_DIR')) {
            $this->logDir = __DIR__;
        } else {
            $this->logDir = LOG_DIR;
        }

        if (!$this->logger) {
            $handler = new RotatingFileHandler($this->logDir . '/guzzle.log');
            $logger = new \Monolog\Logger('guzzle');
            $formatterWithRequestId = new LineFormatter(
                '[%datetime%] [' . REQUEST_ID . "] %channel%.%level_name%: %message% %context% %extra%\n",
                LineFormatter::SIMPLE_DATE,
                false,
                true
            );
            $handler->setFormatter($formatterWithRequestId);
            $logger->pushHandler($handler);
            $this->logger = $logger;
        }

        return $this->logger;
    }

    private function logResponse(ResponseInterface $response)
    {
        $data = [
            'httpCode#' . $response->getStatusCode(),
            'reasonPhrase' . $response->getReasonPhrase(),
        ];

        if (ContentType::isResponseReadable($response)) {
            $data[] = 'response#' . (string)$response->getBody();
        } else {
            $data[] = 'response#';
        }

        return $data;
    }


}