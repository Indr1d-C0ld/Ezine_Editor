<?php
// Crea una nuova uscita.
require __DIR__ . '/lib.php';
ezine_metodo('POST');
$in = ezine_input_json();
if (!isset($in['content']) || !is_array($in['content'])) ezine_errore('Contenuto mancante', 400);

$json = json_encode($in['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$s = ezine_statistiche($in['content']);
$db = ezine_db();
$st = $db->prepare('INSERT INTO issues (title, date, data, content, char_count, word_count, size_kb)
                    VALUES (:title, :date, :data, :content, :c, :w, :kb)');
$st->bindValue(':title', trim((string) ($in['title'] ?? '')) ?: 'Uscita senza titolo', SQLITE3_TEXT);
$st->bindValue(':date', date('Y-m-d'), SQLITE3_TEXT);
$st->bindValue(':data', (string) ($in['data'] ?? ''), SQLITE3_TEXT);
$st->bindValue(':content', $json, SQLITE3_TEXT);
$st->bindValue(':c', $s['chars'], SQLITE3_INTEGER);
$st->bindValue(':w', $s['words'], SQLITE3_INTEGER);
$st->bindValue(':kb', round(strlen($json) / 1024, 2), SQLITE3_FLOAT);
if (!$st->execute()) ezine_errore($db->lastErrorMsg(), 500);

$id = $db->lastInsertRowID();
$updated = $db->querySingle("SELECT updated_at FROM issues WHERE id = $id");
ezine_json(['success' => true, 'id' => $id, 'updated_at' => $updated]);
