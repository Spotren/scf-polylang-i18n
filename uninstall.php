<?php

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('scf_polylang_i18n_mappings');
delete_option('scf_polylang_i18n_config_hash');
delete_option('scf_polylang_i18n_flush_needed');
