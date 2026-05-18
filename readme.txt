=== SCF Polylang i18n ===
Contributors: spotren
Tags: polylang, scf, custom post types, taxonomies, multilingual
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Translate labels, descriptions, and URL bases for SCF-managed custom post types and taxonomies with Polylang.

== Description ==

SCF Polylang i18n complements custom post types and taxonomies created with Secure Custom Fields.

It registers post type and taxonomy labels as Polylang strings, applies translated labels at runtime, and adds WordPress rewrite rules for translated archive, single, and taxonomy URL bases.

This plugin does not create custom post types or taxonomies. Secure Custom Fields remains the source of truth for object registration.

== Requirements ==

* WordPress 6.5 or later.
* PHP 8.0 or later.
* Polylang.
* Secure Custom Fields.

SiteSEO integration is optional. When SiteSEO is active, the plugin supports the `%%cpt_description%%` and `%%post_type_description%%` placeholders for post type archive and single templates.

== Installation ==

1. Install and activate Polylang.
2. Install and activate Secure Custom Fields.
3. Upload the `scf-polylang-i18n` folder to the `/wp-content/plugins/` directory.
4. Activate SCF Polylang i18n through the Plugins screen.
5. Configure URL bases in Settings > SCF Polylang i18n.
6. Flush permalinks if needed.

== Frequently Asked Questions ==

= Does this plugin translate content? =

No. Content translation remains handled by Polylang.

= Does this replace Polylang Pro translated slugs? =

No. It adds project-specific WordPress rewrite rules for configured custom post type and taxonomy bases.

= Does this translate taxonomy terms? =

No. It translates taxonomy object labels and URL bases, not individual terms.

== Changelog ==

= 0.1.0 =
* Initial internal release.
