<?php
// Backup completo in un unico .zip: uscite, revisioni, impostazioni, bozza e
// tutte le immagini caricate. Ripristinabile con restore_backup.php.
require __DIR__ . '/lib.php';
ezine_metodo('GET');
$db = ezine_db();

$righe = function (string $q) use ($db): array {
    $r = $db->query($q);
    $out = [];
    while ($row = $r->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
    return $out;
};

$archivio = [
    'format' => 'ezine-backup',
    'version' => 1,
    'schema' => EZINE_SCHEMA_VERSION,
    'created_at' => gmdate('c'),
    'issues' => $righe('SELECT * FROM issues ORDER BY id'),
    'revisions' => $righe('SELECT * FROM issue_revisions ORDER BY id'),
    'settings' => $righe('SELECT * FROM settings'),
    'draft' => $righe('SELECT * FROM drafts'),
    'publications' => $righe('SELECT * FROM publications ORDER BY issue_id'),
];

$tmp = tempnam(sys_get_temp_dir(), 'ezine');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) ezine_errore('Impossibile creare il backup', 500);
$zip->addFromString('archive.json', json_encode($archivio, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
foreach (glob(DIR_UPLOADS . '/*.{png,jpg}', GLOB_BRACE) ?: [] as $f) {
    if (preg_match('/^[a-f0-9]{24}\.(png|jpg)$/', basename($f))) $zip->addFile($f, 'uploads/' . basename($f));
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="ezine-backup-' . date('Y-m-d-His') . '.zip"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
unlink($tmp);
