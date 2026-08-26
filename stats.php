<?php
header('Content-Type: application/json');
$db = new SQLite3(__DIR__ . '/ezine.db');
$result = $db->query("SELECT id, title, data, created_at, char_count, word_count, size_kb FROM issues ORDER BY created_at DESC");
$issues = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $issues[] = $row;
}
echo json_encode($issues);
?>
