<?php
// Elenco delle uscite, senza il contenuto (è il campo più pesante).
require __DIR__ . '/lib.php';
ezine_metodo('GET');
$r = ezine_db()->query('SELECT ' . ezine_colonne_elenco() . ' FROM issues ORDER BY created_at DESC, id DESC');
$out = [];
while ($row = $r->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
ezine_json($out);
