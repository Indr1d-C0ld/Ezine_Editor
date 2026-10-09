<?php
// Anteprima del sito pubblico, pagina per pagina, senza scaricare lo zip:
//   api/site_preview.php/index.html, …/anno-i-numero-1.html, …/img/….png
// Il nome del file sta dopo lo script (PATH_INFO), così i collegamenti
// relativi delle pagine (index.html, img/…) funzionano come sul sito vero.
//
// I file sono esattamente quelli dello zip: li costruisce site_package.php.
// Costruirli costa (le foto vengono ricampionate), quindi si tengono in una
// cartella temporanea finché non cambia qualcosa che li riguarda: uscite
// pubblicate, etichette, testata, impostazioni di pubblicazione, il codice.
// Come le altre pagine dell'app, l'anteprima sta dietro l'autenticazione.
require_once __DIR__ . '/lib.php';
ezine_metodo('GET');
$db = ezine_db();

function anteprima_risposta(int $codice, string $testo): never {
    http_response_code($codice);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="it"><meta charset="UTF-8"><title>Anteprima del sito</title>'
       . '<p style="font:16px/1.5 sans-serif;padding:2rem">' . htmlspecialchars($testo) . '</p>';
    exit;
}

$nome = ltrim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
if ($nome === '') { header('Location: ' . $_SERVER['SCRIPT_NAME'] . '/index.html'); exit; }
// solo nomi semplici (img/ è l'unica sottocartella): niente percorsi verso l'esterno
if (!preg_match('#^(img/)?[A-Za-z0-9][A-Za-z0-9._-]*$#', $nome)) anteprima_risposta(404, 'File non trovato.');

// ---------- impronta di tutto ciò da cui dipende il sito ----------
$impronta = [];
$r = $db->query('SELECT p.issue_id, p.slug, p.published_at, length(p.html), i.tags
                 FROM publications p LEFT JOIN issues i ON i.id = p.issue_id ORDER BY p.issue_id');
while ($row = $r->fetchArray(SQLITE3_NUM)) $impronta[] = $row;
if (!$impronta) anteprima_risposta(404, 'Nessuna uscita pubblicata: pubblicane almeno una dall\'archivio, poi riapri l\'anteprima.');
$r = $db->query("SELECT key, value FROM settings WHERE key IN ('masthead', 'publishing') ORDER BY key");
while ($row = $r->fetchArray(SQLITE3_NUM)) $impronta[] = $row;
$logo = json_decode($db->querySingle("SELECT value FROM settings WHERE key = 'masthead'") ?: '{}', true)['logo'] ?? '';
$impronta[] = $logo !== '' && is_file(__DIR__ . '/../' . $logo) ? filemtime(__DIR__ . '/../' . $logo) : 0;
$impronta[] = filemtime(__DIR__ . '/site_package.php');
$impronta[] = filemtime(__DIR__ . '/lib.php');
$chiave = substr(sha1(json_encode($impronta)), 0, 20);

// una cartella per installazione, leggibile solo dall'utente del server web
$base = sys_get_temp_dir() . '/ezine-anteprima-' . substr(sha1(realpath(__DIR__ . '/..')), 0, 10);
$dir = "$base/$chiave";

// site_package.php va incluso in una funzione: le sue variabili (molte, con
// nomi comuni come $nome o $base) restano lì e non toccano quelle di qui.
function anteprima_costruisci(): array {
    define('EZINE_ANTEPRIMA', true);
    require __DIR__ . '/site_package.php';
    return $file;
}

function anteprima_elimina(string $d): void {
    foreach (glob("$d/{,.}[!.]*", GLOB_BRACE) ?: [] as $f) is_dir($f) ? anteprima_elimina($f) : unlink($f);
    @rmdir($d);
}

if (!is_dir($dir)) {
    $file = anteprima_costruisci();
    if (!is_dir($base)) mkdir($base, 0700, true);
    $tmp = "$base/.nuova-" . bin2hex(random_bytes(6));
    mkdir("$tmp/img", 0700, true);
    foreach ($file as $f => $contenuto) file_put_contents("$tmp/$f", $contenuto);
    // via le anteprime superate e i resti di costruzioni interrotte
    foreach (glob("$base/{,.}[!.]*", GLOB_BRACE | GLOB_ONLYDIR) ?: [] as $vecchia) {
        $b = basename($vecchia);
        if ($b === $chiave || $vecchia === $tmp) continue;
        if ($b[0] !== '.' || filemtime($vecchia) < time() - 3600) anteprima_elimina($vecchia);
    }
    // due richieste insieme: vince la prima, l'altra butta la sua copia
    if (!@rename($tmp, $dir)) anteprima_elimina($tmp);
}

$percorso = "$dir/$nome";
if (!is_file($percorso)) anteprima_risposta(404, 'File non trovato nel sito pubblico.');
$tipi = ['html' => 'text/html; charset=utf-8', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif',
         'webp' => 'image/webp', 'xml' => 'text/xml; charset=utf-8', 'txt' => 'text/plain; charset=utf-8'];
$ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
if (!isset($tipi[$ext])) anteprima_risposta(404, 'File non trovato.');
header('Content-Type: ' . $tipi[$ext]);
// la stessa politica delle pagine pubblicate, anche come intestazione
header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'");
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('Content-Length: ' . filesize($percorso));
readfile($percorso);
