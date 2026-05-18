<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class RewriteRules
{
    private Config $config;
    private PolylangBridge $polylang;

    public function __construct(Config $config, PolylangBridge $polylang)
    {
        $this->config = $config;
        $this->polylang = $polylang;
    }

    public function register(): void
    {
        add_action('init', [$this, 'addRules'], 30);
        add_action('admin_init', [$this, 'maybeFlushRules']);
        add_filter('query_vars', [$this, 'addQueryVars']);
        add_filter('request', [$this, 'addLanguageToManagedRequest'], 5);
    }

    public function addRules(): void
    {
        foreach ($this->polylang->languages() as $language) {
            $prefix = $this->polylang->pathPrefix($language);

            foreach (array_keys($this->config->postTypes()) as $postType) {
                $base = $this->config->postTypeBase($postType, $language);

                if ($base === null) {
                    continue;
                }

                if ($this->config->shouldRewritePostTypeArchive($postType)) {
                    $this->addPostTypeArchiveRules($postType, $language, $prefix, $base);
                }

                if ($this->config->shouldRewritePostTypeSingle($postType)) {
                    $this->addPostTypeSingleRules($postType, $language, $prefix, $base);
                }
            }

            foreach (array_keys($this->config->taxonomies()) as $taxonomy) {
                $base = $this->config->taxonomyBase($taxonomy, $language);

                if ($base === null) {
                    continue;
                }

                $this->addTaxonomyRules($taxonomy, $language, $prefix, $base);
            }
        }
    }

    /** @param list<string> $vars */
    public function addQueryVars(array $vars): array
    {
        if (!in_array('lang', $vars, true)) {
            $vars[] = 'lang';
        }

        return $vars;
    }

    /** @param array<string, mixed> $queryVars @return array<string, mixed> */
    public function addLanguageToManagedRequest(array $queryVars): array
    {
        $language = $this->languageFromRequestPath();

        if ($language !== null) {
            $queryVars['lang'] = $language;
        }

        return $queryVars;
    }

    public function maybeFlushRules(): void
    {
        $currentHash = $this->config->hash();
        $storedHash = (string) get_option('scf_polylang_i18n_config_hash', '');
        $flushNeeded = get_option('scf_polylang_i18n_flush_needed') === '1';

        if (!$flushNeeded && $storedHash === $currentHash) {
            return;
        }

        flush_rewrite_rules(false);
        update_option('scf_polylang_i18n_config_hash', $currentHash, false);
        delete_option('scf_polylang_i18n_flush_needed');
    }

    private function addPostTypeArchiveRules(string $postType, string $language, string $prefix, string $base): void
    {
        $path = $this->joinPath($prefix, $base);
        $target = 'index.php?post_type=' . $postType;

        add_rewrite_rule('^' . $path . '/?$', $target, 'top');
        add_rewrite_rule('^' . $path . '/page/([0-9]{1,})/?$', $target . '&paged=$matches[1]', 'top');
        add_rewrite_rule('^' . $path . '/feed/(feed|rdf|rss|rss2|atom)/?$', $target . '&feed=$matches[1]', 'top');
        add_rewrite_rule('^' . $path . '/(feed|rdf|rss|rss2|atom)/?$', $target . '&feed=$matches[1]', 'top');
    }

    private function addPostTypeSingleRules(string $postType, string $language, string $prefix, string $base): void
    {
        $path = $this->joinPath($prefix, $base);
        $pattern = $this->postTypeIsHierarchical($postType) ? '(.+?)' : '([^/]+)';

        add_rewrite_rule(
            '^' . $path . '/' . $pattern . '/?$',
            'index.php?post_type=' . $postType . '&name=$matches[1]',
            'top'
        );
    }

    private function addTaxonomyRules(string $taxonomy, string $language, string $prefix, string $base): void
    {
        $path = $this->joinPath($prefix, $base);
        $queryTarget = $this->taxonomyQueryTarget($taxonomy);

        add_rewrite_rule(
            '^' . $path . '/(.+?)/page/([0-9]{1,})/?$',
            $queryTarget . '$matches[1]&paged=$matches[2]',
            'top'
        );

        add_rewrite_rule(
            '^' . $path . '/(.+?)/?$',
            $queryTarget . '$matches[1]',
            'top'
        );
    }

    private function taxonomyQueryTarget(string $taxonomy): string
    {
        $object = get_taxonomy($taxonomy);

        if ($object && $object->query_var) {
            $queryVar = is_string($object->query_var) ? $object->query_var : $taxonomy;

            return 'index.php?' . $queryVar . '=';
        }

        return 'index.php?taxonomy=' . $taxonomy . '&term=';
    }

    private function postTypeIsHierarchical(string $postType): bool
    {
        $object = get_post_type_object($postType);

        return $object ? (bool) $object->hierarchical : false;
    }

    private function joinPath(string ...$parts): string
    {
        return implode('/', array_filter(array_map(static function (string $part): string {
            return trim($part, '/');
        }, $parts), static function (string $part): bool {
            return $part !== '';
        }));
    }

    private function languageFromRequestPath(): ?string
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
            : '';

        $path = trim((string) wp_parse_url($requestUri, PHP_URL_PATH), '/');

        if ($path === '') {
            return null;
        }

        foreach ($this->polylang->languages() as $language) {
            $prefix = $this->polylang->pathPrefix($language);

            foreach (array_keys($this->config->postTypes()) as $postType) {
                $base = $this->config->postTypeBase($postType, $language);

                if ($base !== null && $this->pathStartsWith($path, $this->joinPath($prefix, $base))) {
                    return $language;
                }
            }

            foreach (array_keys($this->config->taxonomies()) as $taxonomy) {
                $base = $this->config->taxonomyBase($taxonomy, $language);

                if ($base !== null && $this->pathStartsWith($path, $this->joinPath($prefix, $base))) {
                    return $language;
                }
            }
        }

        return null;
    }

    private function pathStartsWith(string $path, string $prefix): bool
    {
        $prefix = trim($prefix, '/');

        return $prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'));
    }
}
