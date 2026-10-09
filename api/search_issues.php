<?php
// Ricerca full-text nel testo scritto dall'autore (non nell'intero JSON:
// cercando "text" o "article" corrisponderebbe a ogni uscita).
//
//   q=…               testo da cercare, anche a metà parola, in titolo, data e articoli
//   parola=1          solo parole intere e solo negli articoli: è il modo in cui
//                     la nuvola dell'archivio conta le parole più frequenti
//   ordina=occorrenze dall'uscita in cui il testo compare più volte; a parità,
//                     dalla più recente (altrimenti l'ordine è solo per data)
//
// Ogni risultato riporta in "occorrenze" quante volte il testo compare.
require __DIR__ . '/lib.php';
ezine_metodo('GET');
$q = trim($_GET['q'] ?? '');
if ($q === '') ezine_json([]);
$parola = ($_GET['parola'] ?? '') === '1';
$schema = '/' . ($parola ? '(?<!\p{L})' : '') . preg_quote($q, '/') . ($parola ? '(?!\p{L})' : '') . '/iu';

$r = ezine_db()->query('SELECT ' . ezine_colonne_elenco() . ', content FROM issues ORDER BY created_at DESC, id DESC');
$trovati = [];
while ($row = $r->fetchArray(SQLITE3_ASSOC)) {
    $articoli = ezine_testo_pulito(json_decode($row['content'], true));
    $testo = $parola ? $articoli : $row['title'] . ' ' . $row['data'] . ' ' . $articoli;
    $n = (int) preg_match_all($schema, $testo);
    if ($n > 0) {
        unset($row['content']);
        $row['occorrenze'] = $n;
        $trovati[] = ezine_riga_elenco($row);
    }
}
// usort è stabile: a parità di occorrenze resta l'ordine per data della query
if (($_GET['ordina'] ?? '') === 'occorrenze') usort($trovati, fn($a, $b) => $b['occorrenze'] <=> $a['occorrenze']);
ezine_json($trovati);
