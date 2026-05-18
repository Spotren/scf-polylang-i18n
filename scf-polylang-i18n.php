<?php
/**
 * Plugin Name: SCF Polylang i18n
 * Plugin URI: https://spotren.com/?utm_source=scf-polylang-i18n
 * Description: Adds Polylang string translation and custom language-prefixed rewrites for SCF-managed CPTs and taxonomies.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: polylang, secure-custom-fields
 * Author: Spotren
 * Author URI: https://spotren.com/?utm_source=scf-polylang-i18n
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: scf-polylang-i18n
 * Domain Path: /languages
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('SCF_POLYLANG_I18N_FILE', __FILE__);
define('SCF_POLYLANG_I18N_DIR', plugin_dir_path(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Spotren\\ScfPolylangI18n\\';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = SCF_POLYLANG_I18N_DIR . 'src/' . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($file)) {
        require $file;
    }
});

register_activation_hook(__FILE__, static function (): void {
    update_option('scf_polylang_i18n_flush_needed', '1', false);
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
});

add_action('plugins_loaded', static function (): void {
    Spotren\ScfPolylangI18n\App::boot(SCF_POLYLANG_I18N_DIR);
});
