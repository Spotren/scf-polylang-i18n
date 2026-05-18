<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class SiteSeoIntegration
{
    public function register(): void
    {
        add_action('wp_head', [$this, 'replaceMetaDescriptionPlaceholders'], 0);
        add_filter('pre_get_document_title', [$this, 'replaceTitlePlaceholder'], 999);
        add_filter('wp_title', [$this, 'replaceTitlePlaceholder'], 999);
    }

    public function replaceMetaDescriptionPlaceholders(): void
    {
        global $siteseo;

        if (!isset($siteseo->titles_settings) || !is_array($siteseo->titles_settings)) {
            return;
        }

        if (is_post_type_archive()) {
            $object = get_queried_object();

            if ($object instanceof \WP_Post_Type && !empty($object->name)) {
                $this->replaceArchivePlaceholders($siteseo->titles_settings, $object);
            }
        }

        if (is_singular()) {
            $postType = get_post_type();
            $object = is_string($postType) ? get_post_type_object($postType) : null;

            if ($object instanceof \WP_Post_Type && !empty($object->name)) {
                $this->replaceSinglePlaceholders($siteseo->titles_settings, $object);
            }
        }
    }

    public function replaceTitlePlaceholder(string $title): string
    {
        $object = null;

        if (is_post_type_archive()) {
            $queried = get_queried_object();
            $object = $queried instanceof \WP_Post_Type ? $queried : null;
        } elseif (is_singular()) {
            $postType = get_post_type();
            $object = is_string($postType) ? get_post_type_object($postType) : null;
        }

        return $object instanceof \WP_Post_Type ? $this->replacePostTypeDescription($title, $object) : $title;
    }

    /** @param array<string, mixed> $settings */
    private function replaceArchivePlaceholders(array &$settings, \WP_Post_Type $object): void
    {
        if (empty($settings['titles_archive_titles'][$object->name])) {
            return;
        }

        foreach (['archive_title', 'archive_desc'] as $field) {
            if (empty($settings['titles_archive_titles'][$object->name][$field])) {
                continue;
            }

            $settings['titles_archive_titles'][$object->name][$field] = $this->replacePostTypeDescription(
                (string) $settings['titles_archive_titles'][$object->name][$field],
                $object
            );
        }
    }

    /** @param array<string, mixed> $settings */
    private function replaceSinglePlaceholders(array &$settings, \WP_Post_Type $object): void
    {
        if (empty($settings['titles_single_titles'][$object->name])) {
            return;
        }

        foreach (['title', 'description'] as $field) {
            if (empty($settings['titles_single_titles'][$object->name][$field])) {
                continue;
            }

            $settings['titles_single_titles'][$object->name][$field] = $this->replacePostTypeDescription(
                (string) $settings['titles_single_titles'][$object->name][$field],
                $object
            );
        }
    }

    private function replacePostTypeDescription(string $template, \WP_Post_Type $object): string
    {
        return str_replace(
            ['%%cpt_description%%', '%%post_type_description%%'],
            [(string) $object->description, (string) $object->description],
            $template
        );
    }
}
