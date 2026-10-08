<?php
// Impostazioni della testata (nome, motto, titoli delle sezioni, logo...).
// Vivono nel database e non nel codice: il codice contiene solo valori neutri.
require __DIR__ . '/lib.php';
ezine_metodo('GET', 'POST');
$db = ezine_db();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $v = $db->querySingle("SELECT value FROM settings WHERE key = 'masthead'");
    ezine_json(['masthead' => $v ? json_decode($v, true) : null]);
}
$in = ezine_input_json();
if (!isset($in['masthead']) || !is_array($in['masthead'])) ezine_errore('Impostazioni mancanti', 400);
$st = $db->prepare("INSERT INTO settings (key, value) VALUES ('masthead', :v)
                    ON CONFLICT(key) DO UPDATE SET value = excluded.value");
$st->bindValue(':v', json_encode($in['masthead'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), SQLITE3_TEXT);
$st->execute();
ezine_json(['success' => true]);
