#!/bin/bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-$HOME/repositories/candybird}"
LIVE_DIR="${LIVE_DIR:-$HOME/public_html}"

cd "$REPO_DIR"

rsync -av --delete \
  --exclude='.git/' \
  --exclude='.gitignore' \
  --exclude='.cpanel.yml' \
  --exclude='deploy_candybird.sh' \
  --exclude='dbh.inc.php' \
  --exclude='admin-cb/dbh.inc.php' \
  --exclude='configs/' \
  --exclude='sheet_cache/' \
  --exclude='backups/' \
  --exclude='uploads/' \
  --exclude='error_log' \
  --exclude='*.log' \
  "$REPO_DIR/" "$LIVE_DIR/"

echo "CandyBird deployed from $REPO_DIR to $LIVE_DIR"
