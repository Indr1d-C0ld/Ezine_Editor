<?php
// lib.php – funzioni condivise dagli endpoint in api/.
// Non va mai richiesto direttamente: è bloccato da api/.htaccess.

const EZINE_SCHEMA_VERSION = 4;

// Campi che contengono testo scritto davvero dall'autore. Tutto il resto del
// contenuto (nomi dei campi, valori di servizio come "normal"/"none", percorsi
// delle immagini) va ignorato da ricerca, statistiche e nuvola di parole.
const CAMPI_TESTUALI = ['text', 'title', 'kicker', 'byline', 'imageCaption', 'nextIssue', 'fixedRubric'];

const DIR_UPLOADS = __DIR__ . '/../uploads';

function ezine_db(): SQLite3 {
    static $db = null;
    if ($db) return $db;
    $db = new SQLite3(__DIR__ . '/../ezine.db');
    $db->busyTimeout(3000);
    $db->exec('PRAGMA foreign_keys = ON');
    ezine_migra($db);
    return $db;
}

// Le migrazioni girano alla prima richiesta dopo un aggiornamento del codice:
// non serve più aprire setup_db.php a mano.
function ezine_migra(SQLite3 $db): void {
    $versione = (int) $db->querySingle('PRAGMA user_version');
    if ($versione >= EZINE_SCHEMA_VERSION) return;

    $db->exec('BEGIN IMMEDIATE');
    // ricontrolla dentro la transazione: un'altra richiesta può averla appena fatta
    $versione = (int) $db->querySingle('PRAGMA user_version');

    if ($versione < 1) {
        $db->exec("CREATE TABLE IF NOT EXISTS issues (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT, date TEXT, data TEXT, content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            char_count INTEGER, word_count INTEGER, size_kb REAL
        )");
    }
    if ($versione < 2) {
        $db->exec("CREATE TABLE IF NOT EXISTS issue_revisions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            issue_id INTEGER NOT NULL REFERENCES issues(id) ON DELETE CASCADE,
            title TEXT, data TEXT, content TEXT,
            saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            reason TEXT
        )");
        $db->exec('CREATE INDEX IF NOT EXISTS idx_revisions_issue ON issue_revisions(issue_id, id)');
        $db->exec('CREATE TABLE IF NOT EXISTS settings (key TEXT PRIMARY KEY, value TEXT)');
        $db->exec("CREATE TABLE IF NOT EXISTS drafts (
            id INTEGER PRIMARY KEY CHECK (id = 1),
            payload TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        // Le statistiche salvate finora contavano l'intero JSON: le ricalcola.
        $righe = $db->query('SELECT id, content FROM issues');
        $agg = $db->prepare('UPDATE issues SET char_count = :c, word_count = :w WHERE id = :id');
        while ($r = $righe->fetchArray(SQLITE3_ASSOC)) {
            $s = ezine_statistiche(json_decode($r['content'] ?? 'null', true));
            $agg->bindValue(':c', $s['chars'], SQLITE3_INTEGER);
            $agg->bindValue(':w', $s['words'], SQLITE3_INTEGER);
            $agg->bindValue(':id', $r['id'], SQLITE3_INTEGER);
            $agg->execute();
            $agg->reset();
        }
    }
    if ($versione < 3) {
        // Istantanee pubblicate: HTML autonomo di ciascuna uscita, conservato qui
        // (dietro login) e servito solo dentro il pacchetto del sito pubblico.
        $db->exec("CREATE TABLE IF NOT EXISTS publications (
            issue_id INTEGER PRIMARY KEY REFERENCES issues(id) ON DELETE CASCADE,
            slug TEXT NOT NULL UNIQUE,
            title TEXT, anno TEXT, numero TEXT, data TEXT,
            html TEXT NOT NULL,
            published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            source_updated_at TEXT
        )");
    }
    if ($versione < 4) {
        // Etichette (rubriche, temi) di ogni uscita, come array JSON. Sono dati
        // d'archivio: modificarle non cambia la data dell'ultima modifica né
        // rende "da aggiornare" un'uscita già pubblicata.
        $db->exec("ALTER TABLE issues ADD COLUMN tags TEXT NOT NULL DEFAULT '[]'");
    }
    $db->exec('PRAGMA user_version = ' . EZINE_SCHEMA_VERSION);
    $db->exec('COMMIT');
}

// ---------- risposte ----------

function ezine_json($dati, int $codice = 200): never {
    http_response_code($codice);
    header('Content-Type: application/json');
    echo json_encode($dati, JSON_UNESCAPED_UNICODE);
    exit;
}

function ezine_errore(string $messaggio, int $codice): never {
    ezine_json(['error' => $messaggio], $codice);
}

function ezine_metodo(string ...$ammessi): void {
    if (!in_array($_SERVER['REQUEST_METHOD'], $ammessi, true)) {
        header('Allow: ' . implode(', ', $ammessi));
        ezine_errore('Metodo non ammesso', 405);
    }
}

function ezine_input_json(): array {
    $dati = json_decode(file_get_contents('php://input'), true);
    if (!is_array($dati)) ezine_errore('JSON non valido', 400);
    return $dati;
}

function ezine_id($valore): int {
    $id = filter_var($valore, FILTER_VALIDATE_INT);
    if ($id === false || $id === null || $id <= 0) ezine_errore('Id non valido', 400);
    return $id;
}

// ---------- testo redazionale ----------

function ezine_testo_redazionale($nodo, $chiave = null): string {
    if (is_string($nodo)) {
        return in_array($chiave, CAMPI_TESTUALI, true) ? $nodo . ' ' : '';
    }
    if (is_array($nodo)) {
        $out = '';
        foreach ($nodo as $k => $v) {
            // Negli elenchi le chiavi sono numeriche: si conserva quella del
            // contenitore (es. 'roundup'), altrimenti si usa la chiave propria.
            $out .= ezine_testo_redazionale($v, is_int($k) ? $chiave : $k);
        }
        return $out;
    }
    return '';
}

// Come il filtro di assets/render.js: il contenuto dei tag che le pagine non
// mostrano (script, style...) e i commenti HTML non contano come testo, né per
// la ricerca né per la nuvola né per le statistiche.
const EZINE_TAG_NASCOSTI = 'script|style|iframe|frame|object|embed|noscript|template|textarea|select|svg|math|title|head|xmp|plaintext';

// Testo leggibile: senza tag HTML né sintassi Markdown.
function ezine_testo_pulito($contenuto): string {
    $t = preg_replace(['/<!--.*?-->/su', '#<(' . EZINE_TAG_NASCOSTI . ')\b(?:[^>]*[^/>])?>.*?(?:</\1\s*>|$)#isu'], ' ',
                      ezine_testo_redazionale($contenuto));
    $t = strip_tags($t);
    $t = preg_replace('/\[([^\]]*)\]\([^)]*\)/u', '$1', $t);   // [testo](url) -> testo
    $t = str_replace(['**', '__'], '', $t);
    $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $t));
}

