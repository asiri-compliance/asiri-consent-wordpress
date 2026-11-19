#!/usr/bin/env bash
# Runs the official Plugin Check (the WordPress.org review checks) against the plugin on a real WordPress (latest) with
# the WP Consent API installed, in throwaway containers (WordPress, MariaDB, WP-CLI). Needs Docker. Run it on the dev box:
#   remote-run bash bin/plugin-check.sh
# Containers and the network are removed on exit. Output ends with the Plugin Check table and the WordPress version.
set -euo pipefail
root="$(cd "$(dirname "$0")/.." && pwd)"
id="asiri-pc-$$"
cleanup() { docker rm -f "$id-db" "$id-wp" >/dev/null 2>&1 || true; docker network rm "$id" >/dev/null 2>&1 || true; docker volume rm "$id-core" >/dev/null 2>&1 || true; }
trap cleanup EXIT
docker network create "$id" >/dev/null
docker volume create "$id-core" >/dev/null
docker run -d --name "$id-db" --network "$id" -e MARIADB_ROOT_PASSWORD=pc -e MARIADB_DATABASE=wp mariadb:11 >/dev/null
docker run -d --name "$id-wp" --network "$id" -v "$id-core":/var/www/html \
  -e WORDPRESS_DB_HOST="$id-db" -e WORDPRESS_DB_USER=root -e WORDPRESS_DB_PASSWORD=pc -e WORDPRESS_DB_NAME=wp \
  wordpress:latest >/dev/null
# The plugin is copied in, not mounted, so the container's file owner stays the web user's.
cli() { docker run --rm --network "$id" --volumes-from "$id-wp" --user 33:33 -e HOME=/tmp -e WORDPRESS_DB_HOST="$id-db" -e WORDPRESS_DB_USER=root -e WORDPRESS_DB_PASSWORD=pc -e WORDPRESS_DB_NAME=wp -w /var/www/html wordpress:cli "$@"; }
for _ in $(seq 1 60); do docker exec "$id-wp" test -f /var/www/html/wp-config.php && break; sleep 2; done
for _ in $(seq 1 60); do docker exec "$id-db" mariadb -uroot -ppc -e 'select 1' >/dev/null 2>&1 && break; sleep 2; done
docker cp "$root/asiri-consent" "$id-wp":/var/www/html/wp-content/plugins/asiri-consent
docker exec "$id-wp" chown -R 33:33 /var/www/html/wp-content
cli wp core install --url=http://localhost --title=pc --admin_user=admin --admin_password=admin --admin_email=a@example.com --skip-email >/dev/null
echo "WordPress $(cli wp core version)  PHP $(docker exec "$id-wp" php -r 'echo PHP_VERSION;')"
cli wp plugin install wp-consent-api plugin-check --activate >/dev/null
cli wp plugin activate asiri-consent
cli wp plugin check asiri-consent --format=table || true
echo "--- plugin check done"
