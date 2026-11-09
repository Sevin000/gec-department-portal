<?php
require_once __DIR__ . '/common.php';
start_session();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') method_not_allowed(['POST']);
$input = read_json();
$username = trim((string)($input['username'] ?? ''));
$password = (string)($input['password'] ?? '');
if ($username === '' || $password === '') json_response(['success' => false, 'error' => 'Username and password are required.'], 400);
if (!hash_equals(ADMIN_USERNAME, $username) || !hash_equals(ADMIN_PASSWORD, $password)) json_response(['success' => false, 'error' => 'Invalid username or password.'], 401);
session_regenerate_id(true);
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_username'] = $username;
json_response(['success' => true, 'message' => 'Login successful.', 'user' => ['username' => $username]]);