function ezine_statistiche($contenuto): array {
    $testo = ezine_testo_pulito($contenuto);
    preg_match_all('/[\p{L}\p{N}][\p{L}\p{N}\'’-]*/u', $testo, $m);
    return ['chars' => mb_strlen($testo), 'words' => count($m[0])];
}

// ---------- uscite ----------

function ezine_colonne_elenco(): string {
    return 'id, title, data, created_at, updated_at, char_count, word_count, size_kb, tags';
}

// Una riga dell'elenco pronta per il JSON: le etichette come array.
function ezine_riga_elenco(array $row): array {
    $row['tags'] = ezine_etichette(json_decode($row['tags'] ?? '[]', true));
    return $row;
}

// Etichette ripulite: testo semplice, al massimo 8 da 30 caratteri, senza
// doppioni (anche se scritti con maiuscole diverse). Accetta un array o un
// testo separato da virgole.
const EZINE_MAX_ETICHETTE = 8;
const EZINE_MAX_LUNGHEZZA_ETICHETTA = 30;

function ezine_etichette($valore): array {
    if (is_string($valore)) $valore = explode(',', $valore);
    if (!is_array($valore)) return [];
    $out = [];
    foreach ($valore as $t) {
        if (!is_string($t)) continue;
        $t = preg_replace(['/<[^>]*>/u', '/[\p{C}<>"]+/u'], '', $t) ?? '';   // tag, controlli, < > " sciolti
        $t = trim(preg_replace('/\s+/u', ' ', $t));
        $t = trim(mb_substr($t, 0, EZINE_MAX_LUNGHEZZA_ETICHETTA));
        if ($t === '' || isset($out[mb_strtolower($t)])) continue;
        $out[mb_strtolower($t)] = $t;
        if (count($out) >= EZINE_MAX_ETICHETTE) break;
    }
    return array_values($out);
}

