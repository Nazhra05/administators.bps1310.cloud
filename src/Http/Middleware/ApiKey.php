<?php

declare(strict_types=1);

namespace App\Http\Middleware;

class ApiKey implements MiddlewareInterface
{
    public function handle(array $vars, callable $next): mixed
    {
        $expectedKey = env('API_KEY_APP_B') ?: env('BPS_API_KEY');

        // Look for header X-API-KEY in diverse server environments
        $providedKey = $_SERVER['HTTP_X_API_KEY']
            ?? $_SERVER['HTTP_API_KEY']
            ?? $_GET['api_key']
            ?? '';

        if (empty($expectedKey) || empty($providedKey) || !hash_equals((string)$expectedKey, (string)$providedKey)) {
            json_response([
                'data'  => null,
                'error' => [
                    'code'    => 'unauthorized',
                    'message' => 'API Key tidak valid atau tidak disertakan pada header X-API-KEY.',
                ],
            ], 401);
        }

        return $next($vars);
    }
}
