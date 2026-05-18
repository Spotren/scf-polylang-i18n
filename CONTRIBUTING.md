# Contributing

Thanks for contributing to SCF Polylang i18n.

## Before you start

- Open an issue before starting larger changes so the approach can be aligned first.
- Keep pull requests focused on one change.
- Do not commit generated release artifacts such as the plugin ZIP.

## Local development

Install development dependencies:

```bash
composer install
```

Run coding standards:

```bash
composer lint
```

Build a release ZIP from the current commit:

```bash
bin/build-plugin-zip.sh
```

Run the repository smoke test:

```bash
php bin/smoke-test.php
```

Run the runtime smoke test against a local WordPress install:

```bash
php bin/smoke-test.php /absolute/path/to/wordpress
```

## Coding expectations

- Follow WordPress coding conventions in PHP.
- Keep changes compatible with the current plugin architecture and existing admin workflow.
- Update `README.md` and `readme.txt` when behavior changes in a way maintainers or users should know.
- Keep the release ZIP clean of repository-only files.

## Pull requests

Please include:

- A short summary of the change
- Why the change is needed
- Manual test steps
- Screenshots when admin UI changes

If your change affects rewrites or Polylang integration, describe the tested language setup and the expected URL behavior.
