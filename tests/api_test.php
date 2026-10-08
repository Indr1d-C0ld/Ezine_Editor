<?php
// Test del backend via HTTP, contro una copia temporanea dell'app.
// Non va lanciato a mano: lo avvia tests/run.sh, che prepara la copia con un
// database vuoto e un server PHP di prova, e poi la elimina.
//
// uso: php tests/api_test.php <url_base> <cartella_della_copia>

declare(strict_types=1);

$BASE = rtrim($argv[1] ?? '', '/');
$APP = rtrim($argv[2] ?? '', '/');
if (!$BASE || !is_dir($APP) || !is_file("$APP/api/lib.php")) {
    fwrite(STDERR, "uso: php tests/api_test.php <url_base> <cartella_della_copia>\n");
    exit(2);
}

// ---------- mini framework ----------

$passati = 0;
$falliti = [];

function sezione(string $t): void { echo "\n── $t\n"; }

function verifica(string $descrizione, bool $ok, string $dettaglio = ''): void {
    global $passati, $falliti;
    if ($ok) { $passati++; echo "  ✓ $descrizione\n"; return; }
    $falliti[] = $descrizione;
    echo "  ✗ $descrizione" . ($dettaglio !== '' ? "\n      → $dettaglio" : '') . "\n";
}

/** @return array{status:int, body:string, json:mixed} */
function http(string $metodo, string $percorso, ?string $corpo = null, array $intestazioni = []): array {
    global $BASE;
    $ctx = stream_context_create(['http' => [
        'method' => $metodo,
        'header' => implode("\r\n", $intestazioni),
        'content' => $corpo ?? '',
        'ignore_errors' => true,     // leggere anche le risposte 4xx/5xx
        'timeout' => 30,
    ]]);
    $body = @file_get_contents($BASE . '/' . ltrim($percorso, '/'), false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $status = (int) $m[1];
    }
    $body = $body === false ? '' : $body;
    return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
}

function get(string $p): array { return http('GET', $p); }

function post_json(string $p, $dati): array {
    return http('POST', $p, is_string($dati) ? $dati : json_encode($dati), ['Content-Type: application/json']);
}

function post_file(string $p, string $campo, string $nome, string $tipo, string $bytes, array $altri = []): array {
    $b = 'ezinetest' . bin2hex(random_bytes(8));
    $corpo = '';
    foreach ($altri as $k => $v) {
        $corpo .= "--$b\r\nContent-Disposition: form-data; name=\"$k\"\r\n\r\n$v\r\n";
    }
    $corpo .= "--$b\r\nContent-Disposition: form-data; name=\"$campo\"; filename=\"$nome\"\r\nContent-Type: $tipo\r\n\r\n$bytes\r\n--$b--\r\n";
    return http('POST', $p, $corpo, ["Content-Type: multipart/form-data; boundary=$b"]);
}

function db(): SQLite3 {
    global $APP;
    $d = new SQLite3("$APP/ezine.db");
    $d->busyTimeout(3000);
    return $d;
}

// ---------- dati di prova ----------

function contenuto(array $sovrascrivi = []): array {
    return array_replace_recursive([
        'header' => ['anno' => 'I', 'numero' => '1', 'data' => 'Lunedì 5 ottobre 2026', 'titleColor' => '#8b1f1f'],
        'fullWidth' => null,
        'colLeft' => [['type' => 'article', 'title' => 'Prova', 'text' => 'Città. Perché è così!', 'kicker' => '', 'byline' => '',
                       'image' => '', 'imageFloat' => 'none', 'imagePosition' => 'top', 'imageCaption' => '', 'imageStyle' => 'normal']],
        'colRight' => [], 'roundup' => [], 'letters' => [], 'fight' => [], 'pages' => [],
        'straightFromTheMan' => ['text' => ''], 'fakeAd' => ['text' => '', 'enabled' => false, 'colored' => false],
        'nextIssue' => '', 'fixedRubric' => '',
    ], $sovrascrivi);
}

function salva(string $titolo, array $c): int {
    $r = post_json('api/save_issue.php', ['title' => $titolo, 'data' => $c['header']['data'] ?? '', 'content' => $c]);
    if ($r['status'] !== 200) throw new RuntimeException("salvataggio fallito: {$r['body']}");
    return (int) $r['json']['id'];
}

function carica(int $id): array { return get("api/load_issue.php?id=$id")['json']; }

