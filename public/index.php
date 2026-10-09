<?php

declare(strict_types=1);

/**
 * Front Controller Entrypoint
 * BPS Kabupaten Solok Selatan - Admin Dashboard & Backend API
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/routes.php';

$response = \App\Http\Router::dispatch();

if (is_string($response)) {
    echo $response;
}
