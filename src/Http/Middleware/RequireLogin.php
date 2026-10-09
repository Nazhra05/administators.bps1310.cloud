<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Session;

class RequireLogin implements MiddlewareInterface
{
    public function handle(array $vars, callable $next): mixed
    {
        Session::start();

        $adminId = Session::get('admin_id');
        if (empty($adminId)) {
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api')
                || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

            if ($isApi) {
                json_response([
                    'data'  => null,
                    'error' => [
                        'code'    => 'unauthorized',
                        'message' => 'Autentikasi diperlukan untuk mengakses sumber daya ini.',
                    ],
                ], 401);
            }

            flash('error', 'Silakan login terlebih dahulu untuk mengakses dashboard.');
            header('Location: login.php');
            exit;
        }

        $role = (string)Session::get('admin_role', '');
        if ($role === 'USER APP PORTAL' || $role === 'user') {
            Session::destroy();
            flash('error', 'Akun pegawai portal tidak memiliki hak akses ke Dashboard Administrator.');
            header('Location: login.php');
            exit;
        }

        return $next($vars);
    }
}