function aggiorna(int $id, array $c, ?string $atteso, bool $force = false): array {
    return post_json('api/update_issue.php', ['id' => $id, 'title' => 'T', 'data' => '', 'content' => $c,
                                               'expected_updated_at' => $atteso, 'force' => $force]);
}

// JPEG con un segmento EXIF che contiene marca della fotocamera e posizione GPS,
// costruito a mano: il test non dipende da strumenti esterni.
function jpeg_con_gps(): string {
    $img = imagecreatetruecolor(320, 200);
    imagefilledrectangle($img, 0, 0, 319, 199, imagecolorallocate($img, 200, 170, 120));
    imagefilledellipse($img, 160, 100, 180, 120, imagecolorallocate($img, 40, 60, 90));
    ob_start(); imagejpeg($img, null, 90); $jpg = ob_get_clean();

    $marca = "FotocameraTracciante\0";               // 21 byte
    $off_gps = 38; $off_marca = 68; $off_lat = 68 + 24;
    $tiff = "II*\0" . pack('V', 8)
          // IFD0: Make + puntatore alla sezione GPS
          . pack('v', 2)
          . pack('vvVV', 0x010F, 2, strlen($marca), $off_marca)
          . pack('vvVV', 0x8825, 4, 1, $off_gps)
          . pack('V', 0)
          // IFD GPS: GPSLatitudeRef = N, GPSLatitude = 45° 27' 50.4"
          . pack('v', 2)
          . pack('vvV', 0x0001, 2, 2) . "N\0\0\0"
          . pack('vvVV', 0x0002, 5, 3, $off_lat)
          . pack('V', 0)
          . str_pad($marca, 24, "\0")
          . pack('VVVVVV', 45, 1, 27, 1, 504, 10);
    $app1 = "Exif\0\0" . $tiff;
    return "\xFF\xD8\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpg, 2);
}

// PNG con un blocco di testo (nome dell'autore), come quelli scritti da molti programmi.
function png_con_testo(): string {
    $img = imagecreatetruecolor(120, 80);
    imagefilledrectangle($img, 0, 0, 119, 79, imagecolorallocate($img, 10, 120, 200));
    ob_start(); imagepng($img); $png = ob_get_clean();
    $dati = "Author\0AutoreSegretoDiProva";
    $chunk = pack('N', strlen($dati)) . 'tEXt' . $dati . pack('N', crc32('tEXt' . $dati));
    return substr($png, 0, 33) . $chunk . substr($png, 33);     // dopo IHDR
}

// PNG con rumore (non comprimibile sotto 1 KB) e JPEG grande con sfumature:
// immagini vere per verificare estrazione, deduplicazione e riduzione.
function png_rumore(int $w, int $h): string {
    $img = imagecreatetruecolor($w, $h);
    mt_srand(7);
    for ($y = 0; $y < $h; $y++) for ($x = 0; $x < $w; $x++) {
        $v = mt_rand(0, 1) ? 0 : 255;
        imagesetpixel($img, $x, $y, imagecolorallocate($img, $v, $v, $v));
    }
    ob_start(); imagepng($img, null, 9); return ob_get_clean();
}

function jpeg_grande(int $w, int $h): string {
    $img = imagecreatetruecolor($w, $h);
    for ($x = 0; $x < $w; $x += 4) {
        imagefilledrectangle($img, $x, 0, $x + 3, $h - 1, imagecolorallocate($img, (int) (255 * $x / $w), 90, 160));
    }
    for ($i = 0; $i < 60; $i++) imagefilledellipse($img, ($i * 397) % $w, ($i * 211) % $h, 140, 90, imagecolorallocate($img, 240, 220, 60));
    ob_start(); imagejpeg($img, null, 90); return ob_get_clean();
}

function blocchi_png(string $png): array {
    $out = []; $i = 8;
    while ($i + 8 <= strlen($png)) {
        $n = unpack('N', substr($png, $i, 4))[1];
        $out[] = substr($png, $i + 4, 4);
        $i += 12 + $n;
    }
    return $out;
}

