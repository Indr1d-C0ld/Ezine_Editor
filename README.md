# 📰 Ezine Editor

**Editor + archivio in stile giornale per una propria ezine personale** — componi numeri con testata, articoli su più colonne, immagini, rubriche fisse e anteprima live, e li archivi con ricerca nel testo, statistiche e stampa/esportazione.

Self-hosted, zero dipendenze: solo PHP + SQLite lato server, HTML/CSS/JS puro lato client. Nessun framework, nessun build step, nessun account esterno.

![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)

---

## Indice

- [Caratteristiche](#caratteristiche)
- [Architettura](#architettura)
- [Requisiti](#requisiti)
- [Installazione](#installazione)
- [Uso](#uso)
- [Sicurezza](#sicurezza)
- [Personalizzazione](#personalizzazione)
- [Licenza](#licenza)

## Caratteristiche

- Editor visuale con anteprima live: articolo full-width, due colonne (sinistra/destra), immagini con float e stili (mezzatinta/bitmap), rubriche fisse, finto annuncio, box "prossimo numero"
- Esportazione in un singolo file HTML (CSS e immagini degli articoli incorporati) e stampa/PDF diretta dal browser
- Archivio con ricerca nel testo degli articoli e per titolo, statistiche per numero (caratteri, parole, dimensione) e nuvola delle parole più frequenti dell'archivio
- Persistenza automatica in `localStorage` mentre lavori, oltre al salvataggio esplicito nell'archivio

## Architettura

| File | Ruolo |
|---|---|
| `index.html` | Editor: pannello di controllo a sinistra, anteprima del giornale a destra |
| `archivio.php` | Elenco/ricerca dei numeri salvati, visualizza/stampa/elimina |
| `save_issue.php` / `update_issue.php` / `load_issue.php` / `delete_issue.php` / `stats.php` | Endpoint JSON per il CRUD sul database |
| `search_issues.php` | Ricerca lato server nel testo degli articoli |
| `setup_db.php` | Crea il database SQLite e la tabella `issues` (idempotente) |

Tutto il contenuto di un numero (articoli, testi, immagini in base64, impostazioni) è serializzato come JSON in un'unica colonna `content` — nessuna migrazione di schema da gestire quando aggiungi campi.

## Requisiti

- Apache 2.4 con `mod_rewrite`/`mod_authn_file`/`mod_auth_basic` disponibili (per l'autenticazione opzionale, vedi sotto) e `AllowOverride All` sulla cartella
- PHP 8+ con estensione `sqlite3` (`php -m | grep sqlite3`)
- Nessun'altra dipendenza: niente Composer, niente npm

## Installazione

1. Copia tutti i file in una sottocartella del document root (es. `/var/www/html/ezine/`).
2. Rendi scrivibili la cartella e il futuro database dall'utente con cui gira Apache (es. `www-data`), altrimenti i salvataggi falliranno con *"attempt to write a readonly database"*:
   ```bash
   chmod 775 /percorso/ezine
   ```
3. Apri `setup_db.php` una volta dal browser per creare `ezine.db` e la tabella `issues`.
4. (Consigliato) attiva l'autenticazione — vedi [Sicurezza](#sicurezza).
5. Vai su `index.html`: sei pronto per comporre il primo numero.

## Uso

- **Editor** (`index.html`): compila testata (anno, numero, data, colore), aggiungi articoli nelle varie sezioni, guarda l'anteprima aggiornarsi in tempo reale. "Salva nuova uscita" scrive nel database; se stai modificando un numero esistente (aperto dall'archivio) usa "Aggiorna" invece di creare un duplicato.
- **Archivio** (`archivio.php`): cerca per titolo o nel testo degli articoli, consulta la nuvola delle parole più frequenti, visualizza/stampa/elimina ogni numero.

> **Nota sulla ricerca.** Cercare una parola chiave interroga il server (`search_issues.php`), che scorre il testo degli articoli — titoli, testi, occhielli, firme e didascalie — ignorando la struttura dati e le immagini: cercare `text` o `strong` non restituisce quindi ogni uscita. La nuvola applica lo stesso criterio per contare le parole, ma serve solo a dare un'idea dei temi ricorrenti: le parole non sono cliccabili per filtrare.

## Sicurezza

Di base l'app **non ha autenticazione**: chiunque conosca l'URL può leggere, modificare e cancellare l'archivio. Per uso non strettamente locale è fortemente consigliato attivare HTTP Basic Auth a livello di cartella — protegge automaticamente sia le pagine che gli endpoint, senza bisogno di scrivere codice:

```bash
htpasswd -cB -C 10 /percorso/ezine/.htpasswd <utente>
```

`-C 10` alza il costo bcrypt rispetto al valore predefinito (5), ormai troppo basso. Non salire oltre senza motivo: Basic Auth riverifica l'hash a ogni richiesta HTTP, quindi un costo alto rallenta l'archivio in modo proporzionale al numero di uscite.

poi rinomina [`.htaccess.example`](.htaccess.example) in `.htaccess` e aggiorna `AuthUserFile` con il percorso assoluto reale. Dettagli e motivazione nel file stesso.

Lo stesso file imposta `X-Content-Type-Options`, `X-Frame-Options` e `Referrer-Policy` (richiede `mod_headers`), e spiega perché **non** include una Content-Security-Policy: l'app usa script e stili inline, quindi una policy severa la romperebbe e una permissiva non proteggerebbe da nulla.

## Personalizzazione

- **Testata**: cerca `La Mia Ezine` in `index.html` e `archivio.php` e sostituiscilo col nome della tua testata (compare anche nel `<title>`, nel disclaimer a fondo pagina e nel titolo delle finestre di stampa).
- **Logo**: aggiungi un file immagine nella cartella e puntalo dall'`<img class="header-logo" src="...">` in entrambi i file — se il file manca, il logo semplicemente non viene mostrato (nessun errore, gestito via `onerror`).
- **Motto e colori**: modifica direttamente `.motto`/`.subhead` nell'HTML e le variabili colore nel CSS (`#8b1f1f` è il rosso usato per titolo e accenti).

## Licenza

[GNU GPLv3](LICENSE) — libero di usarlo, modificarlo e ridistribuirlo, a patto che le opere derivate restino sotto la stessa licenza.
