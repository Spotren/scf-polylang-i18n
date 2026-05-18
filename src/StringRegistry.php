<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class StringRegistry
{
    private const POST_TYPE_GROUP = 'SCF Polylang i18n - Post types';
    private const TAXONOMY_GROUP = 'SCF Polylang i18n - Taxonomies';

    private PolylangBridge $polylang;

    /** @var array<string, true> */
    private array $registered = [];

    public function __construct(PolylangBridge $polylang)
    {
        $this->polylang = $polylang;
    }

    public function registerPostTypeArgs(string $postType, array $args): void
    {
        $this->registerArgs('post_type.' . $postType, $args, self::POST_TYPE_GROUP);
    }

    public function registerTaxonomyArgs(string $taxonomy, array $args): void
    {
        $this->registerArgs('taxonomy.' . $taxonomy, $args, self::TAXONOMY_GROUP);
    }

    public function translateArgs(array $args): array
    {
        if (isset($args['label']) && is_string($args['label'])) {
            $args['label'] = $this->polylang->translate($args['label']);
        }

        if (isset($args['description']) && is_string($args['description'])) {
            $args['description'] = $this->polylang->translate($args['description']);
        }

        if (isset($args['labels']) && is_array($args['labels'])) {
            foreach ($args['labels'] as $key => $value) {
                if (is_string($value)) {
                    $args['labels'][$key] = $this->polylang->translate($value);
                }
            }
        }

        return $args;
    }

    private function registerArgs(string $prefix, array $args, string $group): void
    {
        if (isset($args['label']) && is_string($args['label'])) {
            $this->register($prefix . '.label', $args['label'], $group);
        }

        if (isset($args['description']) && is_string($args['description'])) {
            $this->register($prefix . '.description', $args['description'], $group);
        }

        if (!isset($args['labels']) || !is_array($args['labels'])) {
            return;
        }

        foreach ($args['labels'] as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $this->register($prefix . '.labels.' . $key, $value, $group);
            }
        }
    }

    private function register(string $name, string $value, string $group): void
    {
        $key = $group . '|' . $name . '|' . $value;

        if (isset($this->registered[$key])) {
            return;
        }

        $this->registered[$key] = true;
        $this->polylang->registerString($name, $value, $group);
    }
}
