<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class App
{
    public static function boot(string $pluginDir): void
    {
        $config = new Config($pluginDir);
        $polylang = new PolylangBridge($config);
        $strings = new StringRegistry($polylang);

        (new ArgsTranslator($config, $strings, $polylang))->register();
        (new PolylangSettings($config))->register();
        (new RewriteRules($config, $polylang))->register();
        (new LinkFilters($config, $polylang))->register();
        (new CanonicalRedirects($config, $polylang))->register();
        (new AdminPage($config, $polylang))->register();
        (new SiteSeoIntegration())->register();
    }
}
