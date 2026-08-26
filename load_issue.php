<?php
header('Content-Type: application/json');
$db = new SQLite3(__DIR__ . '/ezine.db');
$id = $_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing id']);
    exit;
}
$stmt = $db->prepare("SELECT * FROM issues WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);
if ($row) {
    $row['content'] = json_decode($row['content'], true);
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
?>
