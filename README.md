# SCF Polylang i18n

SCF Polylang i18n is a WordPress plugin that complements custom post types and taxonomies registered with Secure Custom Fields.

It translates labels and descriptions through Polylang, exposes managed objects in Polylang settings, and adds project-specific rewrite rules for translated CPT and taxonomy URL bases.

## Scope

This plugin does not register custom post types or taxonomies. Secure Custom Fields remains the source of truth for object registration.

The plugin currently provides:

- Polylang string registration for custom post type and taxonomy labels and descriptions
- Runtime translation of registered labels and descriptions
- Translated rewrite bases for managed custom post type archives, singles, and taxonomies
- URL generation filters that keep WordPress links aligned with translated bases
- Optional SiteSEO placeholder support for `%%cpt_description%%` and `%%post_type_description%%`
- Automatic cleanup of plugin options on uninstall

The plugin does not provide:

- Automatic content translation
- Taxonomy term translation
- Polylang Pro translated slug UI
- Full replacement of all internal SiteSEO placeholders

## Requirements

- WordPress 6.5 or later
- PHP 8.0 or later
- Polylang
- Secure Custom Fields

SiteSEO integration is optional and only runs when the SiteSEO plugin is active.

## Repository layout

- `scf-polylang-i18n.php`
  Plugin bootstrap and metadata
- `src/`
  Runtime classes
- `config/mappings.php`
  Optional file-based default mappings
- `languages/`
  Translation template files
- `bin/`
  Repository smoke test and release packaging helpers
- `.github/`
  Issue templates and GitHub Actions workflows

## Development

Install development dependencies:

```bash
composer install
```

Run coding standards:

```bash
composer lint
```

Run repository checks:

```bash
php bin/smoke-test.php
```

Run the runtime smoke test against a local WordPress install:

```bash
php bin/smoke-test.php /absolute/path/to/wordpress
```

Build a release ZIP from the current commit:

```bash
bin/build-plugin-zip.sh
```

The ZIP is created with `git archive --worktree-attributes`, so repository-only files listed in `.gitattributes` stay out of the distributable plugin.

## Local WordPress usage

The main settings screen is available at:

```text
Settings > SCF Polylang i18n
```

From there you can manage:

- language columns used for slugs
- managed custom post types and taxonomies
- per-language rewrite bases
- archive and single rewrite behavior for custom post types
- label and description registration with Polylang
- whether the default language should remain unprefixed

Saved settings are stored in the `scf_polylang_i18n_mappings` option. After the first admin save, that option becomes the primary editable mapping source.

## File-based defaults

You can define defaults in `config/mappings.php`. The admin UI overrides matching values after the option is saved.

Example:

```php
return [
    'languages' => ['pt', 'en'],
    'post_types' => [
        'solution' => [
            'slugs' => [
                'pt' => 'solucoes',
                'en' => 'solutions',
            ],
            'archive' => true,
            'single' => true,
        ],
    ],
    'taxonomies' => [
        'solution_category' => [
            'slugs' => [
                'pt' => 'categorias-de-solucoes',
                'en' => 'solution-categories',
            ],
        ],
    ],
    'auto_labels' => true,
    'unprefixed_default_language' => false,
];
```

With `unprefixed_default_language => false`, generated URLs include a language prefix for every configured language, for example:

```text
/pt/solucoes/
/pt/solucoes/nome-do-post/
/pt/categorias-de-solucoes/nome-do-termo/
```

## Release workflow

The repository includes:

- `CI`
  Runs Composer validation, PHPCS, the repository smoke test, build packaging, and Plugin Check against the packaged plugin
- `Release Plugin ZIP`
  Builds `scf-polylang-i18n.zip` and attaches it to GitHub releases

## SiteSEO placeholders

For CPT archives and singles, the plugin adds these placeholders to SiteSEO templates:

```text
%%cpt_description%%
%%post_type_description%%
```

They can be used in SiteSEO title and meta description templates for managed custom post types.
