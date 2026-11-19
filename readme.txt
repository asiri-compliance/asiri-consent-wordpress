=== Asiri Consent ===
Contributors: asiricompliance
Tags: consent, cookies, privacy, wp consent api, banner
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: wp-consent-api
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds the Asiri consent banner to your site and tells the WP Consent API what the visitor chose, so other plugins can listen.

== Description ==

Asiri Consent adds your Asiri banner to the head of every page and reports each visitor's choice to the WP Consent API, so plugins that listen to it wait for a yes before they run.

* The banner script is loaded from a pinned version with a Subresource Integrity hash. The plugin never fetches or installs code.
* Google Consent Mode defaults are set before any Google tag.
* A block ("Asiri: cookie settings link") and the shortcode `[asiri_preferences]` print a button that opens the visitor's choices.
* Optional consent boxes for WPForms, Gravity Forms, Contact Form 7 and WooCommerce checkout. Every box starts unticked. The answer, including an unticked box as a no, is recorded from your server.
* There is no "accept on scroll" setting and no "implied consent" setting.

The plugin sets no cookies of its own.

== Installation ==

1. Install and activate the plugin. WordPress asks you to install the WP Consent API plugin it depends on; activate that first.
2. Open Settings, Asiri Consent, and paste your publishable key (it starts with pk_live_ or pk_test_) and your source id.
3. To record form answers, add the secret key to wp-config.php: `define( 'ASIRI_SECRET_KEY', 'sk_live_...' );`. It is never saved in the database or shown on any page.

== External services ==

This plugin connects to two services run by Asiri Compliance Ltd. Terms of use: https://asiri.ng/legal/terms. Privacy notice: https://asiri.ng/legal/privacy.

= edge.asiri.ng (the consent script) =

Every visitor's browser loads the banner script from https://edge.asiri.ng. The script reads your signed banner configuration and records the visitor's choice, as a receipt, with api.asiri.ng. Your visitor's browser sends the usual request details (IP address, user agent) to load the file. Your publishable key and source id are in the page so the script knows which banner to show.

= api.asiri.ng (the consent record) =

From the visitor's browser, the script sends the choice the visitor made and when, with your publishable key, each time a choice is made or changed.

From your server, only if you switch on form boxes and add the secret key to wp-config.php: when a form with consent boxes is submitted, the plugin sends the notice reference and version, one yes or no for each purpose, the time, and the form entry id or order id. It does not send the name, email address or any other form field. The secret key is sent only to https://api.asiri.ng, in one request header.

== Source code ==

The plugin is plain PHP and one small JavaScript file (`assets/bridge.js`), shipped as written with no build step. The banner script itself is the hosted service described above and is not part of this plugin. Development repository: https://github.com/asiri-compliance/asiri-consent-wordpress

== Screenshots ==

1. The Asiri Consent settings page: status, publishable key and source id.
2. Optional consent boxes for forms and checkout, with notice reference, version and purposes.

== Frequently Asked Questions ==

= What if no consent manager is registered? =

Without a consent manager registered, `wp_has_consent()` returns true for every category, so plugins that rely on it run unchecked. The settings page shows "Not registered" in red when this plugin is not registered with the WP Consent API.

= Do I need an Asiri account? =

Yes. The banner, its signed configuration and the consent record are the Asiri service, so the plugin needs a publishable key and a source id from your Asiri workspace. Create them under Consent, Sources.

= Does a page cache affect the consent type? =

The consent type for visitors in California is chosen from CloudFront's country and region headers. If your page cache ignores those headers, the browser script corrects the type once it knows the visitor's region.

== Changelog ==

= 0.1.0 =
* First release.
