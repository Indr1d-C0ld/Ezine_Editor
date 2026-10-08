<?php
// Bozza di lavoro sul server: prima viveva solo nel localStorage di un singolo
// browser, quindi cambiando computer (o svuotando la cache) andava persa.
// GET -> {payload, updated_at} | POST {payload} -> salva
require __DIR__ . '/lib.php';
ezine_metodo('GET', 'POST');
$db = ezine_db();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $row = $db->query('SELECT payload, updated_at FROM drafts WHERE id = 1')->fetchArray(SQLITE3_ASSOC);
    ezine_json($row ? ['payload' => json_decode($row['payload'], true), 'updated_at' => $row['updated_at']]
                    : ['payload' => null, 'updated_at' => null]);
}
$in = ezine_input_json();
if (!isset($in['payload']) || !is_array($in['payload'])) ezine_errore('Bozza mancante', 400);
$json = json_encode($in['payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (strlen($json) > 5 * 1024 * 1024) ezine_errore('Bozza troppo grande', 413);
$st = $db->prepare("INSERT INTO drafts (id, payload, updated_at) VALUES (1, :p, strftime('%Y-%m-%d %H:%M:%f', 'now'))
                    ON CONFLICT(id) DO UPDATE SET payload = excluded.payload, updated_at = excluded.updated_at");
$st->bindValue(':p', $json, SQLITE3_TEXT);
$st->execute();
ezine_json(['success' => true, 'updated_at' => $db->querySingle('SELECT updated_at FROM drafts WHERE id = 1')]);
