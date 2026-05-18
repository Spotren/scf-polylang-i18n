<?php

declare(strict_types=1);

namespace Spotren\ScfPolylangI18n;

if (!defined('ABSPATH')) {
    exit;
}

final class AdminPage
{
    private const PAGE_SLUG = 'scf-polylang-i18n';

    private Config $config;
    private PolylangBridge $polylang;

    public function __construct(Config $config, PolylangBridge $polylang)
    {
        $this->config = $config;
        $this->polylang = $polylang;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_post_scf_polylang_i18n_save', [$this, 'save']);
    }

    public function addMenuPage(): void
    {
        add_options_page(
            __('SCF Polylang i18n', 'scf-polylang-i18n'),
            __('SCF Polylang i18n', 'scf-polylang-i18n'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render']
        );
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to manage this plugin.', 'scf-polylang-i18n'));
        }

        check_admin_referer('scf_polylang_i18n_save');

        $raw = isset($_POST['scf_polylang_i18n']) && is_array($_POST['scf_polylang_i18n'])
            ? map_deep(wp_unslash($_POST['scf_polylang_i18n']), 'sanitize_text_field')
            : [];

        $data = $this->sanitizeFormData($raw);

        update_option(Config::OPTION_NAME, $data, false);
        update_option('scf_polylang_i18n_flush_needed', '1', false);

        wp_safe_redirect(add_query_arg([
            'page' => self::PAGE_SLUG,
            'settings-updated' => 'true',
        ], admin_url('options-general.php')));
        exit;
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $languages = $this->languagesForForm();
        $postTypes = $this->postTypesForForm();
        $taxonomies = $this->taxonomiesForForm();
        $conflicts = $this->detectSlugConflicts($languages);

        echo '<div class="wrap scf-polylang-i18n-admin">';
        echo '<h1>' . esc_html__('SCF Polylang i18n', 'scf-polylang-i18n') . '</h1>';

        if ($this->settingsUpdated()) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved. Rewrite rules will be flushed automatically.', 'scf-polylang-i18n') . '</p></div>';
        }

