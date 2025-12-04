#!/usr/bin/env bash
set -euo pipefail

# Package the ptp-training-pro plugin directory into ptp-training-pro.zip at repo root.
# Excludes common junk files to keep the archive clean.
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

ZIP_NAME="ptp-training-pro.zip"
PLUGIN_DIR="ptp-training-pro"

if [ ! -d "$PLUGIN_DIR" ]; then
  echo "Plugin directory '$PLUGIN_DIR' not found" >&2
  exit 1
fi

# Remove any existing archive to avoid stale contents.
rm -f "$ZIP_NAME"

zip -r "$ZIP_NAME" "$PLUGIN_DIR" \
  -x "*.DS_Store" "__MACOSX" "*/.git*" "*/node_modules/*" "*/vendor/bin/*"

echo "Created $ZIP_NAME"
