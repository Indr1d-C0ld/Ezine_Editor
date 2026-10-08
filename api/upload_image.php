<?php
// Caricamento immagini. Il client invia già un'immagine ridimensionata e
// ricodificata, ma la ricodifica qui con GD è ciò che garantisce la rimozione
// dei metadati (posizione GPS inclusa) anche se il client venisse aggirato.
require __DIR__ . '/lib.php';
ezine_metodo('POST');
ezine_db(); // assicura le migrazioni
$f = $_FILES['image'] ?? null;
if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    $codici = [UPLOAD_ERR_INI_SIZE => 'File oltre il limite del server', UPLOAD_ERR_FORM_SIZE => 'File troppo grande',
               UPLOAD_ERR_PARTIAL => 'Caricamento interrotto', UPLOAD_ERR_NO_FILE => 'Nessun file ricevuto'];
    ezine_errore($codici[$f['error'] ?? UPLOAD_ERR_NO_FILE] ?? 'Caricamento non riuscito', 400);
}
if (!is_uploaded_file($f['tmp_name'])) ezine_errore('Caricamento non valido', 400);
try {
    ezine_json(ezine_salva_immagine(file_get_contents($f['tmp_name'])));
} catch (EzineErrore $e) {
    ezine_errore($e->getMessage(), $e->getCode() ?: 400);
}
