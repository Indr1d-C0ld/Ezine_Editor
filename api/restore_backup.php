<?php
// Ripristino da backup .zip. Non distruttivo: aggiunge e non cancella mai.
//  - le uscite già presenti (stesso titolo e stesso contenuto) vengono saltate,
//    quindi ripristinare due volte lo stesso backup non crea doppioni;
//  - le immagini vengono validate e ricodificate come un normale caricamento;
//  - le impostazioni vengono sovrascritte solo se richiesto esplicitamente.
// Il file arriva dall'esterno: va trattato come input ostile (zip-slip, zip bomb).
require __DIR__ . '/lib.php';
ezine_metodo('POST');
$f = $_FILES['backup'] ?? null;
if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) ezine_errore('Nessun backup ricevuto', 400);

$zip = new ZipArchive();
if ($zip->open($f['tmp_name'], ZipArchive::RDONLY) !== true) ezine_errore('Il file non è un archivio zip valido', 400);
if ($zip->numFiles > 20000) ezine_errore('Archivio con troppi file', 400);

$totale = 0;
$immagini = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $st = $zip->statIndex($i);
    $totale += $st['size'];
    // Solo nomi esatti: niente percorsi relativi (../) né file arbitrari.
    if (preg_match('#^uploads/([a-f0-9]{24}\.(?:png|jpg))$#', $st['name'], $m)) $immagini[$m[1]] = $i;
}
if ($totale > 1024 * 1024 * 1024) ezine_errore('Archivio troppo grande una volta decompresso', 400);

$json = $zip->getFromName('archive.json');
$dati = $json !== false ? json_decode($json, true) : null;
if (!is_array($dati) || ($dati['format'] ?? '') !== 'ezine-backup') ezine_errore('archive.json mancante o non riconosciuto', 400);

$esito = ['issues_imported' => 0, 'issues_skipped' => 0, 'revisions_imported' => 0,
          'images_imported' => 0, 'images_skipped' => 0, 'images_invalid' => 0, 'settings_restored' => false];

foreach ($immagini as $nome => $indice) {
    if (is_file(DIR_UPLOADS . '/' . $nome)) { $esito['images_skipped']++; continue; }
    try {
        ezine_salva_immagine($zip->getFromIndex($indice), $nome);
        $esito['images_imported']++;
    } catch (EzineErrore $e) {
        $esito['images_invalid']++;
    }
}

$db = ezine_db();
$db->exec('BEGIN IMMEDIATE');
$esiste = $db->prepare('SELECT 1 FROM issues WHERE title IS :t AND content IS :c LIMIT 1');
$ins = $db->prepare('INSERT INTO issues (title, date, data, content, created_at, updated_at, char_count, word_count, size_kb)
                     VALUES (:title, :date, :data, :content, :created, :updated, :c, :w, :kb)');
$mappaId = [];
foreach ($dati['issues'] ?? [] as $u) {
    if (!is_array($u) || !isset($u['content'])) continue;
    $esiste->bindValue(':t', $u['title'] ?? null, SQLITE3_TEXT);
    $esiste->bindValue(':c', $u['content'], SQLITE3_TEXT);
    $trovata = $esiste->execute()->fetchArray();
    $esiste->reset();
    if ($trovata) { $esito['issues_skipped']++; continue; }
    $s = ezine_statistiche(json_decode($u['content'], true));
    $ins->bindValue(':title', $u['title'] ?? 'Uscita senza titolo', SQLITE3_TEXT);
    $ins->bindValue(':date', $u['date'] ?? null, SQLITE3_TEXT);
    $ins->bindValue(':data', $u['data'] ?? '', SQLITE3_TEXT);
    $ins->bindValue(':content', $u['content'], SQLITE3_TEXT);
    $ins->bindValue(':created', $u['created_at'] ?? gmdate('Y-m-d H:i:s'), SQLITE3_TEXT);
    $ins->bindValue(':updated', $u['updated_at'] ?? gmdate('Y-m-d H:i:s'), SQLITE3_TEXT);
    $ins->bindValue(':c', $s['chars'], SQLITE3_INTEGER);
    $ins->bindValue(':w', $s['words'], SQLITE3_INTEGER);
    $ins->bindValue(':kb', round(strlen($u['content']) / 1024, 2), SQLITE3_FLOAT);
    $ins->execute();
    $ins->reset();
    if (isset($u['id'])) $mappaId[$u['id']] = $db->lastInsertRowID();
    $esito['issues_imported']++;
}

// Le revisioni seguono solo le uscite importate ora, con gli id rimappati.
$insRev = $db->prepare('INSERT INTO issue_revisions (issue_id, title, data, content, saved_at, reason)
                        VALUES (:id, :title, :data, :content, :saved, :reason)');
foreach ($dati['revisions'] ?? [] as $r) {
    if (!is_array($r) || !isset($mappaId[$r['issue_id'] ?? null])) continue;
    $insRev->bindValue(':id', $mappaId[$r['issue_id']], SQLITE3_INTEGER);
    $insRev->bindValue(':title', $r['title'] ?? null, SQLITE3_TEXT);
    $insRev->bindValue(':data', $r['data'] ?? null, SQLITE3_TEXT);
    $insRev->bindValue(':content', $r['content'] ?? null, SQLITE3_TEXT);
    $insRev->bindValue(':saved', $r['saved_at'] ?? null, SQLITE3_TEXT);
    $insRev->bindValue(':reason', $r['reason'] ?? 'ripristino', SQLITE3_TEXT);
    $insRev->execute();
    $insRev->reset();
    $esito['revisions_imported']++;
}

$vuote = !$db->querySingle("SELECT 1 FROM settings WHERE key = 'masthead'");
if (!empty($_POST['restore_settings']) || $vuote) {
    $set = $db->prepare('INSERT INTO settings (key, value) VALUES (:k, :v) ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    foreach ($dati['settings'] ?? [] as $s) {
        if (!is_array($s) || !isset($s['key'], $s['value'])) continue;
        $set->bindValue(':k', $s['key'], SQLITE3_TEXT);
        $set->bindValue(':v', $s['value'], SQLITE3_TEXT);
        $set->execute();
        $set->reset();
        $esito['settings_restored'] = true;
    }
}
$db->exec('COMMIT');
ezine_json(['success' => true] + $esito);
