# Contributing

## Local setup

Use PHP 8.3+ (8.4+ for Symfony 8), Composer, SQLite support (`pdo_sqlite`), and
Node.js 22.14+. From the repository root:

```bash
composer install
npm ci
composer test
npm test
npm run build:prod
```

PHP tests use the fixture Symfony kernel and an in-memory SQLite database.
JavaScript tests use a simulated DOM; they do not replace manual browser checks.
This repository is a bundle, not a standalone demo website. Use a consuming Symfony
application for manual UI testing.

## Working on assets

```bash
npm run build:dev
npm run watch
```

The watcher rebuilds on asset/template changes. Reload the browser manually; the
build does not require the legacy WebSocket server. Before submitting changes,
run `npm run build:prod` and include the resulting public assets in your patch.

## Compatibility testing

CI resolves dependencies for Symfony 7.4 with PHP 8.3/8.4 and Symfony 8.0/8.1 with
PHP 8.4. The PHP 8.3 job uses DoctrineBundle 2.19; the PHP 8.4 jobs
explicitly test DoctrineBundle 3.3 with DoctrineMigrationsBundle 4. An additional
Symfony 7.4 job retains coverage for DoctrineMigrationsBundle 3. The committed lock file targets Symfony 7.4 and PHP 8.3. Tests executed
locally use your actual PHP binary, not the Composer platform setting.

In a **disposable checkout**, select a test combination:

```bash
# Run with PHP 8.4 for this example.
php tests/compatibility/configure.php '8.1.*' '3.3.*' '^4.0'
composer update --no-interaction
composer test
```

This helper changes `composer.json`, removes its simulated PHP platform and pins
the test's Symfony constraints. Do not commit that temporary manifest or lock file.
Use a clean test cache when switching dependency versions.

## Pull requests

Explain the problem, the intended behavior and how you tested it. Add regression
tests for behavioral fixes and update documentation when public behavior changes.
Record user-facing changes under `Unreleased` in [CHANGELOG.md](CHANGELOG.md).
For breaking changes, update [the upgrade guide](docs/upgrading.md); for API
deprecations, follow [DEPRECATIONS.md](DEPRECATIONS.md). Do not invent release
numbers or move unreleased changes into a release section before publication.
Run `composer validate --strict` and `git diff --check` before submitting.
Keep unrelated formatting and generated-file changes out of the patch.

Report issues through the repository's GitHub Issues page. Include a small
reproduction and version information; remove credentials and visitor data.


## Release version sources

Composer/Packagist derives the published bundle version from Git tags; do not add
an explicit `version` field to `composer.json`. The README and changelog identify
the planned release while it is unreleased. Keep the frontend package version in
`package.json` and the root entries of `package-lock.json` aligned with that target.

The current target is **2.0.0**. Before publishing, review compatibility changes,
finish the release checks and replace the changelog's unreleased heading with the
actual version and release date. Publishing a matching Git tag/release is a separate
step; editing these files does not publish the package.
