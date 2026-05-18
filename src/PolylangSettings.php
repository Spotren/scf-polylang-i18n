<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class PolylangSettings
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function register(): void
    {
        add_filter('pll_get_post_types', [$this, 'addPostTypes'], 10, 2);
        add_filter('pll_get_taxonomies', [$this, 'addTaxonomies'], 10, 2);
    }

    public function addPostTypes(array $postTypes, bool $isSettings = false): array
    {
        foreach (array_keys($this->config->postTypes()) as $postType) {
            $postTypes[$postType] = $postType;
        }

        return $postTypes;
    }

    public function addTaxonomies(array $taxonomies, bool $isSettings = false): array
    {
        foreach (array_keys($this->config->taxonomies()) as $taxonomy) {
            $taxonomies[$taxonomy] = $taxonomy;
        }

        return $taxonomies;
    }
}
