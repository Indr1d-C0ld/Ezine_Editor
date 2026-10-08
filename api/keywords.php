<?php
// Parole più frequenti dell'intero archivio, calcolate qui in un'unica
// richiesta: prima il browser scaricava ogni uscita una per una.
require __DIR__ . '/lib.php';
ezine_metodo('GET');

$stop = array_flip(['il','lo','la','gli','le','un','uno','una','e','ed','o','ma','per','con','su','tra','fra',
  'da','a','in','di','che','non','si','ci','ciò','questo','questa','questi','queste','quello','quella',
  'quelli','quelle','io','tu','lui','lei','noi','voi','loro','mio','tuo','suo','nostro','vostro','me','te',
  'se','ne','della','delle','dei','degli','alla','alle','ai','agli','dalla','dalle','dai','dagli','sulla',
  'sulle','sui','sugli','nella','nelle','nei','negli','dello','allo','dallo','sullo','nello','come','anche',
  'sono','essere','avere','fare','dire','potere','volere','sapere','stare','andare','venire','molto','tutto',
  'tutti','tutte','dove','quando','perché','perche','più','piu','già','gia','ancora','sempre','mai','solo',
  'così','cosi','poi','ogni','altro','altri','altra','altre','stato','stata','essere','hanno','aveva',
  'dell','nell','sull','dall','all','coll','quell','anch','dev','qual','senza','dopo','prima','sotto','sopra',
  'verso','mentre','però','pero','quindi','oppure','cosa','fatto','anni','anno']);

$conta = [];
$r = ezine_db()->query('SELECT content FROM issues');
while ($row = $r->fetchArray(SQLITE3_NUM)) {
    $testo = mb_strtolower(ezine_testo_pulito(json_decode($row[0], true)));
    preg_match_all('/\p{L}{4,}/u', $testo, $m);
    foreach ($m[0] as $p) {
        if (!isset($stop[$p])) $conta[$p] = ($conta[$p] ?? 0) + 1;
    }
}
arsort($conta);
$out = [];
foreach (array_slice($conta, 0, 30, true) as $parola => $n) $out[] = ['word' => $parola, 'count' => $n];
ezine_json($out);
