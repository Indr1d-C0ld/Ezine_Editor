<?php
require __DIR__ . '/lib.php';
ezine_metodo('GET');
$id = ezine_id($_GET['id'] ?? null);
$st = ezine_db()->prepare('SELECT * FROM issues WHERE id = :id');
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$row = $st->execute()->fetchArray(SQLITE3_ASSOC);
if (!$row) ezine_errore('Uscita non trovata', 404);
$row['content'] = json_decode($row['content'], true);
ezine_json($row);
