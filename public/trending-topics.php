<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$file = __DIR__ . '/../scripts/trending-topics.json';

if (!is_file($file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Topics not generated yet']);
    exit;
}

readfile($file);