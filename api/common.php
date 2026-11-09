<?php
require_once __DIR__ . '/../config.php';

function start_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        session_start();
    }
}
function json_response(array $payload, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function read_json(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    if (!is_array($data)) json_response(['success' => false, 'error' => 'Request body must contain a valid JSON object.'], 400);
    return $data;
}
function require_login(): void {
    start_session();
    if (empty($_SESSION['admin_logged_in'])) json_response(['success' => false, 'error' => 'Authentication required. Please log in.'], 401);
}
function method_not_allowed(array $allowed): never {
    header('Allow: ' . implode(', ', $allowed));
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}
