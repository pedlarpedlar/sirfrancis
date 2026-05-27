#!/bin/bash
set -euo pipefail

REPO_DIR="${REPO_DIR:-$HOME/repositories/candybird}"
LIVE_DIR="${LIVE_DIR:-$HOME/public_html}"

cd "$REPO_DIR"

mkdir -p "$LIVE_DIR"

git --work-tree="$LIVE_DIR" --git-dir="$REPO_DIR/.git" checkout -f main

rm -f "$LIVE_DIR/deploy_candybird.sh"

echo "CandyBird deployed from $REPO_DIR to $LIVE_DIR"
