#!/bin/bash
# =============================================================================
# Controlla, in sola lettura, le protezioni di un'installazione vera su Apache:
# autenticazione, file che non devono essere serviti, header di sicurezza.
# Sono le regole dei .htaccess, che il server di prova di run.sh non applica.
#
#   EZINE_AUTH='utente:password' bash tests/check_apache.sh https://host/ezine
#
# Le credenziali si passano nella variabile EZINE_AUTH e non vengono mai
# stampate. Lo script non scrive nulla sul server.
# =============================================================================
set -u

URL="${1:-}"
URL="${URL%/}"
if [ -z "$URL" ] || [ -z "${EZINE_AUTH:-}" ]; then
    echo "uso: EZINE_AUTH='utente:password' bash tests/check_apache.sh https://host/ezine"
    exit 2
fi

PASSATI=0
FALLITI=0

codice() {   # codice HTTP di una richiesta: credenziali vere (si), sbagliate (errate) o nessuna (no)
    local percorso="$1" auth="$2"
    # le credenziali passano da un file di configurazione di curl, non dalla
    # riga di comando: così non compaiono nell'elenco dei processi
    case "$auth" in
        si)     curl -s -o /dev/null -w '%{http_code}' --max-time 20 -K <(printf 'user = "%s"\n' "$EZINE_AUTH") "$URL/$percorso" ;;
        errate) curl -s -o /dev/null -w '%{http_code}' --max-time 20 -K <(printf 'user = "%s"\n' "${EZINE_AUTH%%:*}:password-sbagliata-di-prova") "$URL/$percorso" ;;
        *)      curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$URL/$percorso" ;;
    esac
}

atteso() {   # descrizione, percorso, con credenziali (si/no), codice atteso
    local ottenuto
    ottenuto=$(codice "$2" "$3")
    if [ "$ottenuto" = "$4" ]; then
        PASSATI=$((PASSATI + 1)); echo "  ✓ $1"
    else
        FALLITI=$((FALLITI + 1)); echo "  ✗ $1"; echo "      → atteso $4, ottenuto $ottenuto"
    fi
}

echo "Controllo di $URL"
echo
echo "── Autenticazione"
atteso "l'editor chiede le credenziali"            ""                       no 401
atteso "gli endpoint chiedono le credenziali"      "api/list_issues.php"    no 401
atteso "gli script chiedono le credenziali"        "assets/render.js"       no 401
atteso "credenziali errate respinte"               ""                       errate 401
atteso "con le credenziali l'editor si apre"       ""                       si 200
atteso "con le credenziali l'archivio si apre"     "archivio.php"           si 200
atteso "con le credenziali gli endpoint rispondono" "api/settings.php"      si 200

echo
echo "── File che non devono mai essere serviti (anche con le credenziali)"
atteso "database"                                  "ezine.db"               si 403
atteso "hash delle password"                       ".htpasswd"              si 403
atteso "configurazione di Apache"                  ".htaccess"              si 403
atteso "libreria interna degli endpoint"           "api/lib.php"            si 403
atteso "regole della cartella immagini"            "uploads/.htaccess"      si 403
atteso "un file non-immagine in uploads/"          "uploads/qualcosa.php"   si 403
atteso "i test"                                    "tests/run.sh"           si 403

echo
echo "── Header di sicurezza"
INTESTAZIONI=$(curl -s -D - -o /dev/null --max-time 20 -K <(printf 'user = "%s"\n' "$EZINE_AUTH") "$URL/")
for h in "X-Content-Type-Options: nosniff" "X-Frame-Options: SAMEORIGIN" "Referrer-Policy: same-origin"; do
    if printf '%s' "$INTESTAZIONI" | tr -d '\r' | grep -qi "^$h"; then
        PASSATI=$((PASSATI + 1)); echo "  ✓ $h"
    else
        FALLITI=$((FALLITI + 1)); echo "  ✗ manca: $h"
    fi
done

echo
echo "────────────────────────────────────────────────────────────"
if [ "$FALLITI" -gt 0 ]; then
    echo "APACHE: $PASSATI superati, $FALLITI falliti"
    exit 1
fi
echo "APACHE: tutti i $PASSATI controlli superati"
