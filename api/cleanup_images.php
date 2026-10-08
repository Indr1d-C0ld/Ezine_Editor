<?php
// Immagini caricate che nessuna uscita, revisione, bozza o impostazione usa più.
// GET -> anteprima (cosa verrebbe eliminato) | POST {confirm:true} -> elimina
require __DIR__ . '/lib.php';
ezine_metodo('GET', 'POST');
$db = ezine_db();
$inUso = array_flip(ezine_immagini_in_uso($db));
$orfane = [];
$byte = 0;
foreach (glob(DIR_UPLOADS . '/*.{png,jpg}', GLOB_BRACE) ?: [] as $file) {
    $nome = basename($file);
    if (!preg_match('/^[a-f0-9]{24}\.(png|jpg)$/', $nome) || isset($inUso[$nome])) continue;
    // Margine di un'ora: un'immagine appena caricata potrebbe non essere ancora
    // finita nella bozza salvata sul server.
    if (filemtime($file) > time() - 3600) continue;
    $orfane[] = $nome;
    $byte += filesize($file);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ezine_json(['orphans' => $orfane, 'count' => count($orfane), 'bytes' => $byte, 'in_use' => count($inUso)]);
}
$in = ezine_input_json();
if (empty($in['confirm'])) ezine_errore('Conferma mancante', 400);
$eliminate = 0;
foreach ($orfane as $nome) if (unlink(DIR_UPLOADS . '/' . $nome)) $eliminate++;
ezine_json(['success' => true, 'deleted' => $eliminate, 'bytes' => $byte]);
