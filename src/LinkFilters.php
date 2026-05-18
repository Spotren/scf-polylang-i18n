<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class LinkFilters
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
        add_filter('post_type_link', [$this, 'postTypeLink'], 20, 4);
        add_filter('post_type_archive_link', [$this, 'postTypeArchiveLink'], 20, 2);
        add_filter('term_link', [$this, 'termLink'], 20, 3);
    }

    /** @param \WP_Post $post */
    public function postTypeLink(string $postLink, $post, bool $leavename, bool $sample): string
    {
        if (!$post instanceof \WP_Post || !$this->config->shouldRewritePostTypeSingle($post->post_type)) {
            return $postLink;
        }

        $language = $this->polylang->postLanguage((int) $post->ID);
        $base = $this->config->postTypeBase($post->post_type, $language);

        if ($base === null) {
            return $postLink;
        }

        $slug = $leavename ? '%' . $post->post_type . '%' : $post->post_name;

        if (is_post_type_hierarchical($post->post_type) && !$leavename) {
            $uri = get_page_uri($post);

            if (is_string($uri) && $uri !== '') {
                $slug = $uri;
            }
        }

        return $this->homeUrl($language, $base, $slug);
    }

    public function postTypeArchiveLink(string $link, string $postType): string
    {
        if (!$this->config->shouldRewritePostTypeArchive($postType)) {
            return $link;
        }

        $language = $this->polylang->currentLanguage();
        $base = $this->config->postTypeBase($postType, $language);

        return $base === null ? $link : $this->homeUrl($language, $base);
    }

    /** @param \WP_Term|\WP_Error $term */
    public function termLink(string $termlink, $term, string $taxonomy): string
    {
        if (!$term instanceof \WP_Term || !$this->config->hasTaxonomy($taxonomy)) {
            return $termlink;
        }

        $language = $this->polylang->termLanguage((int) $term->term_id);
        $base = $this->config->taxonomyBase($taxonomy, $language);

        if ($base === null) {
            return $termlink;
        }

        return $this->homeUrl($language, $base, $this->termPath($term));
    }

    private function termPath(\WP_Term $term): string
    {
        if (!$term->parent) {
            return $term->slug;
        }

        $ancestors = array_reverse(get_ancestors($term->term_id, $term->taxonomy, 'taxonomy'));
        $slugs = [];

        foreach ($ancestors as $ancestorId) {
            $ancestor = get_term((int) $ancestorId, $term->taxonomy);

            if ($ancestor instanceof \WP_Term) {
                $slugs[] = $ancestor->slug;
            }
        }

        $slugs[] = $term->slug;

        return implode('/', $slugs);
    }

    private function homeUrl(string $language, string ...$parts): string
    {
        $pathParts = [];
        $prefix = $this->polylang->pathPrefix($language);

        if ($prefix !== '') {
            $pathParts[] = $prefix;
        }

        foreach ($parts as $part) {
            $part = trim($part, '/');

            if ($part !== '') {
                $pathParts[] = $part;
            }
        }

        return home_url(user_trailingslashit(implode('/', $pathParts)));
    }
}
