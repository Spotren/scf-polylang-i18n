<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class ArgsTranslator
{
    private Config $config;
    private StringRegistry $strings;
    private PolylangBridge $polylang;

    public function __construct(Config $config, StringRegistry $strings, PolylangBridge $polylang)
    {
        $this->config = $config;
        $this->strings = $strings;
        $this->polylang = $polylang;
    }

    public function register(): void
    {
        add_filter('register_post_type_args', [$this, 'filterPostTypeArgs'], 20, 2);
        add_filter('register_taxonomy_args', [$this, 'filterTaxonomyArgs'], 20, 2);
    }

    public function filterPostTypeArgs(array $args, string $postType): array
    {
        if (!$this->config->shouldTranslatePostTypeLabels($postType, $args)) {
            return $args;
        }

        if (is_admin()) {
            $this->strings->registerPostTypeArgs($postType, $args);
        }

        return $this->polylang->isAvailable() ? $this->strings->translateArgs($args) : $args;
    }

    public function filterTaxonomyArgs(array $args, string $taxonomy): array
    {
        if (!$this->config->shouldTranslateTaxonomyLabels($taxonomy, $args)) {
            return $args;
        }

        if (is_admin()) {
            $this->strings->registerTaxonomyArgs($taxonomy, $args);
        }

        return $this->polylang->isAvailable() ? $this->strings->translateArgs($args) : $args;
    }
}
