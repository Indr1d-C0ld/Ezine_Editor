<?php
// Ricerca full-text nel testo scritto dall'autore (non nell'intero JSON:
// cercando "text" o "article" corrisponderebbe a ogni uscita).
require __DIR__ . '/lib.php';
ezine_metodo('GET');
$q = trim($_GET['q'] ?? '');
if ($q === '') ezine_json([]);

$r = ezine_db()->query('SELECT ' . ezine_colonne_elenco() . ', content FROM issues ORDER BY created_at DESC, id DESC');
$trovati = [];
while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    $testo = $row['title'] . ' ' . $row['data'] . ' ' . ezine_testo_pulito(json_decode($row['content'], true));
    if (mb_stripos($testo, $q) !== false) {
        unset($row['content']);
        $trovati[] = $row;
    }
}
ezine_json($trovati);
