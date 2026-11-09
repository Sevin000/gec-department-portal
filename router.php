<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routes = [
    '/api/items' => __DIR__ . '/api/items.php',
    '/api/department' => __DIR__ . '/api/department.php',
    '/api/login' => __DIR__ . '/api/login.php',
    '/api/logout' => __DIR__ . '/api/logout.php',
    '/api/session' => __DIR__ . '/api/session.php'
];
if (isset($routes[$path])) { require $routes[$path]; return true; }
if (preg_match('#^/api/items/(\d+)$#', $path, $matches)) { $_GET['id'] = $matches[1]; require __DIR__ . '/api/items.php'; return true; }
if ($path === '/' || $path === '/index.html') { readfile(__DIR__ . '/index.html'); return true; }
$file = realpath(__DIR__ . $path);
if ($file && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && is_file($file)) { return false; }
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success' => false, 'error' => 'Route not found.']);
