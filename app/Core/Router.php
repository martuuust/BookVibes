<?php

namespace App\Core;

class Router
{
    protected array $routes = [];
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();

        error_log("Resolving path: " . $path . ", method: " . $method); // Debug line

        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            error_log("No route found for path: " . $path . ", method: " . $method); // Debug line
            http_response_code(404);
            echo "Not Found";
            return;
        }

        if (is_string($callback)) {
            // Render View directly
            // Not implemented yet
        }

        if (is_array($callback)) {
            /** @var Controller $controller */
            $controller = new $callback[0]();
            $callback[0] = $controller;
        }

        echo call_user_func($callback, $this->request);
    }
}