// Date delle voci di uno zip così come sono scritte nel file (formato DOS, senza
// fuso orario): è ciò che vede chiunque lo apra. ZipArchive invece le converte
// nel fuso di chi legge, e non serve a verificare cosa contiene il file.
function date_nello_zip(string $zip): array {
    $eocd = strrpos($zip, "PK\x05\x06");
    if ($eocd === false) return [];
    $n = unpack('v', substr($zip, $eocd + 10, 2))[1];
    $pos = unpack('V', substr($zip, $eocd + 16, 4))[1];
    $out = [];
    for ($k = 0; $k < $n && substr($zip, $pos, 4) === "PK\x01\x02"; $k++) {
        $t = unpack('v', substr($zip, $pos + 12, 2))[1];
        $d = unpack('v', substr($zip, $pos + 14, 2))[1];
        $out[] = sprintf('%04d-%02d-%02d %02d:%02d', ($d >> 9) + 1980, ($d >> 5) & 15, $d & 31, $t >> 11, ($t >> 5) & 63);
        [$ln, $le, $lc] = array_values(unpack('v3', substr($zip, $pos + 28, 6)));
        $pos += 46 + $ln + $le + $lc;
    }
    return $out;
}

function zip_da(string $bytes): ZipArchive {
    $f = tempnam(sys_get_temp_dir(), 'eztz');
    file_put_contents($f, $bytes);
    $z = new ZipArchive();
    $z->open($f);
    register_shutdown_function(fn() => @unlink($f));
    return $z;
}

// ===================================================================
sezione('Schema e migrazioni');
$r = get('api/list_issues.php');
verifica('il primo accesso crea il database e risponde con un archivio vuoto', $r['status'] === 200 && $r['json'] === [], $r['body']);
$d = db();
verifica('lo schema è alla versione attuale', (int) $d->querySingle('PRAGMA user_version') === 3);
foreach (['issues', 'issue_revisions', 'settings', 'drafts', 'publications'] as $t) {
    verifica("esiste la tabella $t", (bool) $d->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='$t'"));
}

// ===================================================================
sezione('Validazione delle richieste');
verifica('metodo non ammesso → 405', get('api/save_issue.php')['status'] === 405);
verifica('id non numerico → 400', get('api/load_issue.php?id=abc')['status'] === 400);
verifica('id inesistente → 404', get('api/load_issue.php?id=999999')['status'] === 404);
verifica('JSON non valido → 400', post_json('api/save_issue.php', '{non json')['status'] === 400);
verifica('salvataggio senza contenuto → 400', post_json('api/save_issue.php', ['title' => 'x'])['status'] === 400);

// ===================================================================
sezione('Uscite e statistiche');
$id = salva('Prova', contenuto());
$u = carica($id);
verifica('salva e ricarica un\'uscita', ($u['content']['colLeft'][0]['text'] ?? '') === 'Città. Perché è così!');
verifica('conta le parole del testo, non del JSON (5)', (int) $u['word_count'] === 5, "word_count = {$u['word_count']}");
verifica('conta i caratteri del testo, non del JSON (27)', (int) $u['char_count'] === 27, "char_count = {$u['char_count']}");
$idMd = salva('Md', contenuto(['colLeft' => [['title' => '', 'text' => '**forte** <em>tag</em> [collegamento](https://esempio.it/a_b)']]]));
verifica('Markdown, HTML e indirizzi dei link non contano come parole (3)', (int) carica($idMd)['word_count'] === 3, 'word_count = ' . carica($idMd)['word_count']);
$elenco = get('api/list_issues.php')['json'];
verifica('l\'elenco non include il contenuto (campo più pesante)', isset($elenco[0]) && !array_key_exists('content', $elenco[0]));
verifica('la data ISO è assegnata dal server', preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $u['date']) === 1, (string) $u['date']);

// ===================================================================
sezione('Aggiornamenti, cronologia e conflitti');
$prima = carica($id)['updated_at'];
$c2 = contenuto(['colLeft' => [['text' => 'Seconda versione del testo']]]);
$r = aggiorna($id, $c2, $prima);
verifica('aggiorna con il timestamp corretto', $r['status'] === 200, $r['body']);
$dopo = $r['json']['updated_at'] ?? '';
verifica('updated_at cambia a ogni salvataggio', $dopo !== '' && $dopo !== $prima);
$revs = get("api/revisions.php?issue_id=$id")['json'];
verifica('la versione sostituita finisce nella cronologia', count($revs) === 1 && $revs[0]['reason'] === 'modifica');
$r = aggiorna($id, contenuto(), $prima);
verifica('timestamp vecchio → 409, nessuna sovrascrittura silenziosa', $r['status'] === 409 && ($r['json']['conflict'] ?? false) === true, $r['body']);
verifica('dopo il 409 il contenuto è quello dell\'altra finestra', (carica($id)['content']['colLeft'][0]['text'] ?? '') === 'Seconda versione del testo');
verifica('con force=true si sovrascrive consapevolmente', aggiorna($id, contenuto(), $prima, true)['status'] === 200);
verifica('aggiornare un\'uscita inesistente → 404', aggiorna(999999, contenuto(), null)['status'] === 404);
verifica('aggiornare con id non numerico → 400', post_json('api/update_issue.php', ['id' => 'x', 'content' => []])['status'] === 400);

