<?php
// Pacchetto del sito pubblico (.zip): indice, una pagina per uscita pubblicata,
// robots.txt, feed RSS (se è indicato l'indirizzo pubblico) e istruzioni.
//
// Il sito non viene mai servito da questo server: si scarica e si carica su un
// hosting statico o un servizio onion. Ogni pagina è blindata: la
// Content-Security-Policy ammette solo immagini del sito stesso e vieta
// qualunque richiesta esterna, così chi legge non contatta nessun sito terzo
// (nemmeno per errore, con un'immagine esterna rimasta in un articolo).
//
// Le istantanee salvate hanno le immagini incorporate (data URI). Qui vengono
// estratte in file separati: la pagina pesa un terzo in meno (niente base64),
// il testo appare subito e le immagini arrivano quando servono, e quelle
// ripetute, come il logo in ogni uscita, si scaricano una volta sola. Conta
// soprattutto sui servizi onion, dove la banda è poca.
require __DIR__ . '/lib.php';
ezine_metodo('GET');
ini_set('memory_limit', '384M');   // decodifica e ricampionamento delle immagini
$db = ezine_db();
$cfg = ezine_config_pubblicazione($db);

$mh = json_decode($db->querySingle("SELECT value FROM settings WHERE key = 'masthead'") ?: '{}', true) ?: [];
$nome = $mh['fullName'] ?? 'La Mia Ezine';
$nomeA = $mh['nameA'] ?? 'LA MIA';
$nomeB = $mh['nameB'] ?? 'EZINE';
$motto = $mh['motto'] ?? '';
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

$r = $db->query('SELECT * FROM publications ORDER BY published_at DESC, issue_id DESC');
$uscite = [];
while ($row = $r->fetchArray(SQLITE3_ASSOC)) $uscite[] = $row;
if (!$uscite) ezine_errore('Nessuna uscita pubblicata: pubblicane almeno una dall\'archivio', 404);

$head = '<meta name="referrer" content="no-referrer">'
      . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src \'self\' data:; style-src \'unsafe-inline\'; base-uri \'none\'; form-action \'none\'">'
      . ($cfg['noindex'] ? '<meta name="robots" content="noindex, nofollow, noarchive">' : '')
      // con width/height dichiarati il browser riserva lo spazio prima che
      // l'immagine arrivi; height:auto mantiene le proporzioni quando la
      // colonna è più stretta dell'immagine
      . '<style>.newspaper img.article-img[width] { height: auto; }</style>';

// Le immagini caricate arrivano fino a 2400 px: per leggere a schermo ne
// bastano 1600. Solo le fotografie (JPEG) vengono ridotte e ricompresse, e solo
// se il file risulta davvero più leggero; i PNG retinati restano intatti,
// perché ricampionarli rovinerebbe il retino.
function alleggerisci(string $bytes, string $tipo): array {
    $ext = ['jpeg' => 'jpg', 'png' => 'png', 'gif' => 'gif', 'webp' => 'webp'][$tipo];
    if ($tipo !== 'jpeg') return [$bytes, $ext];
    $info = @getimagesizefromstring($bytes);
    if (!$info || max($info[0], $info[1]) <= 1600) return [$bytes, $ext];
    $src = @imagecreatefromstring($bytes);
    if (!$src) return [$bytes, $ext];
    $k = 1600 / max($info[0], $info[1]);
    $w = (int) round($info[0] * $k);
    $h = (int) round($info[1] * $k);
    $dst = imagecreatetruecolor($w, $h);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
    ob_start();
    imagejpeg($dst, null, 82);
    $nuovo = ezine_jpeg_senza_commenti(ob_get_clean());
    return [strlen($nuovo) < strlen($bytes) ? $nuovo : $bytes, $ext];
}

// Sostituisce in un tag <img> l'immagine incorporata con un file in img/.
// Il nome deriva dal contenuto: la stessa immagine in più pagine è un file solo.
function estrai_immagine(string $tag, array &$immagini): string {
    $marca = 'src="data:image/';
    $a = strpos($tag, $marca);
    if ($a === false) return $tag;
    $t = $a + strlen($marca);
    $b64 = strpos($tag, ';base64,', $t);
    $chiusa = strpos($tag, '"', $t);
    if ($b64 === false || $chiusa === false || $b64 > $chiusa) return $tag;
    $tipo = substr($tag, $t, $b64 - $t);
    if (!in_array($tipo, ['png', 'jpeg', 'gif', 'webp'], true)) return $tag;
    $bytes = base64_decode(substr($tag, $b64 + 8, $chiusa - $b64 - 8), true);
    if ($bytes === false || strlen($bytes) < 1024) return $tag;    // minuscole: restano incorporate
    [$bytes, $ext] = alleggerisci($bytes, $tipo);
    $nome = 'img/' . substr(hash('sha256', $bytes), 0, 20) . '.' . $ext;
    $immagini[$nome] = $bytes;
    $extra = '';
    // dimensioni e caricamento differito solo per le immagini degli articoli:
    // il logo ha un'altezza fissa nel CSS e sta in cima, va mostrato subito
    if (str_contains($tag, 'article-img')) {
        $info = @getimagesizefromstring($bytes);
        if ($info) $extra .= " width=\"{$info[0]}\" height=\"{$info[1]}\"";
        $extra .= ' loading="lazy" decoding="async"';
    }
    return substr($tag, 0, $a) . 'src="' . $nome . '"' . $extra . substr($tag, $chiusa + 1);
}

