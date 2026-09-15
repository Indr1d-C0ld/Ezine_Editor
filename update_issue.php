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
$title = $input['title'] ?? '';
// Se il client non invia 'date' (l'editor non lo fa: manda solo 'data' in formato
// italiano), la data ISO già salvata va conservata, non riscritta a oggi.
$date = $input['date'] ?? null;
$data = $input['data'] ?? '';
$content = json_encode($input['content']);
$charCount = strlen($content);
$wordCount = str_word_count(strip_tags($content), 0, 'àèéìòù');
$sizeKb = round(strlen($content) / 1024, 2);
$stmt = $db->prepare("UPDATE issues SET title=:title, date=COALESCE(:date, date), data=:data, content=:content,
                      updated_at=CURRENT_TIMESTAMP, char_count=:char_count, word_count=:word_count, size_kb=:size_kb
                      WHERE id=:id");
$stmt->bindValue(':title', $title, SQLITE3_TEXT);
if ($date === null) {
    $stmt->bindValue(':date', null, SQLITE3_NULL);
} else {
    $stmt->bindValue(':date', $date, SQLITE3_TEXT);
}
$stmt->bindValue(':data', $data, SQLITE3_TEXT);
$stmt->bindValue(':content', $content, SQLITE3_TEXT);
$stmt->bindValue(':char_count', $charCount, SQLITE3_INTEGER);
$stmt->bindValue(':word_count', $wordCount, SQLITE3_INTEGER);
$stmt->bindValue(':size_kb', $sizeKb, SQLITE3_FLOAT);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => $db->lastErrorMsg()]);
    exit;
}
// execute() riesce anche quando non tocca alcuna riga: senza questo controllo
// un id inesistente riceverebbe comunque 'success'.
if ($db->changes() === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Uscita non trovata']);
    exit;
}
echo json_encode(['success' => true]);
?>
