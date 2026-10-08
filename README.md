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
- [Test](#test)
- [Architettura](#architettura)
- [Licenza](#licenza)

## Caratteristiche

**Composizione**
- Anteprima a dimensione A4 reale: il testo va a capo esattamente come sulla carta
- **Pagine che stanno da sole nell'A4**: ogni pagina che sfora viene ridotta quanto basta (fino al 75%), e se non basta un pulsante sposta gli articoli in eccesso nella pagina successiva
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
- Pagine leggere: le immagini diventano file separati (una sola copia per quelle ripetute, come il logo), le foto vengono ridotte per lo schermo e caricate solo quando servono
- Indirizzi email protetti dai programmi che li raccolgono per lo spam, senza script: chi legge li vede e li copia normalmente

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

**Adattamento all'A4.** Di base ogni pagina che non entra nel foglio viene ridotta in proporzione, quanto basta e non oltre il 75%, sotto il quale il testo stampato scende sotto i 7 punti. Il testo si riimpagina su righe più lunghe invece di rimpicciolire soltanto, quindi spesso basta una riduzione minore di quanto ci si aspetterebbe. La scala viene calcolata nell'editor e salvata con l'uscita: stampa, libretto, export e pagine pubblicate la applicano identica, senza bisogno di script. L'adattamento punta al 99% del foglio, così font leggermente diversi su un altro computer non fanno traboccare qualche riga su un secondo foglio. Si disattiva per singola uscita dal pannello di stampa.

Se una pagina non entra nemmeno al 75%, il pannello lo segnala con un pulsante **"Sposta l'eccedenza"**: gli ultimi articoli passano, interi e nell'ordine originale, all'inizio della pagina successiva (creata se non c'è), finché la pagina sta nel foglio. Le parti fisse della prima pagina (testata, articolo a tutta larghezza, riquadro, colonne brevi) non si spostano: se l'eccedenza è lì, il pannello invita ad accorciare quei testi.

**Stampa A4** produce un foglio per pagina.

**Stampa a libretto A5** riduce ogni pagina in A5 e le dispone due per facciata nell'ordine per la rilegatura a punto metallico (con 8 pagine: [8|1] [2|7] [6|3] [4|5]), aggiungendo pagine bianche fino a un multiplo di quattro. Stampa fronte/retro con rilegatura sul **lato corto**, piega i fogli a metà tutti insieme e pinza al centro. Qui il contenuto che eccede la pagina verrebbe tagliato: con l'adattamento attivo e le segnalazioni risolte non succede.

**Bianco e nero ad alto contrasto** porta tutto a nero su bianco ed elimina i fondini, che in fotocopia diventano grigi sporchi.

## Archivio, versioni e backup

Ogni "Aggiorna" salva la versione che sta per essere sostituita: ne restano fino a 30 per uscita. Anche il ripristino di una versione salva prima quella attuale, quindi si può sempre tornare indietro.

Il backup è un unico .zip con uscite, cronologia, pubblicazioni, impostazioni della testata, bozza e immagini. Il ripristino aggiunge senza cancellare: le uscite già presenti vengono saltate, quindi ripristinare due volte lo stesso file non crea doppioni. Le impostazioni della testata vengono sostituite solo se lo chiedi. Il file caricato viene trattato come non affidabile: si accettano solo nomi di file attesi e ogni immagine viene ricodificata come un normale caricamento.

## Pubblicazione per i lettori

Il server su cui gira l'editor **non serve mai** le pagine pubblicate: le prepara soltanto. Dall'archivio, "🌐 Pubblica" salva un'istantanea autonoma di un'uscita; "Scarica il sito (.zip)" produce il sito completo da caricare altrove. Questo tiene separati il luogo dove lavori e quello dove leggono gli altri: l'indirizzo dell'hosting rivela chi lo gestisce, e il server dell'editor sta spesso su una connessione personale.

Il pacchetto contiene:
- `index.html`, con l'elenco delle uscite pubblicate;
- una pagina per uscita (`anno-i-numero-3.html`), identica all'export e con un link di ritorno all'indice. Ripubblicando, il nome del file resta lo stesso;
- la cartella `img/` con le immagini;
- `feed.xml`, se nell'archivio indichi l'indirizzo pubblico del sito;
- `robots.txt` e `LEGGIMI.txt`.

Le istantanee salvate hanno le immagini incorporate; nel pacchetto vengono estratte in `img/`, con un nome derivato dal contenuto, così un'immagine presente in più uscite si scarica una volta sola. Una pagina passa così da centinaia di KB a una decina, e il testo compare subito anche su una connessione lenta come quella di un servizio onion. Le fotografie oltre i 1600 px vengono ridotte per lo schermo e ricompresse, solo se il file risulta davvero più leggero. I PNG retinati restano invece identici, pixel per pixel, perché ricampionarli rovinerebbe il retino. Le immagini degli articoli dichiarano le proprie dimensioni, così la pagina non salta mentre arrivano, e si caricano solo quando entrano in vista. Quelle minuscole restano incorporate, perché un file in più costerebbe più di loro.

Ogni pagina porta una Content-Security-Policy (`default-src 'none'; img-src 'self' data:`) che ammette solo le immagini del sito stesso e impedisce al browser di chi legge qualunque richiesta esterna. Un'immagine esterna rimasta in un articolo non viene caricata, e l'archivio avvisa prima di pubblicare. Le immagini del sito si vedono anche aprendo le pagine direttamente dal disco: verificato con Chromium e Firefox. Niente script, `no-referrer`, e di base `noindex` per i motori di ricerca, disattivabile. Le date del feed sono arrotondate al giorno e i file nello zip hanno una data fissa, in UTC: un orario preciso direbbe quando lavora la redazione e in quale fuso orario vive.

**Indirizzi email.** Nell'export e nelle pagine pubblicate ogni indirizzo email presente nel testo, compreso il contatto della testata, viene spezzato con frammenti nascosti (`hidden`), e `@` e `.` vengono scritti come entità HTML. Chi legge vede e copia l'indirizzo esatto: i frammenti nascosti non finiscono negli appunti né nella lettura vocale, verificato con Firefox e Chromium. Nel sorgente invece l'indirizzo intero non esiste, quindi i programmi che raccolgono email per lo spam non lo trovano; e chi toglie i tag tenendo il testo ottiene un indirizzo non valido. Il frammento è `(togli)` e non il classico `NOSPAM`, che i raccoglitori sanno eliminare. I browser testuali come lynx e w3m ignorano gli elementi nascosti e lo mostrano (`nome(togli)@(togli)dominio.it`), ma resta chiaro cosa togliere. Due limiti: un raccoglitore che esegue davvero la pagina come un browser vede l'indirizzo come chiunque altro; e i link `mailto:` scritti negli articoli conservano l'indirizzo nel collegamento, perché altrimenti non funzionerebbero. Editor, archivio e stampa mostrano gli indirizzi normalmente.

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

## Test

```bash
bash tests/run.sh
```

Esegue tutti i test su una **copia temporanea** dell'app, con un database vuoto e un server PHP di prova: il sito, il suo database e le immagini caricate non vengono mai toccati, e la copia viene eliminata alla fine. Esce con codice 0 se è tutto superato.

- `tests/api_test.php` verifica il backend via HTTP: uscite e statistiche, cronologia e conflitti, ricerca, bozza e impostazioni, rimozione dei metadati dalle immagini (con un JPEG che contiene davvero coordinate GPS), pulizia, pubblicazione e pacchetto del sito, backup e ripristino, compresi backup ostili e zip-slip.
- `tests/render_test.mjs` verifica il motore di impaginazione con Node: Markdown, normalizzazione, testata, pagine, immagini, adattamento all'A4, protezione degli indirizzi email, ordine del libretto, neutralizzazione dell'HTML iniettato. Se Node manca viene saltato.

Il server di prova gira apposta in un fuso orario lontano da UTC, così un errore legato all'ora locale non passa inosservato. Servono le estensioni PHP dell'app più `exif`, usata per controllare i metadati.

Le regole dei `.htaccess` il server di prova non le applica. Per verificarle su un'installazione vera, in sola lettura:

```bash
EZINE_AUTH='utente:password' bash tests/check_apache.sh https://tuo-sito/ezine
```

Controlla autenticazione, file che non devono mai essere serviti (database, `.htpasswd`, `api/lib.php`, i test) e header di sicurezza. Le credenziali passano da una variabile e non vengono stampate né mostrate nell'elenco dei processi. La cartella `tests/` è protetta dal suo `.htaccess` e non è raggiungibile via web.

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
| `tests/` | Test automatici (non raggiungibili via web) |

Tutto il contenuto di un numero è serializzato come JSON in un'unica colonna `content`, con le immagini come percorsi in `uploads/`. Lo schema del database è versionato (`PRAGMA user_version`) e migrato automaticamente.

## Licenza

[GNU GPLv3](LICENSE) — libero di usarlo, modificarlo e ridistribuirlo, a patto che le opere derivate restino sotto la stessa licenza.
