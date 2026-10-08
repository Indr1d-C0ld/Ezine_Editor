<?php
// Aggiorna un'uscita esistente. Prima di sovrascrivere salva la versione
// precedente in issue_revisions, così ogni modifica è reversibile.
//
// Concorrenza ottimistica: il client invia expected_updated_at (quello letto
// all'apertura). Se nel frattempo l'uscita è stata salvata altrove risponde 409
// invece di sovrascrivere in silenzio; con force=true sovrascrive comunque.
require __DIR__ . '/lib.php';
ezine_metodo('POST');
$in = ezine_input_json();
$id = ezine_id($in['id'] ?? null);
if (!isset($in['content']) || !is_array($in['content'])) ezine_errore('Contenuto mancante', 400);

$db = ezine_db();
$db->exec('BEGIN IMMEDIATE');
$st = $db->prepare('SELECT updated_at FROM issues WHERE id = :id');
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$attuale = $st->execute()->fetchArray(SQLITE3_ASSOC);
if (!$attuale) { $db->exec('ROLLBACK'); ezine_errore('Uscita non trovata', 404); }

$atteso = $in['expected_updated_at'] ?? null;
if (empty($in['force']) && $atteso !== null && $atteso !== $attuale['updated_at']) {
    $db->exec('ROLLBACK');
    ezine_json(['error' => 'L\'uscita è stata modificata altrove dopo che l\'hai aperta',
                'conflict' => true, 'updated_at' => $attuale['updated_at']], 409);
}

ezine_salva_revisione($db, $id, 'modifica');

$json = json_encode($in['content'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$s = ezine_statistiche($in['content']);
// strftime con frazioni di secondo: due salvataggi nello stesso secondo devono
// comunque produrre updated_at diversi, altrimenti il controllo di conflitto cede.
$st = $db->prepare("UPDATE issues SET title = :title, data = :data, content = :content,
                      updated_at = strftime('%Y-%m-%d %H:%M:%f', 'now'),
                      char_count = :c, word_count = :w, size_kb = :kb
                    WHERE id = :id");
$st->bindValue(':title', trim((string) ($in['title'] ?? '')) ?: 'Uscita senza titolo', SQLITE3_TEXT);
$st->bindValue(':data', (string) ($in['data'] ?? ''), SQLITE3_TEXT);
$st->bindValue(':content', $json, SQLITE3_TEXT);
$st->bindValue(':c', $s['chars'], SQLITE3_INTEGER);
$st->bindValue(':w', $s['words'], SQLITE3_INTEGER);
$st->bindValue(':kb', round(strlen($json) / 1024, 2), SQLITE3_FLOAT);
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$st->execute();
$db->exec('COMMIT');

$updated = $db->querySingle("SELECT updated_at FROM issues WHERE id = $id");
ezine_json(['success' => true, 'updated_at' => $updated]);
