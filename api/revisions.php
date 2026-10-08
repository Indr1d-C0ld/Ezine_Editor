<?php
// GET ?issue_id=N  -> elenco delle revisioni di un'uscita (senza contenuto)
// GET ?id=N        -> una revisione completa
require __DIR__ . '/lib.php';
ezine_metodo('GET');
$db = ezine_db();
if (isset($_GET['id'])) {
    $st = $db->prepare('SELECT * FROM issue_revisions WHERE id = :id');
    $st->bindValue(':id', ezine_id($_GET['id']), SQLITE3_INTEGER);
    $row = $st->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$row) ezine_errore('Revisione non trovata', 404);
    $row['content'] = json_decode($row['content'], true);
    ezine_json($row);
}
$st = $db->prepare('SELECT id, issue_id, title, data, saved_at, reason, length(content) AS bytes
                    FROM issue_revisions WHERE issue_id = :id ORDER BY id DESC');
$st->bindValue(':id', ezine_id($_GET['issue_id'] ?? null), SQLITE3_INTEGER);
$r = $st->execute();
$out = [];
while ($row = $r->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
ezine_json($out);
