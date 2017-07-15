<?php
/**
 * Created by PhpStorm.
 * User: Frost Wong <frostwong@gmail.com>
 * Date: 2017/7/12
 * Time: 13:20
 */


namespace SubtlePHP\Middlewares\Guzzle;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;

/**
 * UriReplacer middleware for guzzlehttp/guzzle
 * @package SubtlePHP\Middlewares\Guzzle
 */
class UriReplacer
{
    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            if (isset($options['attributes'])) {
                $attributes = $options['attributes'];
                $uri = (string)$request->getUri();

                $parsedUri = $this->interpolate($uri, $attributes);

                $request = $request->withUri(new Uri($parsedUri), true);
            }
            return $handler($request, $options);
        };
    }

    private function interpolate($message, array $context = [])
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                $replace['{' . $key . '}'] = $value;
            }
        }

        return strtr(urldecode($message), $replace);
    }
}