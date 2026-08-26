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
$title = $input['title'] ?? '';
$date = $input['date'] ?? date('Y-m-d');
$data = $input['data'] ?? '';
$content = json_encode($input['content']);
$charCount = strlen($content);
$wordCount = str_word_count(strip_tags($content), 0, 'àèéìòù');
$sizeKb = round(strlen($content) / 1024, 2);
$stmt = $db->prepare("UPDATE issues SET title=:title, date=:date, data=:data, content=:content, 
                      updated_at=CURRENT_TIMESTAMP, char_count=:char_count, word_count=:word_count, size_kb=:size_kb 
                      WHERE id=:id");
$stmt->bindValue(':title', $title, SQLITE3_TEXT);
$stmt->bindValue(':date', $date, SQLITE3_TEXT);
$stmt->bindValue(':data', $data, SQLITE3_TEXT);
$stmt->bindValue(':content', $content, SQLITE3_TEXT);
$stmt->bindValue(':char_count', $charCount, SQLITE3_INTEGER);
$stmt->bindValue(':word_count', $wordCount, SQLITE3_INTEGER);
$stmt->bindValue(':size_kb', $sizeKb, SQLITE3_FLOAT);
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$result = $stmt->execute();
if ($result) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $db->lastErrorMsg()]);
}
?>