// Scorre i tag <img> senza espressioni regolari sull'intera pagina: con
// immagini incorporate da diversi MB una regex rischierebbe di superare i
// limiti interni di PCRE.
function estrai_immagini(string $html, array &$immagini): string {
    $out = '';
    $pos = 0;
    while (($i = strpos($html, '<img', $pos)) !== false) {
        $fine = strpos($html, '>', $i);
        if ($fine === false) break;
        $out .= substr($html, $pos, $i - $pos) . estrai_immagine(substr($html, $i, $fine - $i + 1), $immagini);
        $pos = $fine + 1;
    }
    return $out . substr($html, $pos);
}

$immagini = [];

// Il logo dell'indice, ridotto e incorporato come per le pagine delle uscite.
function logo_incorporato(?string $percorso): string {
    if (!$percorso) return '';
    $base = realpath(__DIR__ . '/..');
    $file = realpath($base . '/' . $percorso);
    if (!$file || !str_starts_with($file, $base . '/') || !is_file($file)) return '';
    $img = @imagecreatefromstring((string) file_get_contents($file));
    if (!$img) return '';
    $h = 120;
    $w = max(1, (int) round(imagesx($img) * $h / imagesy($img)));
    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $img, 0, 0, 0, 0, $w, $h, imagesx($img), imagesy($img));
    ob_start();
    imagepng($out, null, 9);
    return 'data:image/png;base64,' . base64_encode(ob_get_clean());
}

// ---------- pagine delle uscite ----------
$pagine = [];
foreach ($uscite as $u) {
    $html = $u['html'];
    // gli attributi onerror dell'editor sono script inline: qui non servono
    $html = preg_replace('/\s+onerror="[^"]*"/i', '', $html);
    $html = preg_replace('/<meta charset="UTF-8">/i', '<meta charset="UTF-8">' . $head, $html, 1);
    $nav = '<nav style="font:14px/1.4 Georgia,serif;text-align:center;margin:0 0 6mm">'
         . '<a href="index.html" style="color:#333">← Tutte le uscite</a></nav>'
         . '<style>@media print { nav { display: none } }</style>';
    $html = preg_replace('/(<body[^>]*>)/i', '$1' . $nav, $html, 1);
    $pagine[$u['slug'] . '.html'] = estrai_immagini($html, $immagini);
}

