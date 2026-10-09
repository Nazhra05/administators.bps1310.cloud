<?php
declare(strict_types=1);

namespace App\Http\Middleware;

interface MiddlewareInterface
{
    /**
     * Handle an incoming request.
     *
     * @param array $vars Route parameters
     * @param callable $next Next middleware or controller handler
     * @return mixed
     */
    public function handle(array $vars, callable $next): mixed;
}
