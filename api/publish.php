<?php
// POST {issue_id, html}                 -> pubblica (o ripubblica) un'uscita
// POST {issue_id, action: "unpublish"}  -> la ritira dal sito pubblico
//
// L'HTML arriva dall'editor già autonomo (Ezine.exportDocument): stile, logo e
// immagini caricate sono incorporati. Qui viene solo conservato; meta per i
// motori di ricerca e politica di sicurezza si aggiungono quando si crea il
// pacchetto, così valgono sempre le impostazioni correnti.
require __DIR__ . '/lib.php';
ezine_metodo('POST');
$in = ezine_input_json();
$id = ezine_id($in['issue_id'] ?? null);
$db = ezine_db();

if (($in['action'] ?? '') === 'unpublish') {
    $st = $db->prepare('DELETE FROM publications WHERE issue_id = :id');
    $st->bindValue(':id', $id, SQLITE3_INTEGER);
    $st->execute();
    if ($db->changes() === 0) ezine_errore('L\'uscita non era pubblicata', 404);
    ezine_json(['success' => true]);
}

$html = (string) ($in['html'] ?? '');
if (strlen($html) > 18 * 1024 * 1024) ezine_errore('Pagina troppo grande (oltre 18 MB)', 413);
if (stripos($html, '<!DOCTYPE html') !== 0 || !str_contains($html, 'class="newspaper')) {
    ezine_errore('Contenuto non riconosciuto come pagina del giornale', 400);
}

$st = $db->prepare('SELECT title, data, updated_at, content FROM issues WHERE id = :id');
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$u = $st->execute()->fetchArray(SQLITE3_ASSOC);
if (!$u) ezine_errore('Uscita non trovata', 404);
$h = json_decode($u['content'], true)['header'] ?? [];
$anno = (string) ($h['anno'] ?? '');
$numero = (string) ($h['numero'] ?? '');

// Ripubblicando si conserva il nome del file: i link già diffusi restano validi.
$st = $db->prepare('SELECT slug FROM publications WHERE issue_id = :id');
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$slug = $st->execute()->fetchArray(SQLITE3_ASSOC)['slug'] ?? ezine_slug($db, $id, $anno, $numero);

$st = $db->prepare("INSERT INTO publications (issue_id, slug, title, anno, numero, data, html, published_at, source_updated_at)
                    VALUES (:id, :slug, :title, :anno, :numero, :data, :html, CURRENT_TIMESTAMP, :src)
                    ON CONFLICT(issue_id) DO UPDATE SET title = excluded.title, anno = excluded.anno,
                      numero = excluded.numero, data = excluded.data, html = excluded.html,
                      published_at = excluded.published_at, source_updated_at = excluded.source_updated_at");
$st->bindValue(':id', $id, SQLITE3_INTEGER);
$st->bindValue(':slug', $slug, SQLITE3_TEXT);
$st->bindValue(':title', $u['title'], SQLITE3_TEXT);
$st->bindValue(':anno', $anno, SQLITE3_TEXT);
$st->bindValue(':numero', $numero, SQLITE3_TEXT);
$st->bindValue(':data', $u['data'], SQLITE3_TEXT);
$st->bindValue(':html', $html, SQLITE3_TEXT);
$st->bindValue(':src', $u['updated_at'], SQLITE3_TEXT);
$st->execute();
ezine_json(['success' => true, 'slug' => $slug]);
