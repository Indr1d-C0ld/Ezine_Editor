<?php
// GET  -> uscite pubblicate e configurazione del sito pubblico
// POST {config: {publicUrl, noindex}} -> salva la configurazione
require __DIR__ . '/lib.php';
ezine_metodo('GET', 'POST');
$db = ezine_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = ezine_input_json();
    $c = $in['config'] ?? null;
    if (!is_array($c)) ezine_errore('Configurazione mancante', 400);
    $url = trim((string) ($c['publicUrl'] ?? ''));
    if ($url !== '') {
        if (!preg_match('#^https?://[^\s/$.?\#][^\s]*$#i', $url)) ezine_errore('Indirizzo non valido: deve iniziare con http:// o https://', 400);
        $url = rtrim($url, '/') . '/';
    }
    $st = $db->prepare("INSERT INTO settings (key, value) VALUES ('publishing', :v)
                        ON CONFLICT(key) DO UPDATE SET value = excluded.value");
    $st->bindValue(':v', json_encode(['publicUrl' => $url, 'noindex' => (bool) ($c['noindex'] ?? true)], JSON_UNESCAPED_SLASHES), SQLITE3_TEXT);
    $st->execute();
    ezine_json(['success' => true, 'config' => ezine_config_pubblicazione($db)]);
}

$r = $db->query("SELECT p.issue_id, p.slug, p.title, p.anno, p.numero, p.data, p.published_at, length(p.html) AS bytes,
                        (i.updated_at IS NOT p.source_updated_at) AS stale
                 FROM publications p JOIN issues i ON i.id = p.issue_id
                 ORDER BY p.published_at DESC");
$items = [];
while ($row = $r->fetchArray(SQLITE3_ASSOC)) { $row['stale'] = (bool) $row['stale']; $items[] = $row; }
ezine_json(['config' => ezine_config_pubblicazione($db), 'items' => $items]);
