# 📰 Ezine Editor

**Editor e archivio per una ezine personale in stile giornale** — componi numeri di più pagine con anteprima A4 reale, carica immagini ripulite dai metadati e retinate per la fotocopia, stampa a libretto, archivia con cronologia delle versioni e pubblica un sito statico da caricare dove vuoi.

Self-hosted e senza dipendenze: PHP + SQLite lato server, HTML/CSS/JS puro lato client. Nessun framework, nessun build step, nessun servizio esterno, nessun font o script caricato da altri siti.

![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)

---

## Indice

- [Caratteristiche](#caratteristiche)
- [Requisiti](#requisiti)
- [Installazione](#installazione)
- [Uso](#uso)
- [Immagini e privacy](#immagini-e-privacy)
- [Stampa e fotocopia](#stampa-e-fotocopia)
- [Archivio, versioni e backup](#archivio-versioni-e-backup)
- [Pubblicazione per i lettori](#pubblicazione-per-i-lettori)
- [Sicurezza](#sicurezza)
- [Architettura](#architettura)
- [Licenza](#licenza)

## Caratteristiche

**Composizione**
- Anteprima a dimensione A4 reale: il testo va a capo esattamente come sulla carta, e l'editor segnala le pagine che eccedono il foglio, con la percentuale
- Prima pagina classica (articolo a tutta larghezza, due colonne, riquadro, tre colonne di brevi, consiglio, piè di pagina) più **pagine interne** a 1–3 colonne, con il testo che scorre da una colonna all'altra
- L'anteprima mostra ciò che stai scrivendo mentre lo scrivi, tratteggiato finché non lo aggiungi
- Articoli spostabili trascinandoli, anche fra colonne e pagine diverse
- Testo in **Markdown essenziale** (`**grassetto**`, `*corsivo*`, `[link](https://…)`, riga vuota = nuovo paragrafo), con barra dei comandi
- Testata configurabile dall'interfaccia: nome a due colori, motto, sottotitolo, contatto, prezzo, nota a piè di pagina, logo, titoli delle sezioni

**Immagini**
- Caricamento dal computer, con **rimozione di tutti i metadati** (EXIF, posizione GPS, modello del dispositivo)
- Tre retinature applicate ai pixel — mezzatinta, bitmap a diffusione dell'errore, xilografia a tratteggio — che restano identiche in fotocopia

**Stampa ed esportazione**
- Stampa A4, oppure **a libretto A5** con le pagine già nell'ordine giusto per piegare e pinzare
- Modalità bianco e nero ad alto contrasto, pensata per la fotocopiatrice
- Esportazione in un unico file HTML autonomo, con stile, logo e immagini caricate incorporati

**Archivio**
- Ricerca nel testo degli articoli (lato server), statistiche reali per uscita, nuvola delle parole più frequenti
- **Cronologia delle versioni**: ogni aggiornamento conserva la versione precedente, ripristinabile
- "Nuovo numero da questo": duplica un'uscita incrementando il numero
- Bozza di lavoro salvata sul server, non solo nel browser
- Protezione dai conflitti: se un'uscita viene salvata da un'altra finestra, l'editor chiede prima di sovrascrivere
- Backup completo in un .zip e ripristino non distruttivo; pulizia delle immagini non più usate

**Pubblicazione**
- Le uscite scelte formano un **sito statico da scaricare** (indice, una pagina per uscita, feed RSS, robots.txt), da caricare su qualunque hosting statico o servizio onion
- Pagine blindate: una Content-Security-Policy impedisce a chi legge di contattare qualunque sito terzo; niente script, niente referrer, date senza orario

## Requisiti

- Apache 2.4 con `AllowOverride All` sulla cartella (servono gli `.htaccess` inclusi)
- PHP 8.1+ con le estensioni `sqlite3`, `gd`, `fileinfo`, `mbstring` e `zip`:
  ```bash
  php -m | grep -E 'sqlite3|gd|fileinfo|mbstring|zip'
  ```
- Facoltativo, per l'autenticazione: `mod_authn_file`, `mod_auth_basic`, `mod_headers`

## Installazione

1. Copia tutti i file in una sottocartella del document root, ad esempio `/var/www/html/ezine/`.
2. Rendi scrivibili dall'utente di Apache (es. `www-data`) la cartella principale e `uploads/`:
   ```bash
   chgrp www-data /var/www/html/ezine /var/www/html/ezine/uploads
   chmod 775 /var/www/html/ezine
   chmod 2775 /var/www/html/ezine/uploads
   ```
   Senza questo passaggio i salvataggi falliscono con *"attempt to write a readonly database"*.
3. **Attiva l'autenticazione** — vedi [Sicurezza](#sicurezza). Senza, chiunque conosca l'indirizzo può leggere e modificare tutto.
4. Apri `index.html`. Il database viene creato da solo alla prima richiesta, e si aggiorna da solo quando aggiorni il codice.
5. Apri **⚙️ Impostazioni della testata** e dai un nome alla tua ezine.

## Uso

**Editor** (`index.html`). Scegli una destinazione, scrivi, e l'anteprima ti mostra il risultato tratteggiato mentre digiti; "Aggiungi" lo inserisce. I pulsanti sugli elementi dell'anteprima servono per modificarli, eliminarli o spostarli; gli articoli si spostano anche trascinandoli. Le pagine interne si aggiungono dal pannello **📄 Pagine**.

"Salva come nuova uscita" archivia il numero e collega l'editor all'uscita: da lì in poi "Aggiorna" salva le modifiche senza creare doppioni. "Nuovo numero da questo" riparte dal numero corrente con il numero successivo.

**Archivio** (`archivio.php`). Cerca per titolo o nel testo, apri un'uscita, modificala, duplicala, consulta la sua cronologia, stampala o eliminala. In fondo alla pagina ci sono backup, ripristino e pulizia delle immagini.

Le uscite archiviate conservano la testata con cui sono state salvate: cambiare il nome o il motto in seguito non altera i numeri già usciti.

## Immagini e privacy

Le foto scattate col telefono contengono spesso la posizione esatta di scatto. L'editor le ripulisce due volte: il browser ridisegna l'immagine su un canvas prima di inviarla, e il server la ricodifica comunque con GD, eliminando anche il commento che GD stesso inserisce nei JPEG. I file salvati non contengono alcun metadato.

È ancora possibile indicare l'indirizzo di un'immagine esterna, ma l'editor lo sconsiglia: chi apre il giornale contatta quel sito, che ne vede l'indirizzo IP, e se il file viene rimosso sparisce anche dal numero. Le immagini esterne restano link anche nel file esportato.

Le retinature lavorano sui pixel, non con filtri CSS: per questo l'effetto sopravvive all'esportazione, alla stampa e alla fotocopia. L'originale ripulito viene conservato, così puoi cambiare stile in seguito.

## Stampa e fotocopia

**Stampa A4** produce un foglio per pagina. Se una pagina eccede l'A4, il pannello di stampa lo segnala: in stampa continua su un altro foglio.

**Stampa a libretto A5** riduce ogni pagina in A5 e le dispone due per facciata nell'ordine per la rilegatura a punto metallico (con 8 pagine: [8|1] [2|7] [6|3] [4|5]), aggiungendo pagine bianche fino a un multiplo di quattro. Stampa fronte/retro con rilegatura sul **lato corto**, piega i fogli a metà tutti insieme e pinza al centro. Qui il contenuto che eccede la pagina viene tagliato: controlla prima le segnalazioni.

**Bianco e nero ad alto contrasto** porta tutto a nero su bianco ed elimina i fondini, che in fotocopia diventano grigi sporchi.

## Archivio, versioni e backup

Ogni "Aggiorna" salva la versione che sta per essere sostituita: ne restano fino a 30 per uscita. Anche il ripristino di una versione salva prima quella attuale, quindi si può sempre tornare indietro.

Il backup è un unico .zip con uscite, cronologia, pubblicazioni, impostazioni della testata, bozza e immagini. Il ripristino aggiunge senza cancellare: le uscite già presenti vengono saltate, quindi ripristinare due volte lo stesso file non crea doppioni. Le impostazioni della testata vengono sostituite solo se lo chiedi. Il file caricato viene trattato come non affidabile: si accettano solo nomi di file attesi e ogni immagine viene ricodificata come un normale caricamento.

## Pubblicazione per i lettori

Il server su cui gira l'editor **non serve mai** le pagine pubblicate: le prepara soltanto. Dall'archivio, "🌐 Pubblica" salva un'istantanea autonoma di un'uscita; "Scarica il sito (.zip)" produce il sito completo da caricare altrove. Questo tiene separati il luogo dove lavori e quello dove leggono gli altri: l'indirizzo dell'hosting rivela chi lo gestisce, e il server dell'editor sta spesso su una connessione personale.

Il pacchetto contiene:
- `index.html`, con l'elenco delle uscite pubblicate;
- una pagina per uscita (`anno-i-numero-3.html`), identica all'export e con un link di ritorno all'indice. Ripubblicando, il nome del file resta lo stesso;
- `feed.xml`, se nell'archivio indichi l'indirizzo pubblico del sito;
- `robots.txt` e `LEGGIMI.txt`.

Ogni pagina porta una Content-Security-Policy (`default-src 'none'; img-src data:`) che impedisce al browser di chi legge qualunque richiesta esterna: anche un'immagine esterna rimasta in un articolo non viene caricata, e l'archivio avvisa prima di pubblicare. Niente script, `no-referrer`, e di base `noindex` per i motori di ricerca, disattivabile. Le date del feed sono arrotondate al giorno e i file nello zip hanno una data fissa, in UTC: un orario preciso direbbe quando lavora la redazione e in quale fuso orario vive.

Se modifichi un'uscita dopo averla pubblicata, l'archivio la segna "da aggiornare" finché non la ripubblichi. Ritirarla la toglie dal pacchetto successivo; le copie già caricate online vanno aggiornate a mano.

Queste protezioni riguardano chi legge. Chi ospita i file vede comunque gli indirizzi dei visitatori.

## Sicurezza

Di base l'app **non ha autenticazione**. Attiva HTTP Basic Auth a livello di cartella: protegge pagine, endpoint e immagini senza bisogno di codice.

```bash
htpasswd -cB -C 10 /var/www/html/ezine/.htpasswd <utente>
```

poi rinomina [`.htaccess.example`](.htaccess.example) in `.htaccess` e aggiorna `AuthUserFile` con il percorso assoluto reale. Lo stesso file imposta `X-Content-Type-Options`, `X-Frame-Options` e `Referrer-Policy`, e spiega perché non include una Content-Security-Policy.

`-C 10` alza il costo bcrypt rispetto al predefinito (5). Non salire molto oltre senza motivo: Basic Auth riverifica l'hash a ogni richiesta, e l'archivio ne fa diverse.

Già inclusi e attivi anche senza `.htaccess.example`:
- `api/.htaccess` blocca l'accesso diretto a `api/lib.php`;
- `uploads/.htaccess` lascia servire solo immagini con il nome generato dal server e impedisce di eseguire qualunque file in quella cartella.

## Architettura

| Percorso | Ruolo |
|---|---|
| `index.html` | Editor: pannello di controllo e anteprima A4 |
| `archivio.php` | Archivio: elenco, ricerca, cronologia, backup |
| `assets/render.js` | Unico motore di impaginazione, usato da editor, archivio, esportazione e stampa |
| `assets/newspaper.css` | Aspetto del giornale, stampa normale, libretto, bianco e nero |
| `assets/images.js` | Preparazione delle immagini nel browser e retinature |
| `api/lib.php` | Funzioni condivise: database, migrazioni automatiche, testo, statistiche, immagini |
| `api/*.php` | Endpoint JSON: uscite, ricerca, parole chiave, revisioni, bozza, impostazioni, immagini, backup, pubblicazione |
| `uploads/` | Immagini caricate |

Tutto il contenuto di un numero è serializzato come JSON in un'unica colonna `content`, con le immagini come percorsi in `uploads/`. Lo schema del database è versionato (`PRAGMA user_version`) e migrato automaticamente.

## Licenza

[GNU GPLv3](LICENSE) — libero di usarlo, modificarlo e ridistribuirlo, a patto che le opere derivate restino sotto la stessa licenza.
