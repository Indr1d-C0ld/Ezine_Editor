<?php
// search_issues.php – ricerca full-text nel testo degli articoli.
//
// Cerca solo nel testo scritto dall'autore, non nell'intero JSON: cercando
// direttamente in 'content' una parola come "text" o "article" corrisponderebbe
// a ogni uscita (sono nomi di campi), e i campi 'image' contengono immagini in
// base64 che genererebbero corrispondenze casuali.
header('Content-Type: application/json');

// Stessi campi usati dalla nuvola di parole chiave in archivio.php.
const CAMPI_TESTUALI = ['text', 'title', 'kicker', 'byline', 'imageCaption', 'nextIssue', 'fixedRubric'];

function estrai_testo_redazionale($nodo, $chiave): string {
    if (is_string($nodo)) {
        return in_array($chiave, CAMPI_TESTUALI, true) ? $nodo . ' ' : '';
    }
    if (is_array($nodo)) {
        $out = '';
        foreach ($nodo as $k => $v) {
            // Negli elenchi le chiavi sono numeriche: si conserva quella del
            // contenitore (es. 'roundup'), altrimenti si usa la chiave propria.
            $out .= estrai_testo_redazionale($v, is_int($k) ? $chiave : $k);
        }
        return $out;
    }
    return '';
}

$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

$db = new SQLite3(__DIR__ . '/ezine.db');
$res = $db->query("SELECT id, title, data, content, created_at, char_count, word_count, size_kb
                   FROM issues ORDER BY created_at DESC");

$trovati = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $contenuto = json_decode($row['content'], true);
    $testo = strip_tags(estrai_testo_redazionale($contenuto, null));
    // Il titolo dell'uscita e la data di testata restano cercabili insieme al testo.
    $cercabile = $row['title'] . ' ' . $row['data'] . ' ' . $testo;
    if (mb_stripos($cercabile, $q) !== false) {
        // 'content' non torna al client: è il campo più pesante (immagini incluse).
        unset($row['content']);
        $trovati[] = $row;
    }
}
echo json_encode($trovati);
?>