// ---------- indice ----------
$logo = logo_incorporato($mh['logo'] ?? '');
$righe = '';
foreach ($uscite as $u) {
    $righe .= '<li><a href="' . $e($u['slug']) . '.html"><span class="num">Anno ' . $e($u['anno']) . ' – Numero ' . $e($u['numero'])
            . '</span><span class="tit">' . $e($u['title']) . '</span><span class="dat">' . $e($u['data']) . '</span></a></li>';
}
$feedLink = $cfg['publicUrl'] ? '<p class="feed"><a href="feed.xml">Feed RSS</a></p>' : '';
$indice = '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8">' . $head
  . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $e($nome) . '</title>'
  . '<style>
      body { margin: 0; background: #e6e3db; color: #111; font-family: "Courier New", Courier, monospace; }
      main { max-width: 760px; margin: 0 auto; background: #fef9ef; min-height: 100vh; padding: 40px 28px; box-sizing: border-box; border-left: 1px solid #222; border-right: 1px solid #222; }
      header { text-align: center; border-bottom: 4px double #111; padding-bottom: 18px; margin-bottom: 26px; }
      header img { height: 64px; vertical-align: middle; margin-right: 12px; }
      h1 { display: inline-block; vertical-align: middle; margin: 0; font: 800 clamp(1.8rem, 6vw, 3rem)/1.1 Georgia, "Times New Roman", serif; text-transform: uppercase; letter-spacing: -1px; }
      h1 .a { color: #8b1f1f; }
      .motto { font-style: italic; color: #444; margin: 10px 0 0; font-size: .85rem; }
      h2 { font: bold .8rem monospace; text-transform: uppercase; letter-spacing: 2px; border-bottom: 1px solid #888; padding-bottom: 6px; }
      ul { list-style: none; padding: 0; margin: 0; }
      li a { display: grid; grid-template-columns: 11rem 1fr; gap: 2px 16px; padding: 14px 4px; border-bottom: 1px dashed #aaa; color: inherit; text-decoration: none; }
      li a:hover .tit, li a:focus .tit { text-decoration: underline; }
      .num { font-size: .78rem; color: #8b1f1f; font-weight: bold; }
      .tit { font: bold 1.05rem Georgia, serif; }
      .dat { grid-column: 2; font-size: .75rem; color: #555; }
      .feed { text-align: center; font-size: .8rem; margin-top: 30px; }
      .feed a { color: #8b1f1f; }
      footer { text-align: center; font-size: .7rem; color: #555; margin-top: 40px; border-top: 2px solid #111; padding-top: 12px; }
      @media (max-width: 560px) { li a { grid-template-columns: 1fr; } .dat { grid-column: 1; } }
    </style></head><body><main><header>'
  . ($logo ? '<img src="' . $logo . '" alt="">' : '')
  . '<h1><span class="a">' . $e($nomeA) . '</span> ' . $e($nomeB) . '</h1>'
  . ($motto ? '<p class="motto">“' . $e($motto) . '”</p>' : '')
  . '</header><h2>Uscite</h2><ul>' . $righe . '</ul>' . $feedLink
  . '<footer>' . $e($nome) . '</footer></main></body></html>';
$indice = estrai_immagini($indice, $immagini);

// ---------- feed RSS ----------
// Le date sono arrotondate al giorno: un orario preciso direbbe a che ora
// lavora la redazione, e da lì in quale fuso orario vive.
$feed = null;
if ($cfg['publicUrl']) {
    $x = fn($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $items = '';
    foreach ($uscite as $u) {
        $url = $cfg['publicUrl'] . $u['slug'] . '.html';
        $giorno = gmdate('D, d M Y', strtotime($u['published_at'] . ' UTC')) . ' 00:00:00 +0000';
        $items .= "<item><title>{$x("Anno {$u['anno']} – Numero {$u['numero']}: {$u['title']}")}</title>"
                . "<link>{$x($url)}</link><guid isPermaLink=\"true\">{$x($url)}</guid>"
                . "<pubDate>$giorno</pubDate><description>{$x($u['data'])}</description></item>";
    }
    $feed = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
          . '<rss version="2.0"><channel>'
          . "<title>{$x($nome)}</title><link>{$x($cfg['publicUrl'])}</link>"
          . "<description>{$x($motto ?: $nome)}</description><language>it</language>"
          . $items . '</channel></rss>';
}

$robots = $cfg['noindex'] ? "User-agent: *\nDisallow: /\n" : "User-agent: *\nAllow: /\n";

$leggimi = "Sito pubblico di $nome\n" . str_repeat('=', mb_strlen("Sito pubblico di $nome")) . "\n\n"
  . "Contiene " . count($uscite) . " uscit" . (count($uscite) === 1 ? 'a' : 'e') . " pubblicat" . (count($uscite) === 1 ? 'a' : 'e') . ", la pagina indice"
  . ($feed ? ", il feed RSS" : '') . " e robots.txt.\n\n"
  . "Le immagini stanno nella cartella img/: va caricata insieme alle pagine.\n\n"
  . "Sono semplici file statici: si caricano così come sono su qualunque hosting statico\n"
  . "o servizio onion. Non serve PHP, né un database.\n\n"
  . "Una Content-Security-Policy ammette solo le immagini del sito stesso e impedisce\n"
  . "al browser di chi legge di contattare qualunque altro sito. Nessun font esterno,\n"
  . "nessuno script, nessun tracciamento.\n\n"
  . ($cfg['noindex'] ? "Le pagine chiedono ai motori di ricerca di non indicizzarle.\n\n" : '')
  . "Attenzione: queste protezioni riguardano chi legge. Chi ospita i file vede gli\n"
  . "indirizzi IP dei visitatori, e l'indirizzo del server rivela chi lo gestisce.\n"
  . "Scegli un hosting che non sia riconducibile a te.\n";

// ---------- archivio ----------
// Lo ZIP registra le date nell'ora locale del server: con il fuso italiano la
// data fissa diventerebbe 01:00, rivelando il fuso orario. In UTC resta 00:00.
// (PHP ripristina l'ambiente originale a fine richiesta.)
putenv('TZ=UTC');
$tmp = tempnam(sys_get_temp_dir(), 'ezsite');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) ezine_errore('Impossibile creare il pacchetto', 500);
$dir = 'sito/';
$file = ['index.html' => $indice, 'robots.txt' => $robots, 'LEGGIMI.txt' => $leggimi] + $pagine + $immagini;
if ($feed) $file['feed.xml'] = $feed;
foreach ($file as $nomeFile => $contenuto) {
    $zip->addFromString($dir . $nomeFile, $contenuto);
    // Data fissa sui file: l'orario reale di creazione del pacchetto non deve
    // finire dentro l'archivio che verrà diffuso.
    $zip->setMtimeName($dir . $nomeFile, 315532800);   // 1980-01-01
}
$zip->close();

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="sito-pubblico.zip"');
header('Content-Length: ' . filesize($tmp));
readfile($tmp);
unlink($tmp);
