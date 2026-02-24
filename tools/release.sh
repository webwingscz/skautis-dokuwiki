#!/usr/bin/env bash
set -euo pipefail

PLUGIN_NAME="authskautis"
VERSION="${1:-$(git describe --tags --always)}"

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
BUILD_DIR="$(mktemp -d)"

command -v composer >/dev/null 2>&1 || { echo "composer not found"; exit 1; }
command -v zip >/dev/null 2>&1 || { echo "zip not found"; exit 1; }

mkdir -p "$DIST_DIR"

rsync -a --delete \
  --exclude ".git/" \
  --exclude ".github/" \
  --exclude "dist/" \
  --exclude "docker/" \
  --exclude "tools/" \
  --exclude "tests/" \
  --exclude "node_modules/" \
  --exclude ".idea/" \
  --exclude "vendor/" \
  "$ROOT_DIR/" "$BUILD_DIR/$PLUGIN_NAME/"

# čistá instalace vendor v build složce
(
  cd "$BUILD_DIR/$PLUGIN_NAME"
  composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress
)

(
  cd "$BUILD_DIR"
  zip -qr "$DIST_DIR/${PLUGIN_NAME}-${VERSION}.zip" "$PLUGIN_NAME" \
    -x "*/.DS_Store" -x "*/Thumbs.db"
)

echo "Created: $DIST_DIR/${PLUGIN_NAME}-${VERSION}.zip"