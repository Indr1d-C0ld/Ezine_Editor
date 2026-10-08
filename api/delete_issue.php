<?php
// Le revisioni dell'uscita vengono eliminate a cascata (FOREIGN KEY ... ON DELETE CASCADE).
require __DIR__ . '/lib.php';
ezine_metodo('POST');
$in = ezine_input_json();
$id = ezine_id($in['id'] ?? null);
$db = ezine_db();
$st = $db->prepare('DELETE FROM issues WHERE id = :id');
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$st->execute();
if ($db->changes() === 0) ezine_errore('Uscita non trovata', 404);
ezine_json(['success' => true]);
