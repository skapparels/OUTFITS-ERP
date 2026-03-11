#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist/godaddy_starter"
ZIP_PATH="$ROOT_DIR/dist/godaddy_starter_package.zip"

rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

rsync -av --exclude='.git' --exclude='node_modules' --exclude='vendor' \
  --exclude='dist' --exclude='.env' "$ROOT_DIR/backend" "$DIST_DIR/"

if [[ -d "$ROOT_DIR/frontend/dist" ]]; then
  rsync -av "$ROOT_DIR/frontend/dist/" "$DIST_DIR/frontend-dist/"
else
  echo "frontend/dist not found. Run npm run build in frontend first." >&2
  exit 1
fi

mkdir -p "$ROOT_DIR/dist"
cd "$DIST_DIR/.."
zip -r "$(basename "$ZIP_PATH")" "$(basename "$DIST_DIR")" >/dev/null
mv -f "$(basename "$ZIP_PATH")" "$ZIP_PATH"

echo "Created package: $ZIP_PATH"
