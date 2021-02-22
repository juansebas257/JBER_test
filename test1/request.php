<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class SimpleJsonRequest
{
    const REDIS_SERVER = '127.0.0.1';
    const REDIS_PORT = 6379;
    const REDIS_TTL = 30;//redis ttl in seconds
    const DEBUG = true;

    private static function makeRequest(string $method, string $url, array $parameters = null, array $data = null)
    {
        // connecting to redis server
        $objRedis = new Redis();
        $objRedis->connect( SimpleJsonRequest::REDIS_SERVER, SimpleJsonRequest::REDIS_PORT);

        // setting the key of the redis element as the method and url concat
        $redisKey = $method . '_' . $url;

        // if the request is cached, return it from cache
        $isCached = $objRedis->exists($redisKey);
        if($isCached) {
            if(SimpleJsonRequest::DEBUG) {
                echo 'reading cached<br>';
            }
            return $objRedis->get($redisKey);
        } else {
            // if is not cached, make direct request and save in redis cache
            if(SimpleJsonRequest::DEBUG) {
                echo 'reading direct<br>';
            }
            $request = SimpleJsonRequest::directRequest($method, $url, $parameters, $data);
            $objRedis->setex($redisKey, SimpleJsonRequest::REDIS_TTL, $request );
            return $request;
        }
    }

    private static function directRequest(string $method, string $url, array $parameters = null, array $data = null)
    {
        $opts = [
            'http' => [
                'method'  => $method,
                'header'  => 'Content-type: application/json',
                'content' => $data ? json_encode($data) : null
            ]
        ];

        $url .= ($parameters ? '?' . http_build_query($parameters) : '');
        return file_get_contents($url, false, stream_context_create($opts));
    }

    public static function get(string $url, array $parameters = null)
    {
        return json_decode(self::makeRequest('GET', $url, $parameters));
    }

    public static function post(string $url, array $parameters = null, array $data)
    {
        return json_decode(self::makeRequest('POST', $url, $parameters, $data));
    }

    public static function put(string $url, array $parameters = null, array $data)
    {
        return json_decode(self::makeRequest('PUT', $url, $parameters, $data));
    }

    public static function patch(string $url, array $parameters = null, array $data)
    {
        return json_decode(self::makeRequest('PATCH', $url, $parameters, $data));
    }

    public static function delete(string $url, array $parameters = null, array $data = null)
    {
        return json_decode(self::makeRequest('DELETE', $url, $parameters, $data));
    }
}

// EXAMPLE:
$url = 'https://jsonplaceholder.typicode.com/todos/1';
echo json_encode(SimpleJsonRequest::get($url), JSON_PRETTY_PRINT);