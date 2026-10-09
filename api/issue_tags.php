<?php
// Etichette di un'uscita: { id, tags: [...] } oppure tags come testo separato
// da virgole. Restituisce le etichette ripulite. Non tocca la data dell'ultima
// modifica: le etichette servono all'archivio e all'indice del sito, non sono
// una nuova versione dell'uscita.
require __DIR__ . '/lib.php';
ezine_metodo('POST');
$in = ezine_input_json();
$id = ezine_id($in['id'] ?? null);
$tags = ezine_etichette($in['tags'] ?? []);

$db = ezine_db();
$st = $db->prepare('UPDATE issues SET tags = :t WHERE id = :id');
$st->bindValue(':t', json_encode($tags, JSON_UNESCAPED_UNICODE), SQLITE3_TEXT);
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$st->execute();
if ($db->changes() === 0) ezine_errore('Uscita non trovata', 404);
ezine_json(['success' => true, 'tags' => $tags]);
