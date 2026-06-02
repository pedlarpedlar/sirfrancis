#!/bin/bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-$HOME/repositories/candybird}"
LIVE_DIR="${LIVE_DIR:-$HOME/public_html}"

cd "$REPO_DIR"

mkdir -p "$LIVE_DIR"

git --work-tree="$LIVE_DIR" --git-dir="$REPO_DIR/.git" checkout -f main

STALE_LIVE_PATHS=(
  "candybird2025"
  "candybird-libs"
  "TCPDF-main"
  "expomedia.co.za"
  "syncitt.co.za"
)

for stale_path in "${STALE_LIVE_PATHS[@]}"; do
  target="$LIVE_DIR/$stale_path"
  case "$target" in
    "$LIVE_DIR"/*)
      if [ -e "$target" ]; then
        rm -rf -- "$target"
        echo "Removed stale live folder: $target"
      fi
      ;;
    *)
      echo "Skipped unsafe cleanup path: $target" >&2
      ;;
  esac
done

rm -f "$LIVE_DIR/deploy_candybird.sh"

echo "CandyBird deployed from $REPO_DIR to $LIVE_DIR"
