<?php
declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Csrf;

class VerifyCsrf implements MiddlewareInterface
{
    public function handle(array $vars, callable $next): mixed
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
            if (!Csrf::verify()) {
                http_response_code(403);

                $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api')
                    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

                if ($isApi) {
                    json_response([
                        'data'  => null,
                        'error' => [
                            'code'    => 'csrf_invalid',
                            'message' => 'Token keamanan CSRF tidak valid atau sesi telah berakhir.',
                        ],
                    ], 403);
                }

                echo view('errors/403', [
                    'title'   => '403 - Token Keamanan Tidak Valid',
                    'message' => 'Token CSRF tidak valid atau formulir telah kedaluwarsa. Silakan muat ulang halaman.',
                ], null);
                exit;
            }
        }

        return $next($vars);
    }
}
