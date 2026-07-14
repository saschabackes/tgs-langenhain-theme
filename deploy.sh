#!/bin/bash
# TGS Langenhain Theme — Deploy to Staging

# Load .env if present
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -f "$SCRIPT_DIR/.env" ]; then
    source "$SCRIPT_DIR/.env"
fi

FTP_HOST="${FTP_HOST:-ftp.tgs-langenhain.de}"
FTP_USER="${FTP_USER:-389410-claudeai}"
FTP_PASS="${FTP_PASS:-}"
REMOTE_PATH="${FTP_REMOTE_PATH:-/wordpress/wp-content/themes/tgs-langenhain-theme}"

if [ -z "$FTP_PASS" ]; then
    echo "❌ FTP_PASS nicht gesetzt. Bitte .env Datei anlegen."
    exit 1
fi

echo "🚀 Deploying TGS Theme to staging..."
echo "   Local:  $SCRIPT_DIR"
echo "   Remote: $FTP_HOST:$REMOTE_PATH"
echo ""

lftp -c "
set ftp:ssl-allow no;
set mirror:use-pget-n 5;
open -u $FTP_USER,$FTP_PASS $FTP_HOST;
mirror --reverse --verbose --exclude .git/ --exclude .gitignore --exclude deploy.sh --exclude .DS_Store --exclude node_modules/ --exclude .env \"$SCRIPT_DIR\" \"$REMOTE_PATH\";
echo '✅ Deploy complete!';
"

echo ""
echo "👉 Seite prüfen: http://staging.tgs-langenhain.de"