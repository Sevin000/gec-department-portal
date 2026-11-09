<?php
require_once __DIR__ . '/common.php';
$method = $_SERVER['REQUEST_METHOD'];

function faculty_rows(PDO $pdo, string $q = ''): array {
    $sql = "SELECT f.id, f.name, f.designation, GROUP_CONCAT(s.name ORDER BY s.name SEPARATOR ',') AS specializations
            FROM faculty f
            LEFT JOIN faculty_specializations fs ON fs.faculty_id = f.id
            LEFT JOIN specializations s ON s.id = fs.specialization_id";
    $params = [];
    if ($q !== '') {
        $sql .= " WHERE f.name LIKE :q1 OR f.designation LIKE :q2 OR s.name LIKE :q3";
        $like = '%' . $q . '%';
        $params = [':q1' => $like, ':q2' => $like, ':q3' => $like];
    }
    $sql .= ' GROUP BY f.id, f.name, f.designation ORDER BY f.name';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) $row['specialization'] = $row['specializations'] === null || $row['specializations'] === '' ? [] : explode(',', $row['specializations']);
    unset($row);
    return $rows;
}

try {
    $pdo = db();
    if ($method === 'GET') {
        $q = trim((string)($_GET['q'] ?? ''));
        $items = faculty_rows($pdo, $q);
        json_response(['success' => true, 'count' => count($items), 'items' => $items]);
    }
    if ($method === 'POST') {
        require_login();
        $input = read_json();
        $name = trim((string)($input['name'] ?? ''));
        $designation = trim((string)($input['designation'] ?? ''));
        $specializations = $input['specialization'] ?? [];
        if ($name === '' || $designation === '' || !is_array($specializations) || count($specializations) === 0) json_response(['success' => false, 'error' => 'Name, designation and at least one specialization are required.'], 400);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO faculty (name, designation, department_id) VALUES (?, ?, 1)');
        $stmt->execute([$name, $designation]);
        $id = (int)$pdo->lastInsertId();
        $insertSpec = $pdo->prepare('INSERT INTO specializations (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $link = $pdo->prepare('INSERT IGNORE INTO faculty_specializations (faculty_id, specialization_id) VALUES (?, ?)');
        foreach ($specializations as $spec) {
            $spec = trim((string)$spec);
            if ($spec === '') continue;
            $insertSpec->execute([$spec]);
            $specId = (int)$pdo->lastInsertId();
            $link->execute([$id, $specId]);
        }
        $pdo->commit();
        $created = array_values(array_filter(faculty_rows($pdo), fn($row) => (int)$row['id'] === $id))[0] ?? null;
        json_response(['success' => true, 'message' => 'Faculty member created.', 'item' => $created], 201);
    }
    if ($method === 'PUT') {
        require_login();
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) json_response(['success' => false, 'error' => 'A valid faculty ID is required in the id query parameter.'], 400);
        $input = read_json();
        $name = trim((string)($input['name'] ?? ''));
        $designation = trim((string)($input['designation'] ?? ''));
        $specializations = $input['specialization'] ?? [];
        if ($name === '' || $designation === '' || !is_array($specializations) || count($specializations) === 0) json_response(['success' => false, 'error' => 'Name, designation and at least one specialization are required.'], 400);
        $check = $pdo->prepare('SELECT id FROM faculty WHERE id = ?');
        $check->execute([$id]);
        if (!$check->fetch()) json_response(['success' => false, 'error' => 'Faculty member not found.'], 404);
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE faculty SET name = ?, designation = ? WHERE id = ?');
        $stmt->execute([$name, $designation, $id]);
        $pdo->prepare('DELETE FROM faculty_specializations WHERE faculty_id = ?')->execute([$id]);
        $insertSpec = $pdo->prepare('INSERT INTO specializations (name) VALUES (?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $link = $pdo->prepare('INSERT IGNORE INTO faculty_specializations (faculty_id, specialization_id) VALUES (?, ?)');
        foreach ($specializations as $spec) {
            $spec = trim((string)$spec);
            if ($spec === '') continue;
            $insertSpec->execute([$spec]);
            $link->execute([$id, (int)$pdo->lastInsertId()]);
        }
        $pdo->commit();
        $updated = array_values(array_filter(faculty_rows($pdo), fn($row) => (int)$row['id'] === (int)$id))[0] ?? null;
        json_response(['success' => true, 'message' => 'Faculty member updated.', 'item' => $updated]);
    }
    method_not_allowed(['GET', 'POST', 'PUT']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage());
    json_response(['success' => false, 'error' => 'A server error occurred while processing the request.'], 500);
}