function ezine_salva_revisione(SQLite3 $db, int $issueId, string $motivo): void {
    $st = $db->prepare("INSERT INTO issue_revisions (issue_id, title, data, content, reason)
                        SELECT id, title, data, content, :motivo FROM issues WHERE id = :id");
    $st->bindValue(':motivo', $motivo, SQLITE3_TEXT);
    $st->bindValue(':id', $issueId, SQLITE3_INTEGER);
    $st->execute();
    // Conserva le ultime 30 revisioni per uscita.
    $st = $db->prepare("DELETE FROM issue_revisions WHERE issue_id = :id AND id NOT IN (
                          SELECT id FROM issue_revisions WHERE issue_id = :id ORDER BY id DESC LIMIT 30)");
    $st->bindValue(':id', $issueId, SQLITE3_INTEGER);
    $st->execute();
}

// ---------- pubblicazione ----------

function ezine_config_pubblicazione(SQLite3 $db): array {
    $v = $db->querySingle("SELECT value FROM settings WHERE key = 'publishing'");
    $c = $v ? json_decode($v, true) : [];
    return ['publicUrl' => (string) ($c['publicUrl'] ?? ''), 'noindex' => (bool) ($c['noindex'] ?? true)];
}

// Nome di file leggibile e stabile per un'uscita pubblicata: anno-i-numero-3.html
function ezine_slug(SQLite3 $db, int $issueId, string $anno, string $numero): string {
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(
        iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', "anno $anno numero $numero") ?: '')), '-');
    if ($base === '' || $base === 'anno-numero') $base = "uscita-$issueId";
    $slug = $base;
    $st = $db->prepare('SELECT 1 FROM publications WHERE slug = :s AND issue_id != :id');
    $st->bindValue(':id', $issueId, SQLITE3_INTEGER);
    for ($n = 2; ; $n++) {
        $st->bindValue(':s', $slug, SQLITE3_TEXT);
        $occupato = $st->execute()->fetchArray();
        $st->reset();
        if (!$occupato) return $slug;
        $slug = "$base-$n";
    }
}

// ---------- immagini ----------

// Errore con codice HTTP: chi chiama decide se rispondere al client o saltare
// l'elemento (il ripristino di un backup salta le immagini illeggibili).
class EzineErrore extends RuntimeException {}

// Ricodifica sempre l'immagine con GD: è questo passaggio a eliminare in modo
// garantito i metadati (EXIF, GPS, nome del dispositivo), qualunque cosa arrivi
// dal client. Restituisce il percorso relativo alla cartella dell'app.
// $nome forza il nome del file (usato dal ripristino dei backup, per non
// rompere i riferimenti già presenti nei contenuti).
function ezine_salva_immagine(string $bytes, ?string $nome = null): array {
    if ($nome !== null && !preg_match('/^[a-f0-9]{24}\.(png|jpg)$/', $nome)) {
        throw new EzineErrore('Nome immagine non valido', 400);
    }
    if (strlen($bytes) > 15 * 1024 * 1024) throw new EzineErrore('Immagine troppo grande (massimo 15 MB)', 413);

    $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
    $ammessi = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $ammessi, true)) throw new EzineErrore('Formato non supportato: usa JPEG, PNG, WebP o GIF', 415);

    $info = getimagesizefromstring($bytes);
    if (!$info) throw new EzineErrore('Il file non è un\'immagine leggibile', 415);
    [$w, $h] = $info;
    if ($w * $h > 40_000_000) throw new EzineErrore('Immagine troppo grande (oltre 40 megapixel)', 413);

    ini_set('memory_limit', '384M');
    $src = imagecreatefromstring($bytes);
    if (!$src) throw new EzineErrore('Impossibile decodificare l\'immagine', 415);

    // Lato massimo 2400 px: oltre non serve né a schermo né in stampa A4.
    $max = 2400;
    if ($w > $max || $h > $max) {
        $k = $max / max($w, $h);
        $nw = (int) round($w * $k);
        $nh = (int) round($h * $k);
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        $src = $dst;
        [$w, $h] = [$nw, $nh];
    }

    // PNG per ciò che ha trasparenza o è già a pochi colori (le immagini
    // retinate arrivano così e restano nitide), JPEG per le fotografie.
    $png = $nome !== null ? str_ends_with($nome, '.png') : in_array($mime, ['image/png', 'image/gif'], true);
    if (!is_dir(DIR_UPLOADS) && !mkdir(DIR_UPLOADS, 0775, true)) {
        throw new EzineErrore('Impossibile creare la cartella uploads', 500);
    }
    $nome ??= bin2hex(random_bytes(12)) . ($png ? '.png' : '.jpg');
    if (!$png) {
        // JPEG non ha trasparenza: senza uno sfondo bianco le zone trasparenti
        // (es. WebP con canale alfa) diventerebbero nere.
        $bg = imagecreatetruecolor($w, $h);
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagecopy($bg, $src, 0, 0, 0, 0, $w, $h);
        imagedestroy($src);
        $src = $bg;
    }
    $percorso = DIR_UPLOADS . '/' . $nome;
    if ($png) {
        $ok = imagepng($src, $percorso, 9);
    } else {
        ob_start();
        $ok = imagejpeg($src, null, 86);
        $ok = $ok && file_put_contents($percorso, ezine_jpeg_senza_commenti(ob_get_clean())) !== false;
    }
    imagedestroy($src);
    if (!$ok) throw new EzineErrore('Salvataggio dell\'immagine non riuscito', 500);
    chmod($percorso, 0664);

    return ['url' => 'uploads/' . $nome, 'width' => $w, 'height' => $h, 'bytes' => filesize($percorso)];
}

