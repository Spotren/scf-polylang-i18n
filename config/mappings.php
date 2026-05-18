<?php
/**
 * URL slug map for SCF-managed post types and taxonomies.
 *
 * Keep this file focused on URL bases. Labels and descriptions are registered
 * automatically as Polylang strings from the args passed by SCF to WordPress.
 *
 * Example:
 *
 * return [
 *     'languages' => ['pt', 'en'],
 *     'post_types' => [
 *         'solution' => [
 *             'slugs' => [
 *                 'pt' => 'solucoes',
 *                 'en' => 'solutions',
 *             ],
 *             'archive' => true,
 *             'single' => true,
 *         ],
 *     ],
 *     'taxonomies' => [
 *         'solution_category' => [
 *             'slugs' => [
 *                 'pt' => 'categorias-de-solucoes',
 *                 'en' => 'solution-categories',
 *             ],
 *         ],
 *     ],
 * ];
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    'languages' => [],
    'post_types' => [],
    'taxonomies' => [],
    'auto_labels' => true,
    'unprefixed_default_language' => false,
];
