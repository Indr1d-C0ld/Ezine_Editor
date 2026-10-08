<?php
// Ripristina una revisione. Lo stato attuale viene prima salvato a sua volta
// come revisione: anche il ripristino è reversibile.
require __DIR__ . '/lib.php';
ezine_metodo('POST');
$in = ezine_input_json();
$revId = ezine_id($in['revision_id'] ?? null);
$db = ezine_db();
$db->exec('BEGIN IMMEDIATE');
$st = $db->prepare('SELECT * FROM issue_revisions WHERE id = :id');
$st->bindValue(':id', $revId, SQLITE3_INTEGER);
$rev = $st->execute()->fetchArray(SQLITE3_ASSOC);
if (!$rev) { $db->exec('ROLLBACK'); ezine_errore('Revisione non trovata', 404); }

ezine_salva_revisione($db, (int) $rev['issue_id'], 'prima del ripristino');
$contenuto = json_decode($rev['content'], true);
$s = ezine_statistiche($contenuto);
$st = $db->prepare("UPDATE issues SET title = :title, data = :data, content = :content,
                      updated_at = strftime('%Y-%m-%d %H:%M:%f', 'now'),
                      char_count = :c, word_count = :w, size_kb = :kb WHERE id = :id");
$st->bindValue(':title', $rev['title'], SQLITE3_TEXT);
$st->bindValue(':data', $rev['data'], SQLITE3_TEXT);
$st->bindValue(':content', $rev['content'], SQLITE3_TEXT);
$st->bindValue(':c', $s['chars'], SQLITE3_INTEGER);
$st->bindValue(':w', $s['words'], SQLITE3_INTEGER);
$st->bindValue(':kb', round(strlen($rev['content']) / 1024, 2), SQLITE3_FLOAT);
$st->bindValue(':id', $rev['issue_id'], SQLITE3_INTEGER);
$st->execute();
if ($db->changes() === 0) { $db->exec('ROLLBACK'); ezine_errore('Uscita non trovata', 404); }
$db->exec('COMMIT');
ezine_json(['success' => true, 'issue_id' => (int) $rev['issue_id']]);