// GD scrive in ogni JPEG un commento "CREATOR: gd-jpeg ...". Non identifica
// nessuno, ma l'obiettivo è che nei file non resti alcun metadato: si rimuovono
// i segmenti COM (0xFFFE) che precedono i dati dell'immagine.
function ezine_jpeg_senza_commenti(string $jpg): string {
    if (substr($jpg, 0, 2) !== "\xFF\xD8") return $jpg;
    $out = "\xFF\xD8";
    $i = 2;
    $n = strlen($jpg);
    while ($i + 4 <= $n && $jpg[$i] === "\xFF") {
        $marker = ord($jpg[$i + 1]);
        if ($marker === 0xDA) break;                     // inizio dei dati compressi
        $len = unpack('n', substr($jpg, $i + 2, 2))[1];
        if ($marker !== 0xFE) $out .= substr($jpg, $i, $len + 2);
        $i += $len + 2;
    }
    return $out . substr($jpg, $i);
}

// Tutti i percorsi uploads/... citati da uscite, revisioni, bozza e impostazioni.
function ezine_immagini_in_uso(SQLite3 $db): array {
    $testi = [];
    foreach (['SELECT content FROM issues', 'SELECT content FROM issue_revisions',
              'SELECT payload FROM drafts', 'SELECT value FROM settings'] as $q) {
        $r = $db->query($q);
        while ($row = $r->fetchArray(SQLITE3_NUM)) $testi[] = (string) $row[0];
    }
    // Tollera anche "uploads\/..." (json_encode senza JSON_UNESCAPED_SLASHES).
    preg_match_all('#uploads\\\\?/([a-f0-9]{24}\.(?:png|jpg))#', implode("\n", $testi), $m);
    return array_values(array_unique($m[1]));
}
