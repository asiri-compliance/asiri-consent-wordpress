#!/usr/bin/env bash
# Runs the WordPress PHPCS standard (WPCS 3) and the PHP 7.4+ compatibility check over the plugin, then the plugin's own
# tests. Needs git, curl and Docker (for PHP). Standards are cached in ~/.cache/asiri-lint. Run it on the dev box:
#   remote-run bash bin/lint.sh
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
cache="${HOME}/.cache/asiri-lint"
mkdir -p "$cache"
[ -f "$cache/phpcs.phar" ] || curl -fsSL -o "$cache/phpcs.phar" https://github.com/PHPCSStandards/PHP_CodeSniffer/releases/latest/download/phpcs.phar
for r in WordPress/WordPress-Coding-Standards PHPCSStandards/PHPCSUtils PHPCSStandards/PHPCSExtra PHPCompatibility/PHPCompatibility PHPCompatibility/PHPCompatibilityWP PHPCompatibility/PHPCompatibilityParagonie; do
  d="$cache/$(basename "$r")"
  [ -d "$d" ] || git clone -q --depth 1 "https://github.com/$r.git" "$d"
done
paths="/c/WordPress-Coding-Standards,/c/PHPCSUtils,/c/PHPCSExtra,/c/PHPCompatibility,/c/PHPCompatibilityWP,/c/PHPCompatibilityParagonie"
docker run --rm -v "$root":/w -v "$cache":/c -w /w php:8.2-cli sh -c "
  set -e
  php /c/phpcs.phar --config-set installed_paths $paths >/dev/null
  php /c/phpcs.phar -i
  echo '--- WordPress standard'
  php /c/phpcs.phar --standard=WordPress-Extra --extensions=php --report=summary --report-width=100 asiri-consent && echo 'WordPress: clean'
  echo '--- PHP 7.4 and up compatibility'
  php /c/phpcs.phar --standard=PHPCompatibilityWP --runtime-set testVersion 7.4-8.5 --extensions=php --report=summary asiri-consent && echo 'compat: clean'
  echo '--- own tests'
  php tests/run.php | tail -3
  php tests/nokey.php | tail -2
"
