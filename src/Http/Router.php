<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Simple HTTP router for matching requests to handlers
 *
 * Supports route parameters (e.g., /books/{id}) and dispatches
 * requests to appropriate controller methods.
 */
class Router
{
    /**
     * Registered routes by HTTP method
     *
     * @var array<string, array<string, callable>>
     */
    private array $routes = [];

    /**
     * Register a GET route
     *
     * @param string $path Route path (e.g., /books/{id})
     * @param callable $handler Route handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Register a POST route
     *
     * @param string $path Route path (e.g., /books/{id}/borrow)
     * @param callable $handler Route handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Register a PUT route
     *
     * @param string $path Route path
     * @param callable $handler Route handler
     */
    public function put(string $path, callable $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    /**
     * Register a DELETE route
     *
     * @param string $path Route path
     * @param callable $handler Route handler
     */
    public function delete(string $path, callable $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    /**
     * Match a request to a registered route
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array{handler: callable, params: array<string, string>}|null Matched route info or null
     */
    public function match(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $path => $handler) {
            $pattern = $this->convertPathToRegex($path);

            if (preg_match($pattern, $uri, $matches) === 1) {
                // Extract named parameters
                $params = array_filter(
                    $matches,
                    fn ($key): bool => is_string($key),
                    ARRAY_FILTER_USE_KEY
                );

                return [
                    'handler' => $handler,
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    /**
     * Dispatch a request to the appropriate handler
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return string Response from handler
     */
    public function dispatch(string $method, string $uri): string
    {
        $match = $this->match($method, $uri);

        if ($match === null) {
            return Response::notFound('Route not found');
        }

        try {
            $handler = $match['handler'];
            $params = $match['params'];

            // Call handler with parameters
            return (string) $handler($params);
        } catch (\Throwable $e) {
            return Response::internalError($e->getMessage());
        }
    }

    /**
     * Convert route path pattern to regex
     *
     * Converts paths like /books/{id} to regex patterns like #^/books/(?P<id>[^/]+)$#
     *
     * @param string $path Route path with {param} placeholders
     * @return string Regex pattern
     */
    private function convertPathToRegex(string $path): string
    {
        // Escape forward slashes for regex
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            fn (array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $path
        );

        return '#^' . $pattern . '$#';
    }
}