$revs = get("api/revisions.php?issue_id=$id")['json'];
$piuVecchia = end($revs);
$rev = get("api/revisions.php?id={$piuVecchia['id']}")['json'];
verifica('una revisione si legge per intero', ($rev['content']['colLeft'][0]['text'] ?? '') === 'Città. Perché è così!');
$r = post_json('api/restore_revision.php', ['revision_id' => (int) $piuVecchia['id']]);
verifica('ripristina una revisione', $r['status'] === 200 && (carica($id)['content']['colLeft'][0]['text'] ?? '') === 'Città. Perché è così!');
$motivi = array_column(get("api/revisions.php?issue_id=$id")['json'], 'reason');
verifica('anche il ripristino è reversibile (salva prima lo stato attuale)', in_array('prima del ripristino', $motivi, true));

$idMolte = salva('Molte versioni', contenuto());
for ($i = 0; $i < 33; $i++) aggiorna($idMolte, contenuto(['colLeft' => [['text' => "versione $i"]]]), null, true);
verifica('la cronologia conserva al massimo 30 versioni per uscita', count(get("api/revisions.php?issue_id=$idMolte")['json']) === 30);

$r = post_json('api/delete_issue.php', ['id' => $idMolte]);
verifica('elimina un\'uscita', $r['status'] === 200);
verifica('eliminando un\'uscita si elimina anche la sua cronologia', (int) db()->querySingle("SELECT count(*) FROM issue_revisions WHERE issue_id = $idMolte") === 0);
verifica('eliminare due volte → 404', post_json('api/delete_issue.php', ['id' => $idMolte])['status'] === 404);
verifica('eliminare con id non numerico → 400', post_json('api/delete_issue.php', ['id' => 'abc'])['status'] === 400);

// ===================================================================
sezione('Ricerca e parole chiave');
$trovati = get('api/search_issues.php?q=' . rawurlencode('perché'))['json'];
verifica('trova una parola nel testo degli articoli (senza badare alle maiuscole)', in_array($id, array_column($trovati, 'id'), true));
verifica('non trova i nomi dei campi del JSON ("kicker")', get('api/search_issues.php?q=kicker')['json'] === []);
verifica('non trova i tag HTML ("strong")', get('api/search_issues.php?q=strong')['json'] === []);
verifica('i risultati non includono il contenuto', isset($trovati[0]) && !array_key_exists('content', $trovati[0]));
verifica('ricerca vuota → nessun risultato', get('api/search_issues.php?q=')['json'] === []);
$parole = array_column(get('api/keywords.php')['json'], 'word');
verifica('la nuvola contiene le parole scritte', in_array('perché', $parole, true) || in_array('città', $parole, true), implode(', ', $parole));
verifica('la nuvola non contiene nomi di campi', !array_intersect($parole, ['text', 'title', 'kicker', 'header', 'article', 'type', 'image']));

// ===================================================================
sezione('Bozza e impostazioni');
verifica('bozza assente all\'inizio', get('api/draft.php')['json']['payload'] === null);
$r = post_json('api/draft.php', ['payload' => ['content' => contenuto(), 'issue' => ['id' => $id], 'savedAt' => 'x']]);
verifica('salva la bozza sul server', $r['status'] === 200);
verifica('la bozza si rilegge identica', (get('api/draft.php')['json']['payload']['issue']['id'] ?? null) === $id);
verifica('bozza malformata → 400', post_json('api/draft.php', ['payload' => 'testo'])['status'] === 400);
verifica('impostazioni assenti all\'inizio', get('api/settings.php')['json']['masthead'] === null);
post_json('api/settings.php', ['masthead' => ['nameA' => 'PROVA', 'fullName' => 'Testata di prova']]);
verifica('salva e rilegge le impostazioni della testata', (get('api/settings.php')['json']['masthead']['fullName'] ?? '') === 'Testata di prova');

