#!/bin/bash
# =============================================================================
# Esegue tutti i test automatici dell'editor.
#
#   bash tests/run.sh
#
# Lavora su una copia temporanea dell'app, con un database vuoto e un server PHP
# di prova su una porta libera: il sito vero, il suo database e le immagini
# caricate non vengono mai toccati. A fine esecuzione (anche se un test fallisce
# o lo interrompi) la copia viene eliminata.
#
# Contiene:
#   - controllo di sintassi di tutto il PHP e il JavaScript
#   - tests/api_test.php     il backend via HTTP: uscite, cronologia, conflitti,
#                            ricerca, immagini e metadati, pubblicazione, backup
#   - tests/render_test.mjs  il motore di impaginazione (richiede Node; se manca
#                            viene saltato con un avviso)
#
# Le regole di Apache (.htaccess, autenticazione, header) il server di prova non
# le applica: per quelle c'è tests/check_apache.sh, da lanciare sul sito vero.
#
# Requisiti: php (con sqlite3, gd, fileinfo, mbstring, zip, exif), node facoltativo.
# Esce con codice 0 se tutto è superato, 1 altrimenti.
# =============================================================================
set -u

QUI="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SORGENTE="$(dirname "$QUI")"
COPIA="$(mktemp -d -t ezine-test-XXXXXX)"
LOG="$COPIA.server.log"
SERVER_PID=""

pulizia() {
    [ -n "$SERVER_PID" ] && kill "$SERVER_PID" 2>/dev/null && wait "$SERVER_PID" 2>/dev/null
    rm -rf "$COPIA" "$LOG"
}
trap pulizia EXIT INT TERM

ESITO=0
fallito() { ESITO=1; }

echo "Copia di prova in $COPIA"
# Solo il codice: mai il database, le immagini caricate o le credenziali.
mkdir -p "$COPIA/uploads"
cp "$SORGENTE/index.html" "$SORGENTE/archivio.php" "$COPIA/"
cp -r "$SORGENTE/assets" "$SORGENTE/api" "$COPIA/"
cp "$SORGENTE/uploads/.htaccess" "$COPIA/uploads/"

echo
echo "── Sintassi"
for f in "$COPIA"/api/*.php "$COPIA/archivio.php"; do
    if out=$(php -l "$f" 2>&1); then echo "  ✓ ${f#$COPIA/}"; else echo "  ✗ ${f#$COPIA/}: $out"; fallito; fi
done
if command -v node >/dev/null 2>&1; then
    for f in "$COPIA"/assets/*.js; do
        if out=$(node --check "$f" 2>&1); then echo "  ✓ ${f#$COPIA/}"; else echo "  ✗ ${f#$COPIA/}: $out"; fallito; fi
    done
fi

PORTA=$(php -r '$s = stream_socket_server("tcp://127.0.0.1:0"); echo explode(":", stream_socket_get_name($s, false))[1];')
# Il server di prova gira apposta in un fuso lontano da UTC (+5:30): un errore
# legato all'ora locale, come una data che rivela il fuso orario della
# redazione, su una macchina impostata in UTC passerebbe inosservato.
TZ=Asia/Kolkata php -S "127.0.0.1:$PORTA" -t "$COPIA" > "$LOG" 2>&1 &
SERVER_PID=$!
for _ in $(seq 1 50); do
    php -r "exit(@fsockopen('127.0.0.1', $PORTA) ? 0 : 1);" 2>/dev/null && break
    sleep 0.1
done
if ! php -r "exit(@fsockopen('127.0.0.1', $PORTA) ? 0 : 1);" 2>/dev/null; then
    echo "  ✗ il server di prova non si è avviato:"; cat "$LOG"; exit 1
fi

php "$QUI/api_test.php" "http://127.0.0.1:$PORTA" "$COPIA" || fallito

if command -v node >/dev/null 2>&1; then
    node "$QUI/render_test.mjs" || fallito
else
    echo
    echo "── Impaginazione: saltata, Node non è installato"
fi

# Errori PHP finiti nel log del server (avvisi, eccezioni non gestite).
if grep -qiE "PHP (Fatal|Warning|Notice|Deprecated)" "$LOG"; then
    echo
    echo "── Errori registrati dal server di prova"
    grep -iE "PHP (Fatal|Warning|Notice|Deprecated)" "$LOG" | sed 's/^/  /'
    fallito
fi

echo
[ "$ESITO" -eq 0 ] && echo "ESITO: tutti i test superati" || echo "ESITO: ci sono test falliti"
exit "$ESITO"
