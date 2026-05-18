<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class Config
{
    public const OPTION_NAME = 'scf_polylang_i18n_mappings';

    /** @var array<string, mixed> */
    private array $data;

    public function __construct(string $pluginDir)
    {
        $file = $pluginDir . 'config/mappings.php';
        $data = is_readable($file) ? require $file : [];

        if (!is_array($data)) {
            $data = [];
        }

        $optionData = get_option(self::OPTION_NAME, []);

        if (!is_array($optionData)) {
            $optionData = [];
        }

        $data = $this->mergeConfig($data, $optionData);

        $this->data = $this->normalize((array) apply_filters('scf_polylang_i18n_config', $data));
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }

    public function autoLabels(): bool
    {
        return (bool) ($this->data['auto_labels'] ?? true);
    }

    public function usesUnprefixedDefaultLanguage(): bool
    {
        return (bool) ($this->data['unprefixed_default_language'] ?? false);
    }

    /** @return list<string> */
    public function configuredLanguages(): array
    {
        return $this->data['languages'] ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public function postTypes(): array
    {
        return $this->data['post_types'] ?? [];
    }

    /** @return array<string, array<string, mixed>> */
    public function taxonomies(): array
    {
        return $this->data['taxonomies'] ?? [];
    }

    public function hasPostType(string $postType): bool
    {
        return isset($this->data['post_types'][$postType]);
    }

    public function hasTaxonomy(string $taxonomy): bool
    {
        return isset($this->data['taxonomies'][$taxonomy]);
    }

    public function shouldTranslatePostTypeLabels(string $postType, array $args): bool
    {
        if ($this->hasPostType($postType)) {
            return (bool) ($this->data['post_types'][$postType]['labels'] ?? true);
        }

        return $this->autoLabels() && $this->isPublicCustomObject($args) && !$this->isCorePostType($postType, $args);
    }

    public function shouldTranslateTaxonomyLabels(string $taxonomy, array $args): bool
    {
        if ($this->hasTaxonomy($taxonomy)) {
            return (bool) ($this->data['taxonomies'][$taxonomy]['labels'] ?? true);
        }

        return $this->autoLabels() && $this->isPublicCustomObject($args) && !$this->isCoreTaxonomy($taxonomy, $args);
    }

    public function shouldRewritePostTypeArchive(string $postType): bool
    {
        if (!$this->hasPostType($postType)) {
            return false;
        }

        return (bool) ($this->data['post_types'][$postType]['archive'] ?? true);
    }

    public function shouldRewritePostTypeSingle(string $postType): bool
    {
        if (!$this->hasPostType($postType)) {
            return false;
        }

        return (bool) ($this->data['post_types'][$postType]['single'] ?? true);
    }

    public function postTypeBase(string $postType, string $language): ?string
    {
        return $this->baseFor($this->data['post_types'][$postType]['slugs'] ?? [], $language);
    }

    public function taxonomyBase(string $taxonomy, string $language): ?string
    {
        return $this->baseFor($this->data['taxonomies'][$taxonomy]['slugs'] ?? [], $language);
    }

    /** @return list<string> */
    public function configuredRewriteLanguages(): array
    {
        $languages = $this->configuredLanguages();

        foreach ($this->postTypes() as $definition) {
            $languages = array_merge($languages, array_keys($definition['slugs'] ?? []));
        }

        foreach ($this->taxonomies() as $definition) {
            $languages = array_merge($languages, array_keys($definition['slugs'] ?? []));
        }

        return array_values(array_unique(array_filter($languages)));
    }

    public function hash(): string
    {
        return md5((string) wp_json_encode($this->data));
    }

    /** @param array<string, mixed> $data */
    private function normalize(array $data): array
    {
        $data['languages'] = $this->normalizeLanguages($data['languages'] ?? []);
        $data['post_types'] = $this->normalizeDefinitions($data['post_types'] ?? []);
        $data['taxonomies'] = $this->normalizeDefinitions($data['taxonomies'] ?? []);
        $data['auto_labels'] = (bool) ($data['auto_labels'] ?? true);
        $data['unprefixed_default_language'] = (bool) ($data['unprefixed_default_language'] ?? false);

        return $data;
    }

    /** @param mixed $languages @return list<string> */
    private function normalizeLanguages($languages): array
    {
        if (!is_array($languages)) {
            return [];
        }

        $normalized = [];

        foreach ($languages as $language) {
            if (!is_string($language)) {
                continue;
            }

            $language = sanitize_key($language);

            if ($language !== '') {
                $normalized[] = $language;
            }
        }

        return array_values(array_unique($normalized));
    }

    /** @param mixed $definitions @return array<string, array<string, mixed>> */
    private function normalizeDefinitions($definitions): array
    {
        if (!is_array($definitions)) {
            return [];
        }

        $normalized = [];

        foreach ($definitions as $name => $definition) {
            if (!is_string($name) || !is_array($definition)) {
                continue;
            }

            $key = sanitize_key($name);

            if ($key === '') {
                continue;
            }

            $definition['slugs'] = $this->normalizeSlugs($definition['slugs'] ?? $definition['base'] ?? []);
            $normalized[$key] = $definition;
        }

        return $normalized;
    }

    /** @param mixed $slugs @return array<string, string> */
    private function normalizeSlugs($slugs): array
    {
        if (!is_array($slugs)) {
            return [];
        }

        $normalized = [];

        foreach ($slugs as $language => $slug) {
            if (!is_string($language) || !is_string($slug)) {
                continue;
            }

            $language = sanitize_key($language);
            $slug = $this->normalizePath($slug);

            if ($language !== '' && $slug !== '') {
                $normalized[$language] = $slug;
            }
        }

        return $normalized;
    }

    private function normalizePath(string $path): string
    {
        $parts = array_filter(array_map('sanitize_title', explode('/', trim($path, '/'))));

        return implode('/', $parts);
    }

    /** @param array<string, string> $bases */
    private function baseFor(array $bases, string $language): ?string
    {
        if (isset($bases[$language])) {
            return $bases[$language];
        }

        $configured = $this->configuredLanguages();

        foreach ($configured as $fallbackLanguage) {
            if (isset($bases[$fallbackLanguage])) {
                return $bases[$fallbackLanguage];
            }
        }

        $base = reset($bases);

        return is_string($base) && $base !== '' ? $base : null;
    }

    private function isCorePostType(string $postType, array $args): bool
    {
        if (!empty($args['_builtin'])) {
            return true;
        }

        return in_array($postType, ['post', 'page', 'attachment', 'revision', 'nav_menu_item', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation'], true);
    }

    private function isCoreTaxonomy(string $taxonomy, array $args): bool
    {
        if (!empty($args['_builtin'])) {
            return true;
        }

        return in_array($taxonomy, ['category', 'post_tag', 'nav_menu', 'link_category', 'post_format', 'wp_theme'], true);
    }

    private function isPublicCustomObject(array $args): bool
    {
        return !empty($args['public']) || !empty($args['show_ui']) || !empty($args['show_in_nav_menus']);
    }

    /** @param array<string, mixed> $fileData @param array<string, mixed> $optionData @return array<string, mixed> */
    private function mergeConfig(array $fileData, array $optionData): array
    {
        if (!empty($optionData['_admin_saved'])) {
            return array_merge($fileData, $optionData);
        }

        $merged = array_merge($fileData, $optionData);
        $merged['post_types'] = array_merge($fileData['post_types'] ?? [], $optionData['post_types'] ?? []);
        $merged['taxonomies'] = array_merge($fileData['taxonomies'] ?? [], $optionData['taxonomies'] ?? []);

        return $merged;
    }
}
