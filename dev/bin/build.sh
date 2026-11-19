#!/usr/bin/env bash
# Builds asiri-consent.zip: regenerates includes/release.php from the edge build, then zips the plugin without
# its tests. No composer, no PHP-Scoper: the plugin ships its own small client, so nothing is vendored.
# Usage: bin/build.sh [edge-dir]
set -euo pipefail
here="$(cd "$(dirname "$0")" && pwd)"
root="$(cd "$here/.." && pwd)"
out="$root/dist"
stage="$(mktemp -d "${TMPDIR:-/tmp}/asiri-consent.XXXXXX")"
trap 'rm -rf "$stage"' EXIT

node "$here/gen-release.js" "$@"

# A store zip must not carry a placeholder. ASIRI_ALLOW_PLACEHOLDERS=1 builds a dev zip anyway.
if grep -rnE 'SOURCE_REPOSITORY_URL|REPLACE_WITH' "$root/asiri-consent" && [ "${ASIRI_ALLOW_PLACEHOLDERS:-0}" != 1 ]; then
  echo "build stopped: replace the placeholders above first (or set ASIRI_ALLOW_PLACEHOLDERS=1 for a dev zip)" >&2
  exit 1
fi

mkdir -p "$out" "$stage/asiri-consent"
rsync -a --exclude 'tests' --exclude '.DS_Store' "$root/asiri-consent/" "$stage/asiri-consent/"
rm -f "$out/asiri-consent.zip"
# -X drops extra file attributes and a fixed time keeps the bytes the same for the same files.
find "$stage/asiri-consent" -exec touch -t 202601010000 {} +
(cd "$stage" && find asiri-consent -type f | LC_ALL=C sort | zip -X -q "$out/asiri-consent.zip" -@)

if command -v sha256sum >/dev/null 2>&1; then
  sum="$(sha256sum "$out/asiri-consent.zip" | cut -d' ' -f1)"
else
  sum="$(shasum -a 256 "$out/asiri-consent.zip" | cut -d' ' -f1)"
fi
echo "zip: $out/asiri-consent.zip"
echo "sha256: $sum"