// ===================================================================
sezione('Immagini e metadati');
$jpg = jpeg_con_gps();
$tmpJpg = tempnam(sys_get_temp_dir(), 'ezjpg');
file_put_contents($tmpJpg, $jpg);
$exifPrima = @exif_read_data($tmpJpg, null, true) ?: [];
unlink($tmpJpg);
verifica('(controllo del test) il file di prova contiene davvero GPS e marca', isset($exifPrima['GPS'], $exifPrima['IFD0']['Make']));
$r = post_file('api/upload_image.php', 'image', 'foto.jpg', 'image/jpeg', $jpg);
$url = $r['json']['url'] ?? '';
verifica('carica un JPEG', $r['status'] === 200 && preg_match('#^uploads/[a-f0-9]{24}\.jpg$#', $url) === 1, $r['body']);
$salvato = (string) @file_get_contents("$APP/$url");
$exifDopo = @exif_read_data("$APP/$url", null, true) ?: [];
verifica('il file salvato non contiene la posizione GPS', !isset($exifDopo['GPS']));
verifica('il file salvato non contiene la marca della fotocamera', !isset($exifDopo['IFD0']) && !str_contains($salvato, 'FotocameraTracciante'));
verifica('il file salvato non contiene nemmeno il commento di GD', !str_contains($salvato, 'gd-jpeg') && !isset($exifDopo['COMMENT']));
verifica('il file salvato resta un\'immagine valida', (getimagesize("$APP/$url")[2] ?? 0) === IMAGETYPE_JPEG);
$imgUsata = $url;

$png = png_con_testo();
verifica('(controllo del test) il PNG di prova contiene un blocco di testo', in_array('tEXt', blocchi_png($png), true));
$r = post_file('api/upload_image.php', 'image', 'disegno.png', 'image/png', $png);
$urlPng = $r['json']['url'] ?? '';
verifica('carica un PNG', $r['status'] === 200 && str_ends_with($urlPng, '.png'), $r['body']);
$blocchi = blocchi_png((string) @file_get_contents("$APP/$urlPng"));
verifica('il PNG salvato non contiene blocchi di testo o metadati', !array_intersect($blocchi, ['tEXt', 'iTXt', 'zTXt', 'eXIf', 'tIME']), implode(',', $blocchi));

$r = post_file('api/upload_image.php', 'image', 'foto.jpg', 'image/jpeg', '<?php echo "eseguito"; ?>');
verifica('un file PHP travestito da immagine viene rifiutato (415)', $r['status'] === 415, $r['body']);
verifica('richiesta senza file → 400', post_json('api/upload_image.php', [])['status'] === 400);
$php = glob("$APP/uploads/*.php") ?: [];
verifica('nessun file non-immagine è finito in uploads/', $php === []);

// ===================================================================
sezione('Pulizia delle immagini');
$idImg = salva('Con immagine', contenuto(['colLeft' => [['text' => 'testo', 'image' => $imgUsata]]]));
foreach (glob("$APP/uploads/*.{png,jpg}", GLOB_BRACE) as $f) touch($f, time() - 7200);
$r = post_file('api/upload_image.php', 'image', 'recente.png', 'image/png', png_con_testo());
$recente = basename($r['json']['url'] ?? '');
$anteprima = get('api/cleanup_images.php')['json'];
verifica('individua l\'immagine non usata da nessuna uscita', in_array(basename($urlPng), $anteprima['orphans'], true));
verifica('non considera orfana l\'immagine usata', !in_array(basename($imgUsata), $anteprima['orphans'], true));
verifica('non tocca le immagini caricate nell\'ultima ora', !in_array($recente, $anteprima['orphans'], true));
verifica('eliminazione senza conferma → 400', post_json('api/cleanup_images.php', [])['status'] === 400);
$r = post_json('api/cleanup_images.php', ['confirm' => true]);
verifica('eliminazione confermata', $r['status'] === 200 && $r['json']['deleted'] >= 1);
verifica('l\'immagine usata esiste ancora', is_file("$APP/$imgUsata"));
verifica('l\'immagine orfana è stata eliminata', !is_file("$APP/$urlPng"));

// ===================================================================
sezione('Pubblicazione');
$pagina = '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Uscita</title></head><body style="margin:0">'
        . '<div class="newspaper"><img src="data:image/png;base64,AAAA" onerror="alert(1)"><p>Testo pubblicato</p></div></body></html>';
