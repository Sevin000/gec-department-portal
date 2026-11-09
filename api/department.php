<?php
require_once __DIR__ . '/common.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') method_not_allowed(['GET']);
try {
    $pdo = db();
    $department = $pdo->query('SELECT id, name, short_name, description FROM departments ORDER BY id LIMIT 1')->fetch();
    if (!$department) json_response(['success' => false, 'error' => 'Department not found.'], 404);
    $department['programs'] = $pdo->query('SELECT p.name FROM programmes p JOIN department_programmes dp ON dp.programme_id = p.id WHERE dp.department_id = ' . (int)$department['id'] . ' ORDER BY p.name')->fetchAll(PDO::FETCH_COLUMN);
    $department['facilities'] = $pdo->query('SELECT name FROM facilities WHERE department_id = ' . (int)$department['id'] . ' ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
    $department['researchAreas'] = $pdo->query('SELECT name FROM research_areas WHERE department_id = ' . (int)$department['id'] . ' ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
    json_response(['success' => true, 'department' => $department]);
} catch (Throwable $e) { error_log($e->getMessage()); json_response(['success' => false, 'error' => 'A server error occurred.'], 500); }
