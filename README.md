# Asiri Consent for WordPress

Adds the Asiri consent banner to your site and tells the [WP Consent API](https://wordpress.org/plugins/wp-consent-api/) what the visitor chose, so other plugins can listen.

This repository is the plugin as WordPress.org ships it: the plugin files are at the root (`asiri-consent.php`, `includes/`, `blocks/`, `assets/`, `readme.txt`, `uninstall.php`). It is plain PHP and one small unminified JavaScript file, with no build step.

The banner script itself is the hosted Asiri service, pinned by version with a Subresource Integrity hash in `includes/release.php`. See `readme.txt`, section "External services", for what the plugin sends and when.

`dev/` holds the unit tests and the build and lint scripts. They come from the Asiri monorepo layout, where the plugin sits in an `asiri-consent/` folder, so run them from there.

Licence: GPL-2.0-or-later. Company: Asiri Compliance Ltd.
