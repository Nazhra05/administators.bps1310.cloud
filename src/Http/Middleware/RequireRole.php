<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Session;

class RequireRole implements MiddlewareInterface
{
    private array $allowedRoles;

    public function __construct(array $allowedRoles = ['ADMIN IT', 'ADMIN DASHBOARD'])
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function handle(array $vars, callable $next): mixed
    {
        Session::start();

        $role = (string)Session::get('admin_role', '');

        if (!in_array($role, $this->allowedRoles, true)) {
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api')
                || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isApi) {
                json_response([
                    'data'  => null,
                    'error' => [
                        'code'    => 'forbidden',
                        'message' => 'Anda tidak memiliki hak akses yang cukup.',
                    ],
                ], 403);
            }

            http_response_code(403);
            echo view('errors/403', [
                'title'   => '403 - Akses Ditolak',
                'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini.',
            ], null);
            exit;
        }

        return $next($vars);
    }
}
