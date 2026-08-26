<?php
header('Content-Type: application/json');

$db = new SQLite3(__DIR__ . '/ezine.db');

// Crea tabella se non esiste
$db->exec("CREATE TABLE IF NOT EXISTS issues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    date TEXT,
    data TEXT,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    char_count INTEGER,
    word_count INTEGER,
    size_kb REAL
)");

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$title = $input['title'] ?? 'Uscita senza titolo';
$date = $input['date'] ?? date('Y-m-d');
$data = $input['data'] ?? ''; // data italiana
$content = json_encode($input['content']); // tutto il newspaperData

// Calcola metadati: numero caratteri, parole, dimensione
$charCount = strlen($content);
$wordCount = str_word_count(strip_tags($content), 0, 'àèéìòù');
$sizeKb = round(strlen($content) / 1024, 2);

$stmt = $db->prepare("INSERT INTO issues (title, date, data, content, char_count, word_count, size_kb) 
                      VALUES (:title, :date, :data, :content, :char_count, :word_count, :size_kb)");
$stmt->bindValue(':title', $title, SQLITE3_TEXT);
$stmt->bindValue(':date', $date, SQLITE3_TEXT);
$stmt->bindValue(':data', $data, SQLITE3_TEXT);
$stmt->bindValue(':content', $content, SQLITE3_TEXT);
$stmt->bindValue(':char_count', $charCount, SQLITE3_INTEGER);
$stmt->bindValue(':word_count', $wordCount, SQLITE3_INTEGER);
$stmt->bindValue(':size_kb', $sizeKb, SQLITE3_FLOAT);
$result = $stmt->execute();

if ($result) {
    $id = $db->lastInsertRowID();
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => $db->lastErrorMsg()]);
}
?>