verifica('pacchetto senza uscite pubblicate → 404', get('api/site_package.php')['status'] === 404);
verifica('pubblicare HTML non riconosciuto → 400', post_json('api/publish.php', ['issue_id' => $id, 'html' => '<script>x</script>'])['status'] === 400);
verifica('pubblicare un\'uscita inesistente → 404', post_json('api/publish.php', ['issue_id' => 999999, 'html' => $pagina])['status'] === 404);
$r = post_json('api/publish.php', ['issue_id' => $id, 'html' => $pagina]);
verifica('pubblica un\'uscita con un nome di file leggibile', $r['status'] === 200 && ($r['json']['slug'] ?? '') === 'anno-i-numero-1', $r['body']);
$slug = $r['json']['slug'] ?? '';
$stato = fn(int $i) => array_values(array_filter(get('api/publications.php')['json']['items'], fn($p) => $p['issue_id'] === $i))[0] ?? null;
verifica('appena pubblicata risulta allineata', ($stato($id)['stale'] ?? null) === false);
aggiorna($id, contenuto(['colLeft' => [['text' => 'cambiata dopo la pubblicazione']]]), null, true);
verifica('modificata dopo la pubblicazione risulta "da aggiornare"', ($stato($id)['stale'] ?? null) === true);
$r = post_json('api/publish.php', ['issue_id' => $id, 'html' => $pagina]);
verifica('ripubblicando torna allineata', ($stato($id)['stale'] ?? null) === false);
verifica('ripubblicando il nome del file non cambia', ($r['json']['slug'] ?? '') === $slug);
$r = post_json('api/publish.php', ['issue_id' => $idImg, 'html' => $pagina]);
verifica('due uscite con lo stesso anno e numero hanno nomi di file distinti', ($r['json']['slug'] ?? '') === 'anno-i-numero-1-2', $r['body']);

verifica('indirizzo pubblico non valido → 400', post_json('api/publications.php', ['config' => ['publicUrl' => 'javascript:alert(1)']])['status'] === 400);
$r = post_json('api/publications.php', ['config' => ['publicUrl' => 'http://redazione.onion', 'noindex' => true]]);
verifica('l\'indirizzo pubblico viene normalizzato con la barra finale', ($r['json']['config']['publicUrl'] ?? '') === 'http://redazione.onion/');

$r = get('api/site_package.php');
verifica('scarica il pacchetto del sito', $r['status'] === 200 && str_starts_with($r['body'], "PK"));
$dateZip = date_nello_zip($r['body']);
verifica('i file nello zip hanno una data fissa, senza ora locale (1980-01-01 00:00)',
    $dateZip && array_unique($dateZip) === ['1980-01-01 00:00'], implode(', ', array_unique($dateZip)));
$z = zip_da($r['body']);
$nomi = [];
for ($i = 0; $i < $z->numFiles; $i++) $nomi[] = $z->getNameIndex($i);
foreach (['sito/index.html', 'sito/robots.txt', 'sito/LEGGIMI.txt', 'sito/feed.xml', "sito/$slug.html"] as $atteso) {
    verifica("il pacchetto contiene $atteso", in_array($atteso, $nomi, true));
}
$tutteBlindate = true; $dettaglio = '';
foreach ($nomi as $n) {
    if (!str_ends_with($n, '.html')) continue;
    $h = $z->getFromName($n);
    $ok = str_contains($h, "default-src 'none'; img-src 'self' data:;") && str_contains($h, 'name="referrer" content="no-referrer"')
       && str_contains($h, 'name="robots" content="noindex') && !preg_match('/onerror=|<script/i', $h);
    if (!$ok) { $tutteBlindate = false; $dettaglio .= "$n "; }
}
verifica('ogni pagina ha CSP (solo immagini del sito), no-referrer e noindex, e nessuno script', $tutteBlindate, $dettaglio);
verifica('le pagine delle uscite hanno il link di ritorno all\'indice', str_contains((string) $z->getFromName("sito/$slug.html"), 'href="index.html"'));
verifica('robots.txt esclude i motori di ricerca', trim((string) $z->getFromName('sito/robots.txt')) === "User-agent: *\nDisallow: /");
$feed = @simplexml_load_string((string) $z->getFromName('sito/feed.xml'));
verifica('il feed RSS è XML valido', $feed !== false);
$dateFeed = $feed ? array_map('strval', $feed->xpath('//item/pubDate')) : [];
verifica('le date del feed sono arrotondate al giorno', $dateFeed && !array_filter($dateFeed, fn($x) => !str_ends_with($x, '00:00:00 +0000')), implode(' | ', $dateFeed));

