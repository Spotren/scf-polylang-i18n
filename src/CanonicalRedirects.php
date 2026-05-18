<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class CanonicalRedirects
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
        add_filter('redirect_canonical', [$this, 'disableForManagedUrls'], 20, 2);
    }

    /** @param string|false $redirectUrl */
    public function disableForManagedUrls($redirectUrl, string $requestedUrl)
    {
        if ($redirectUrl === false || !$this->isManagedRequest()) {
            return $redirectUrl;
        }

        return false;
    }

    private function isManagedRequest(): bool
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
            : '';

        $path = trim((string) wp_parse_url($requestUri, PHP_URL_PATH), '/');

        if ($path === '') {
            return false;
        }

        foreach ($this->polylang->languages() as $language) {
            $prefix = $this->polylang->pathPrefix($language);

            foreach (array_keys($this->config->postTypes()) as $postType) {
                $base = $this->config->postTypeBase($postType, $language);

                if ($base !== null && $this->pathStartsWith($path, $this->joinPath($prefix, $base))) {
                    return true;
                }
            }

            foreach (array_keys($this->config->taxonomies()) as $taxonomy) {
                $base = $this->config->taxonomyBase($taxonomy, $language);

                if ($base !== null && $this->pathStartsWith($path, $this->joinPath($prefix, $base))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pathStartsWith(string $path, string $prefix): bool
    {
        $prefix = trim($prefix, '/');

        return $prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'));
    }

    private function joinPath(string ...$parts): string
    {
        return implode('/', array_filter(array_map(static function (string $part): string {
            return trim($part, '/');
        }, $parts), static function (string $part): bool {
            return $part !== '';
        }));
    }
}
