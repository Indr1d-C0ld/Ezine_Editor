<?php
header('Content-Type: application/json');
$db = new SQLite3(__DIR__ . '/ezine.db');
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
$id = $input['id'];
$stmt = $db->prepare("DELETE FROM issues WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $db->lastErrorMsg()]);
}
?>