post_json('api/publications.php', ['config' => ['publicUrl' => '', 'noindex' => false]]);
$z = zip_da(get('api/site_package.php')['body']);
verifica('senza indirizzo pubblico il feed non viene generato', $z->locateName('sito/feed.xml') === false);
verifica('con indicizzazione consentita robots.txt lo permette', str_contains((string) $z->getFromName('sito/robots.txt'), 'Allow: /'));
verifica('con indicizzazione consentita le pagine non hanno noindex', !str_contains((string) $z->getFromName('sito/index.html'), 'noindex'));
post_json('api/publications.php', ['config' => ['publicUrl' => '', 'noindex' => true]]);

// --- pacchetto leggero: immagini estratte, deduplicate, ridotte ---
$pngRetinato = png_rumore(300, 200);
$jpgGrande = jpeg_grande(2400, 1600);
$logoPng = png_rumore(120, 60);
$pesante = '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Uscita</title></head><body style="margin:0">'
         . '<div class="newspaper"><img class="header-logo" src="data:image/png;base64,' . base64_encode($logoPng) . '" alt="">'
         . '<figure class="image-wrapper"><img class="article-img img-baked" src="data:image/png;base64,' . base64_encode($pngRetinato) . '" alt="a"></figure>'
         . '<figure class="image-wrapper"><img class="article-img" src="data:image/jpeg;base64,' . base64_encode($jpgGrande) . '" alt="b"></figure>'
         . '<img src="data:image/png;base64,AAAA" alt="minuscola"><p>Testo</p></div></body></html>';
post_json('api/publish.php', ['issue_id' => $id, 'html' => $pesante]);
post_json('api/publish.php', ['issue_id' => $idImg, 'html' => $pesante]);
$zipLeggero = get('api/site_package.php')['body'];
$z = zip_da($zipLeggero);
$img = [];
for ($i = 0; $i < $z->numFiles; $i++) {
    $n = $z->getNameIndex($i);
    if (str_starts_with($n, 'sito/img/')) $img[$n] = $z->getFromIndex($i);
}
$pag = (string) $z->getFromName("sito/$slug.html");
verifica('le immagini incorporate diventano file nella cartella img/', count($img) >= 3 && str_contains($pag, 'src="img/'), count($img) . ' file');
verifica('nella pagina non restano immagini incorporate pesanti', !preg_match('#src="data:image/(png|jpeg);base64,[A-Za-z0-9+/=]{2000,}#', $pag));
verifica('le immagini minuscole restano incorporate (un file in più costerebbe più di loro)', str_contains($pag, 'src="data:image/png;base64,AAAA"'));
verifica('la pagina pesa una frazione dell\'istantanea salvata', strlen($pag) < strlen($pesante) * 0.1, round(strlen($pag) / 1024) . ' KB contro ' . round(strlen($pesante) / 1024) . ' KB');
verifica('immagini uguali in uscite diverse diventano un file solo', count($img) === 3, implode(', ', array_keys($img)));
$foto = array_values(array_filter($img, fn($b, $n) => str_ends_with($n, '.jpg'), ARRAY_FILTER_USE_BOTH))[0] ?? '';
$dimFoto = $foto ? getimagesizefromstring($foto) : [0, 0];
verifica('le fotografie oltre i 1600 px vengono ridotte', max($dimFoto[0], $dimFoto[1]) === 1600, "{$dimFoto[0]}×{$dimFoto[1]}");
verifica('...e pesano meno dell\'originale', $foto !== '' && strlen($foto) < strlen($jpgGrande), round(strlen($foto) / 1024) . ' KB contro ' . round(strlen($jpgGrande) / 1024) . ' KB');
verifica('...senza metadati né commenti', $foto !== '' && !str_contains($foto, 'gd-jpeg'));
verifica('i PNG retinati restano identici, pixel per pixel', in_array($pngRetinato, $img, true));
verifica('le immagini degli articoli hanno dimensioni dichiarate e caricamento differito',
    preg_match('#<img class="article-img img-baked" src="img/[a-f0-9]{20}\.png" width="300" height="200" loading="lazy"#', $pag) === 1);
verifica('il logo si carica subito, senza caricamento differito', preg_match('#<img class="header-logo" src="img/[a-f0-9]{20}\.png" alt="">#', $pag) === 1);
$dateZip = date_nello_zip($zipLeggero);
verifica('anche le immagini nello zip hanno la data fissa', $dateZip && array_unique($dateZip) === ['1980-01-01 00:00']);

