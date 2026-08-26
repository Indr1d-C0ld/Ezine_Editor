<?php
// setup_db.php – crea il database SQLite e la tabella issues

// Percorso assoluto del database
$dbPath = __DIR__ . '/ezine.db';

try {
    // Apre (o crea) il database SQLite
    $db = new SQLite3($dbPath);
    
    // Query per creare la tabella
    $query = "CREATE TABLE IF NOT EXISTS issues (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT,
        date TEXT,
        data TEXT,
        content TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        char_count INTEGER,
        word_count INTEGER,
        size_kb REAL
    )";
    
    // Esegue la query
    $result = $db->exec($query);
    
    if ($result) {
        echo "✅ Database e tabella 'issues' creati con successo!<br>";
        echo "Percorso: " . $dbPath;
    } else {
        echo "❌ Errore durante la creazione della tabella: " . $db->lastErrorMsg();
    }
    
    // Chiude il database
    $db->close();
    
} catch (Exception $e) {
    echo "❌ Eccezione: " . $e->getMessage();
}
?>
