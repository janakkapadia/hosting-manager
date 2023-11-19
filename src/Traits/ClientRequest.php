<?php

namespace JanakKapadia\HostingManager\Traits;

use Exception;
use Illuminate\Support\Facades\Http;

trait ClientRequest
{
    /**
     * Make an HTTP request.
     *
     * @param string $route Route name mapped in self::$routes.
     * @param string $method The HTTP method to send (GET, POST, PUT, DELETE).
     * @param array $params Request parameters.
     * @return mixed Array or object (deserialize JSON).
     */
    private function request(string $route, string $method, array $params): mixed
    {
        $uri = $this->routes[$route];
        // 'RESTful' URLs.
        if (str_contains($uri, "{")) {
            foreach ($params as $key => $value) {
                $uri = str_replace("{" . $key . "}", (string)$value, $uri);
            }
        }

        $url = $this->baseUrl . $uri;

        // Prepare the request header
        $headers = $this->headers;

        // Make the HTTP request.
        return $this->guzzle($url, $method, $headers, $params);
    }

    private function guzzle($url, $method, $request_headers, $params)
    {
        return Http::withHeaders($request_headers)->$method($url, $params)->json();
    }

    /**
     * @throws Exception
     */
    private function get(string $route, array $params = [])
    {
        return $this->request($route, "GET", $params);
    }

    /**
     * @throws Exception
     */
    private function post(string $route, array $params = [])
    {
        return $this->request($route, "POST", $params);
    }

    /**
     * @throws Exception
     */
    private function put(string $route, array $params = [])
    {
        return $this->request($route, "PUT", $params);
    }

    /**
     * @throws Exception
     */
    private function patch(string $route, array $params = [])
    {
        return $this->request($route, "PATCH", $params);
    }

    /**
     * @throws Exception
     */
    private function delete(string $route, array $params = [])
    {
        return $this->request($route, "DELETE", $params);
    }
}