$r = post_json('api/publish.php', ['issue_id' => $idImg, 'action' => 'unpublish']);
verifica('ritira un\'uscita dal sito', $r['status'] === 200 && $stato($idImg) === null);
verifica('ritirare due volte → 404', post_json('api/publish.php', ['issue_id' => $idImg, 'action' => 'unpublish'])['status'] === 404);

// ===================================================================
sezione('Backup e ripristino');
$r = get('api/backup.php');
verifica('scarica il backup', $r['status'] === 200 && str_starts_with($r['body'], 'PK'));
$bk = $r['body'];
$z = zip_da($bk);
$arch = json_decode((string) $z->getFromName('archive.json'), true);
verifica('il backup contiene uscite, revisioni, impostazioni, bozza e pubblicazioni',
    ($arch['format'] ?? '') === 'ezine-backup' && $arch['issues'] && $arch['revisions'] && $arch['settings'] && $arch['draft'] && $arch['publications']);
verifica('il backup contiene le immagini caricate', $z->locateName('uploads/' . basename($imgUsata)) !== false);

$r = post_file('api/restore_backup.php', 'backup', 'b.zip', 'application/zip', $bk);
verifica('ripristinare lo stesso backup non crea doppioni', $r['status'] === 200 && $r['json']['issues_imported'] === 0 && $r['json']['issues_skipped'] > 0, $r['body']);

$nRevPrima = count(get("api/revisions.php?issue_id=$id")['json']);
post_json('api/delete_issue.php', ['id' => $id]);
verifica('eliminando un\'uscita pubblicata si elimina la pubblicazione', (int) db()->querySingle("SELECT count(*) FROM publications WHERE issue_id = $id") === 0);
$r = post_file('api/restore_backup.php', 'backup', 'b.zip', 'application/zip', $bk);
verifica('dopo un\'eliminazione il ripristino recupera l\'uscita', $r['status'] === 200 && $r['json']['issues_imported'] === 1, $r['body']);
verifica('...con la sua cronologia', ($r['json']['revisions_imported'] ?? 0) === $nRevPrima, "importate {$r['json']['revisions_imported']} su $nRevPrima");
verifica('...e la sua pubblicazione', ($r['json']['publications_imported'] ?? 0) === 1);

$f = tempnam(sys_get_temp_dir(), 'ezev');
$zz = new ZipArchive(); $zz->open($f, ZipArchive::OVERWRITE);
$zz->addFromString('archive.json', json_encode(['format' => 'ezine-backup', 'version' => 1, 'issues' => [], 'revisions' => [], 'settings' => []]));
$zz->addFromString('../../fuori_dalla_cartella.php', '<?php echo 1;');
$zz->addFromString('uploads/../evaso.php', '<?php echo 1;');
$zz->addFromString('uploads/shell.php', '<?php echo 1;');
$zz->addFromString('uploads/aaaaaaaaaaaaaaaaaaaaaaaa.png', '<?php echo "non sono un png";');
$zz->close();
$r = post_file('api/restore_backup.php', 'backup', 'ostile.zip', 'application/zip', (string) file_get_contents($f));
unlink($f);
verifica('backup ostile: l\'immagine falsa viene scartata', $r['status'] === 200 && ($r['json']['images_invalid'] ?? 0) === 1, $r['body']);
verifica('backup ostile: nessun file scritto fuori posto (zip-slip)',
    !is_file("$APP/../fuori_dalla_cartella.php") && !is_file("$APP/evaso.php") && !is_file("$APP/uploads/shell.php")
    && !is_file("$APP/uploads/aaaaaaaaaaaaaaaaaaaaaaaa.png"));
verifica('archivio senza archive.json → 400', post_file('api/restore_backup.php', 'backup', 'x.zip', 'application/zip', (function () {
    $f = tempnam(sys_get_temp_dir(), 'ezx'); $z = new ZipArchive(); $z->open($f, ZipArchive::OVERWRITE);
    $z->addFromString('altro.txt', 'x'); $z->close(); $b = (string) file_get_contents($f); unlink($f); return $b;
})())['status'] === 400);
verifica('file che non è uno zip → 400', post_file('api/restore_backup.php', 'backup', 'x.zip', 'application/zip', 'non sono uno zip')['status'] === 400);

// ===================================================================
$totale = $passati + count($falliti);
echo "\n" . str_repeat('─', 60) . "\n";
if ($falliti) {
    echo "BACKEND: $passati/$totale superati, " . count($falliti) . " falliti:\n";
    foreach ($falliti as $f) echo "  ✗ $f\n";
    exit(1);
}
echo "BACKEND: tutti i $totale test superati\n";
