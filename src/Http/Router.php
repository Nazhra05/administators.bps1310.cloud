<?php
declare(strict_types=1);

namespace App\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use RuntimeException;

class Router
{
    private static array $routes = [];
    private static array $groupStack = [];

    /**
     * Add a route with optional middlewares.
     */
    public static function add(string|array $methods, string $route, mixed $handler, array $middlewares = []): void
    {
        $methods = (array)$methods;
        $prefix = self::getCurrentPrefix();
        $groupMiddlewares = self::getCurrentMiddlewares();

        $fullPath = '/' . trim($prefix . '/' . trim($route, '/'), '/');
        if ($fullPath !== '/' && str_ends_with($fullPath, '/')) {
            $fullPath = rtrim($fullPath, '/');
        }

        $allMiddlewares = array_merge($groupMiddlewares, $middlewares);

        self::$routes[] = [
            'methods'     => array_map('strtoupper', $methods),
            'route'       => $fullPath,
            'handler'     => $handler,
            'middlewares' => $allMiddlewares,
        ];
    }

    public static function get(string $route, mixed $handler, array $middlewares = []): void
    {
        self::add('GET', $route, $handler, $middlewares);
    }

    public static function post(string $route, mixed $handler, array $middlewares = []): void
    {
        self::add('POST', $route, $handler, $middlewares);
    }

    public static function put(string $route, mixed $handler, array $middlewares = []): void
    {
        self::add('PUT', $route, $handler, $middlewares);
    }

    public static function delete(string $route, mixed $handler, array $middlewares = []): void
    {
        self::add('DELETE', $route, $handler, $middlewares);
    }

    /**
     * Group routes with common prefix and middlewares.
     */
    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = $attributes;
        $callback();
        array_pop(self::$groupStack);
    }

    private static function getCurrentPrefix(): string
    {
        $prefixes = [];
        foreach (self::$groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefixes[] = trim((string)$group['prefix'], '/');
            }
        }
        return empty($prefixes) ? '' : '/' . implode('/', $prefixes);
    }

    private static function getCurrentMiddlewares(): array
    {
        $middlewares = [];
        foreach (self::$groupStack as $group) {
            if (!empty($group['middleware'])) {
                $m = (array)$group['middleware'];
                $middlewares = array_merge($middlewares, $m);
            }
        }
        return $middlewares;
    }

    /**
     * Clear registered routes (useful for test resets).
     */
    public static function clear(): void
    {
        self::$routes = [];
        self::$groupStack = [];
    }

    /**
     * Dispatch the current HTTP request.
     */
    public static function dispatch(?string $httpMethod = null, ?string $uri = null): mixed
    {
        $httpMethod = strtoupper($httpMethod ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $rawUri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');

        // 1. Strip query string
        $path = parse_url($rawUri, PHP_URL_PATH) ?? '/';

        // 2. Strip APP_BASE_PATH if running in a subdirectory
        $basePath = rtrim((string)env('APP_BASE_PATH', ''), '/');
        if (!empty($basePath) && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
            if ($path === '' || !str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
        }

        // Clean trailing slash for consistency (except root '/')
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // 3. FastRoute Dispatcher
        $dispatcher = simpleDispatcher(function (RouteCollector $r) {
            foreach (self::$routes as $index => $routeData) {
                $r->addRoute($routeData['methods'], $routeData['route'], $index);
            }
        });

        $routeInfo = $dispatcher->dispatch($httpMethod, $path);

        $isApi = str_contains($path, '/api')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                http_response_code(404);
                if ($isApi) {
                    json_response([
                        'data'  => null,
                        'error' => [
                            'code'    => 'not_found',
                            'message' => 'Endpoint tidak ditemukan.',
                        ],
                    ], 404);
                }
                echo view('errors/404', [
                    'title'   => '404 - Halaman Tidak Ditemukan',
                    'message' => "Rute '{$path}' tidak ditemukan di server.",
                ], null);
                exit;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                http_response_code(405);
                header('Allow: ' . implode(', ', $allowedMethods));
                if ($isApi) {
                    json_response([
                        'data'  => null,
                        'error' => [
                            'code'    => 'method_not_allowed',
                            'message' => "Metode {$httpMethod} tidak diizinkan. Metode yang didukung: " . implode(', ', $allowedMethods),
                        ],
                    ], 405);
                }
                echo view('errors/404', [
                    'badge'   => '405 Error',
                    'title'   => '405 - Metode Tidak Diizinkan',
                    'message' => "Metode {$httpMethod} tidak diizinkan untuk rute ini.",
                ], null);
                exit;

            case Dispatcher::FOUND:
                $routeIndex = $routeInfo[1];
                $vars = $routeInfo[2];
                $matchedRoute = self::$routes[$routeIndex];

                return self::runPipeline($matchedRoute['middlewares'], $matchedRoute['handler'], $vars);
        }

        return null;
    }

    /**
     * Run middleware pipeline followed by target controller action.
     */
    private static function runPipeline(array $middlewares, mixed $handler, array $vars): mixed
    {
        $pipeline = array_reduce(
            array_reverse($middlewares),
            function ($next, $middleware) {
                return function ($vars) use ($middleware, $next) {
                    $instance = is_string($middleware) ? new $middleware() : $middleware;
                    if ($instance instanceof Middleware\MiddlewareInterface) {
                        return $instance->handle($vars, $next);
                    }
                    if (is_callable($instance)) {
                        return $instance($vars, $next);
                    }
                    throw new RuntimeException("Invalid middleware configured.");
                };
            },
            function ($vars) use ($handler) {
                return self::executeHandler($handler, $vars);
            }
        );

        $result = $pipeline($vars);

        if (is_array($result)) {
            json_response($result);
        }

        return $result;
    }

    /**
     * Execute route handler (Closure or [ControllerClass, 'action']).
     */
    private static function executeHandler(mixed $handler, array $vars): mixed
    {
        if (is_callable($handler)) {
            return call_user_func($handler, $vars);
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            return call_user_func([$controller, $method], $vars);
        }

        throw new RuntimeException("Format handler rute tidak valid.");
    }
}