        if (!$this->polylang->isAvailable()) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Polylang is not active. Settings can be edited, but translations and language detection will only run after Polylang is active.', 'scf-polylang-i18n') . '</p></div>';
        }

        foreach ($conflicts as $conflict) {
            echo '<div class="notice notice-error"><p>' . esc_html($conflict) . '</p></div>';
        }

        $this->renderStyles();

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="scf_polylang_i18n_save">';
        wp_nonce_field('scf_polylang_i18n_save');

        $this->renderGeneralSettings($languages);
        $this->renderPostTypesTable($postTypes, $languages);
        $this->renderTaxonomiesTable($taxonomies, $languages);

        submit_button(__('Save settings', 'scf-polylang-i18n'));

        echo '</form>';
        echo '</div>';
    }

    /** @param array<string, mixed> $raw @return array<string, mixed> */
    private function sanitizeFormData(array $raw): array
    {
        $languages = $this->sanitizeLanguagesCsv((string) ($raw['languages'] ?? ''));

        $data = [
            '_admin_saved' => true,
            'languages' => $languages,
            'auto_labels' => !empty($raw['auto_labels']),
            'unprefixed_default_language' => !empty($raw['unprefixed_default_language']),
            'post_types' => [],
            'taxonomies' => [],
        ];

        $postTypes = isset($raw['post_types']) && is_array($raw['post_types']) ? $raw['post_types'] : [];

        foreach ($postTypes as $postType => $definition) {
            if (!is_string($postType) || !is_array($definition) || empty($definition['enabled'])) {
                continue;
            }

            $postType = sanitize_key($postType);

            if ($postType === '') {
                continue;
            }

            $data['post_types'][$postType] = [
                'slugs' => $this->sanitizeSlugMap($definition['slugs'] ?? [], $languages),
                'archive' => !empty($definition['archive']),
                'single' => !empty($definition['single']),
                'labels' => !empty($definition['labels']),
            ];
        }

        $taxonomies = isset($raw['taxonomies']) && is_array($raw['taxonomies']) ? $raw['taxonomies'] : [];

        foreach ($taxonomies as $taxonomy => $definition) {
            if (!is_string($taxonomy) || !is_array($definition) || empty($definition['enabled'])) {
                continue;
            }

            $taxonomy = sanitize_key($taxonomy);

            if ($taxonomy === '') {
                continue;
            }

            $data['taxonomies'][$taxonomy] = [
                'slugs' => $this->sanitizeSlugMap($definition['slugs'] ?? [], $languages),
                'labels' => !empty($definition['labels']),
            ];
        }

        return $data;
    }

    /** @return list<string> */
    private function languagesForForm(): array
    {
        $languages = $this->polylang->languages();

        if ($languages === []) {
            $languages = ['pt'];
        }

        return $languages;
    }

    /** @return array<string, object> */
    private function postTypesForForm(): array
    {
        $objects = get_post_types(['_builtin' => false], 'objects');

        if (!is_array($objects)) {
            $objects = [];
        }

        foreach (array_keys($this->config->postTypes()) as $postType) {
            if (!isset($objects[$postType])) {
                $objects[$postType] = (object) [
                    'name' => $postType,
                    'label' => $postType,
                ];
            }
        }

        ksort($objects);

        return $objects;
    }

    /** @return array<string, object> */
    private function taxonomiesForForm(): array
    {
        $objects = get_taxonomies(['_builtin' => false], 'objects');

        if (!is_array($objects)) {
            $objects = [];
        }

        foreach (array_keys($this->config->taxonomies()) as $taxonomy) {
            if (!isset($objects[$taxonomy])) {
                $objects[$taxonomy] = (object) [
                    'name' => $taxonomy,
                    'label' => $taxonomy,
                ];
            }
        }

        ksort($objects);

        return $objects;
    }

    /** @param list<string> $languages */
    private function renderGeneralSettings(array $languages): void
    {
        echo '<h2>' . esc_html__('General settings', 'scf-polylang-i18n') . '</h2>';
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="scf-polylang-i18n-languages">' . esc_html__('Languages', 'scf-polylang-i18n') . '</label></th><td>';
        echo '<input type="text" id="scf-polylang-i18n-languages" class="regular-text" name="scf_polylang_i18n[languages]" value="' . esc_attr(implode(', ', $languages)) . '">';
        echo '<p class="description">' . esc_html__('Comma-separated language slugs. Leave aligned with Polylang, for example: pt, en, es.', 'scf-polylang-i18n') . '</p>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Behavior', 'scf-polylang-i18n') . '</th><td>';
        $this->checkbox('scf_polylang_i18n[auto_labels]', '1', $this->config->autoLabels(), __('Automatically register labels/descriptions from custom post types and taxonomies', 'scf-polylang-i18n'));
        echo '<br>';
        $this->checkbox('scf_polylang_i18n[unprefixed_default_language]', '1', $this->config->usesUnprefixedDefaultLanguage(), __('Do not prefix URLs for the default language', 'scf-polylang-i18n'));
        echo '</td></tr>';

        echo '</tbody></table>';
    }

    /** @param array<string, object> $postTypes @param list<string> $languages */
    private function renderPostTypesTable(array $postTypes, array $languages): void
    {
        echo '<h2>' . esc_html__('Custom post types', 'scf-polylang-i18n') . '</h2>';

        if ($postTypes === []) {
            echo '<p>' . esc_html__('No custom post types are registered yet.', 'scf-polylang-i18n') . '</p>';
            return;
        }

        echo '<table class="widefat striped scf-polylang-i18n-table"><thead><tr>';
        echo '<th>' . esc_html__('Post type', 'scf-polylang-i18n') . '</th>';
        echo '<th>' . esc_html__('Use', 'scf-polylang-i18n') . '</th>';
        echo '<th>' . esc_html__('Options', 'scf-polylang-i18n') . '</th>';

        foreach ($languages as $language) {
            /* translators: %s: language slug. */
            echo '<th>' . esc_html(sprintf(__('Slug %s', 'scf-polylang-i18n'), $language)) . '</th>';
        }

        echo '</tr></thead><tbody>';

        foreach ($postTypes as $postType => $object) {
            $definition = $this->config->postTypes()[$postType] ?? [];
            $enabled = $this->config->hasPostType($postType);

            echo '<tr>';
            echo '<td><strong>' . esc_html($this->objectLabel($object, $postType)) . '</strong><br><code>' . esc_html($postType) . '</code></td>';
            echo '<td>';
            $this->checkbox("scf_polylang_i18n[post_types][$postType][enabled]", '1', $enabled, __('Managed', 'scf-polylang-i18n'));
            echo '</td>';
            echo '<td>';
            $this->checkbox("scf_polylang_i18n[post_types][$postType][archive]", '1', (bool) ($definition['archive'] ?? true), __('Archive', 'scf-polylang-i18n'));
            echo '<br>';
            $this->checkbox("scf_polylang_i18n[post_types][$postType][single]", '1', (bool) ($definition['single'] ?? true), __('Single', 'scf-polylang-i18n'));
            echo '<br>';
            $this->checkbox("scf_polylang_i18n[post_types][$postType][labels]", '1', (bool) ($definition['labels'] ?? true), __('Labels', 'scf-polylang-i18n'));
            echo '</td>';

            foreach ($languages as $language) {
                $value = $definition['slugs'][$language] ?? '';
                echo '<td><input type="text" class="regular-text code" name="scf_polylang_i18n[post_types][' . esc_attr($postType) . '][slugs][' . esc_attr($language) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($postType) . '"></td>';
            }

            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /** @param array<string, object> $taxonomies @param list<string> $languages */
    private function renderTaxonomiesTable(array $taxonomies, array $languages): void
    {
        echo '<h2>' . esc_html__('Taxonomies', 'scf-polylang-i18n') . '</h2>';

        if ($taxonomies === []) {
            echo '<p>' . esc_html__('No custom taxonomies are registered yet.', 'scf-polylang-i18n') . '</p>';
            return;
        }

        echo '<table class="widefat striped scf-polylang-i18n-table"><thead><tr>';
        echo '<th>' . esc_html__('Taxonomy', 'scf-polylang-i18n') . '</th>';
        echo '<th>' . esc_html__('Use', 'scf-polylang-i18n') . '</th>';
        echo '<th>' . esc_html__('Options', 'scf-polylang-i18n') . '</th>';

        foreach ($languages as $language) {
            /* translators: %s: language slug. */
            echo '<th>' . esc_html(sprintf(__('Slug %s', 'scf-polylang-i18n'), $language)) . '</th>';
        }

        echo '</tr></thead><tbody>';

        foreach ($taxonomies as $taxonomy => $object) {
            $definition = $this->config->taxonomies()[$taxonomy] ?? [];
            $enabled = $this->config->hasTaxonomy($taxonomy);

            echo '<tr>';
            echo '<td><strong>' . esc_html($this->objectLabel($object, $taxonomy)) . '</strong><br><code>' . esc_html($taxonomy) . '</code></td>';
            echo '<td>';
            $this->checkbox("scf_polylang_i18n[taxonomies][$taxonomy][enabled]", '1', $enabled, __('Managed', 'scf-polylang-i18n'));
            echo '</td>';
            echo '<td>';
            $this->checkbox("scf_polylang_i18n[taxonomies][$taxonomy][labels]", '1', (bool) ($definition['labels'] ?? true), __('Labels', 'scf-polylang-i18n'));
            echo '</td>';

            foreach ($languages as $language) {
                $value = $definition['slugs'][$language] ?? '';
                echo '<td><input type="text" class="regular-text code" name="scf_polylang_i18n[taxonomies][' . esc_attr($taxonomy) . '][slugs][' . esc_attr($language) . ']" value="' . esc_attr($value) . '" placeholder="' . esc_attr($taxonomy) . '"></td>';
            }

            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function checkbox(string $name, string $value, bool $checked, string $label): void
    {
        echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . checked($checked, true, false) . '> ' . esc_html($label) . '</label>';
    }

    private function renderStyles(): void
    {
        echo '<style>
            .scf-polylang-i18n-table { margin-top: 10px; max-width: 100%; }
            .scf-polylang-i18n-table th,
            .scf-polylang-i18n-table td { vertical-align: top; }
            .scf-polylang-i18n-table input.regular-text { width: 100%; max-width: 220px; }
            .scf-polylang-i18n-admin h2 { margin-top: 28px; }
        </style>';
    }

    /** @return list<string> */
    private function sanitizeLanguagesCsv(string $value): array
    {
        $languages = [];

        foreach (explode(',', $value) as $language) {
            $language = sanitize_key(trim($language));

            if ($language !== '') {
                $languages[] = $language;
            }
        }

        return array_values(array_unique($languages));
    }

    /** @param mixed $slugs @param list<string> $languages @return array<string, string> */
    private function sanitizeSlugMap($slugs, array $languages): array
    {
        if (!is_array($slugs)) {
            return [];
        }

        $normalized = [];

        foreach ($languages as $language) {
            $slug = isset($slugs[$language]) && is_string($slugs[$language])
                ? $this->sanitizePath($slugs[$language])
                : '';

            if ($slug !== '') {
                $normalized[$language] = $slug;
            }
        }

        return $normalized;
    }

    private function sanitizePath(string $path): string
    {
        $parts = array_filter(array_map('sanitize_title', explode('/', trim($path, '/'))));

        return implode('/', $parts);
    }

    private function objectLabel(object $object, string $fallback): string
    {
        if (isset($object->label) && is_string($object->label) && $object->label !== '') {
            return $object->label;
        }

        return $fallback;
    }

    private function settingsUpdated(): bool
    {
        return filter_input(INPUT_GET, 'settings-updated', FILTER_VALIDATE_BOOLEAN) === true;
    }

    /** @param list<string> $languages @return list<string> */
    private function detectSlugConflicts(array $languages): array
    {
        $seen = [];
        $conflicts = [];

        foreach ($languages as $language) {
            foreach ($this->config->postTypes() as $postType => $definition) {
                $slug = isset($definition['slugs'][$language]) ? (string) $definition['slugs'][$language] : '';

                if ($slug !== '') {
                    $this->trackSlugConflict($seen, $conflicts, $language, $slug, 'post type ' . $postType);
                }
            }

            foreach ($this->config->taxonomies() as $taxonomy => $definition) {
                $slug = isset($definition['slugs'][$language]) ? (string) $definition['slugs'][$language] : '';

                if ($slug !== '') {
                    $this->trackSlugConflict($seen, $conflicts, $language, $slug, 'taxonomy ' . $taxonomy);
                }
            }
        }

        return array_values(array_unique($conflicts));
    }

    /** @param array<string, string> $seen @param list<string> $conflicts */
    private function trackSlugConflict(array &$seen, array &$conflicts, string $language, string $slug, string $owner): void
    {
        $key = $language . '/' . trim($slug, '/');

        if (!isset($seen[$key])) {
            $seen[$key] = $owner;
            return;
        }

        $conflicts[] = sprintf('Slug conflict for %s: "%s" is used by %s and %s.', $language, $slug, $seen[$key], $owner);
    }
}
