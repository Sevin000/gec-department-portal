<?php
require_once __DIR__ . '/common.php';
start_session();
json_response(['authenticated' => !empty($_SESSION['admin_logged_in']), 'user' => $_SESSION['admin_username'] ?? null]);
