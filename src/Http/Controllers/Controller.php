<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Database;
use App\Support\Session;
use PDO;

abstract class Controller
{
    /**
     * Get database PDO instance.
     */
    protected function db(): PDO
    {
        return Database::pdo();
    }

    /**
     * Render view template.
     */
    protected function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        return view($template, $data, $layout);
    }

    /**
     * Return JSON response.
     */
    protected function json(mixed $data, int $status = 200, array $headers = []): never
    {
        json_response($data, $status, $headers);
    }

    /**
     * Redirect to another path.
     */
    protected function redirect(string $path, int $status = 302): never
    {
        redirect($path, $status);
    }

    /**
     * Get current authenticated user profile.
     */
    protected function user(): ?array
    {
        Session::start();
        $id = Session::get('admin_id');
        if (empty($id)) {
            return null;
        }

        return [
            'id'       => $id,
            'username' => Session::get('admin_username'),
            'role'     => Session::get('admin_role'),
            'email'    => Session::get('admin_email'),
            'unit_id'  => Session::get('admin_unit_id'),
        ];
    }
}
