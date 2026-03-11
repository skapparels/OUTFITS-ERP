#!/usr/bin/env bash
set -euo pipefail

# Deploy OUTFLITS ERP to GoDaddy shared hosting via FTP/SFTP upload.
# Required environment variables:
#   GODADDY_HOST
#   GODADDY_USER
#   GODADDY_PASS
# Optional:
#   GODADDY_PROTOCOL (default: ftp)
#   GODADDY_PORT (default: 21)
#   GODADDY_REMOTE_BASE (default: /)
#   GODADDY_API_DOCROOT (default: public_html/api)
#   GODADDY_APP_DOCROOT (default: public_html)

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist/godaddy_starter"
ZIP_PATH="$ROOT_DIR/dist/godaddy_starter_package.zip"

: "${GODADDY_HOST:?GODADDY_HOST is required}"
: "${GODADDY_USER:?GODADDY_USER is required}"
: "${GODADDY_PASS:?GODADDY_PASS is required}"

GODADDY_PROTOCOL="${GODADDY_PROTOCOL:-ftp}"
GODADDY_PORT="${GODADDY_PORT:-21}"
GODADDY_REMOTE_BASE="${GODADDY_REMOTE_BASE:-/}"
GODADDY_API_DOCROOT="${GODADDY_API_DOCROOT:-public_html/api}"
GODADDY_APP_DOCROOT="${GODADDY_APP_DOCROOT:-public_html}"

if ! command -v lftp >/dev/null 2>&1; then
  echo "lftp is required. Install it first (apt install lftp / brew install lftp)." >&2
  exit 1
fi

if [[ ! -d "$ROOT_DIR/frontend/dist" ]]; then
  echo "frontend/dist not found. Run: cd frontend && npm ci && npm run build" >&2
  exit 1
fi

bash "$ROOT_DIR/scripts/package_godaddy_starter.sh"

echo "Uploading package to GoDaddy..."

lftp -u "$GODADDY_USER","$GODADDY_PASS" -p "$GODADDY_PORT" "$GODADDY_PROTOCOL://$GODADDY_HOST" <<LFTP_CMDS
set ssl:verify-certificate no
set ftp:passive-mode true
mkdir -p $GODADDY_REMOTE_BASE/outflits-erp-release
cd $GODADDY_REMOTE_BASE/outflits-erp-release
put $ZIP_PATH -o godaddy_starter_package.zip
LFTP_CMDS

echo "Upload completed: $GODADDY_REMOTE_BASE/outflits-erp-release/godaddy_starter_package.zip"
echo "Next in cPanel File Manager: extract zip, set API docroot, update .env, run artisan commands, set cron."
echo "See docs/DEPLOY_NOW_GODADDY.md for exact next steps."
