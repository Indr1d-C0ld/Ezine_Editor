<?php
header('Content-Type: application/json');
$db = new SQLite3(__DIR__ . '/ezine.db');
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}
$id = filter_var($input['id'], FILTER_VALIDATE_INT);
if ($id === false || $id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid id']);
    exit;
}
$stmt = $db->prepare("DELETE FROM issues WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => $db->lastErrorMsg()]);
    exit;
}
// execute() riesce anche quando non cancella nulla: senza questo controllo
// un id inesistente riceverebbe comunque 'success'.
if ($db->changes() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Uscita non trovata']);
    exit;
}
echo json_encode(['success' => true]);
?>
