# Cookie Consent Bundle for Symfony

[![Latest Stable Version](https://poser.pugx.org/kpeu3u/symfony-cookie-consent-bundle/v/stable)](https://packagist.org/packages/kpeu3u/symfony-cookie-consent-bundle)
[![Total Downloads](https://poser.pugx.org/kpeu3u/symfony-cookie-consent-bundle/downloads)](https://packagist.org/packages/kpeu3u/symfony-cookie-consent-bundle)
[![License](https://poser.pugx.org/kpeu3u/symfony-cookie-consent-bundle/license)](https://packagist.org/packages/kpeu3u/symfony-cookie-consent-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/kpeu3u/symfony-cookie-consent-bundle.svg)](https://packagist.org/packages/kpeu3u/symfony-cookie-consent-bundle)
[![Symfony Version](https://img.shields.io/badge/Symfony-7.4%20%7C%208.x-black?logo=symfony)](#requirements)


A Symfony bundle for collecting cookie preferences through a dialog or banner.
Visitors can accept all configured vendors, reject them all, or save individual
choices. Your application can read those choices through Twig functions and
optionally record submissions in a database.

**The bundle does not automatically block scripts, remove third-party cookies, or
make an application legally compliant.** Load optional integrations only after
checking consent, and provide your own privacy information.

**Development version: 2.0.0 (unreleased).** See the [changelog](CHANGELOG.md)
for the changes being prepared. The stable-version badge above reports the
published Packagist release and may show an earlier version.

This documentation describes the current source branch. For an installed release,
use the documentation at the matching Git tag; unreleased changes may not yet be
available through a plain `composer require`.


## Requirements

| Component | Supported versions / requirements |
| --- | --- |
| Symfony | 7.4 or 8.x |
| PHP | 8.3+ for Symfony 7.4; 8.4+ for Symfony 8.x |
| Application bundles | FrameworkBundle, TwigBundle and DoctrineBundle |
| DoctrineBundle | 2.19.x on PHP 8.3+ or 3.3.x on PHP 8.4+; Composer allows `^2.19 \|\| ^3.3` |
| DoctrineMigrationsBundle | `^3.3` or `^4.0`; 4.x requires PHP 8.4+ |
| Session | Enabled and available on the consent routes |
| Browser | JavaScript modules, `fetch`, `FormData(form, submitter)`; native `dialog` for dialog mode |
| Node.js | 22.14+ for contributors rebuilding assets; not required to use the bundle |

DoctrineBundle is currently required even with logging disabled. A consent log
schema is only needed when `persist_consent` is enabled.

## Quick start

The following setup uses HTTPS and disables database logging initially. Merge the
snippets into existing configuration; do not replace your application's files.

### 1. Install and register

```bash
composer require kpeu3u/symfony-cookie-consent-bundle
```

Check `config/bundles.php` and register any missing entries:

```php
return [
    // Keep your other bundles here.
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    CookieConsentBundle\CookieConsentBundle::class => ['all' => true],
];
```

The repository contains a [recipe example](recipe/), but its presence does not mean
Symfony Flex has applied it. Verify the following configuration explicitly.

### 2. Enable the framework features

```yaml
# config/packages/framework.yaml
framework:
    form: true
    csrf_protection: true
    session: true
    translator:
        default_path: '%kernel.project_dir%/translations'
    fragments: true
    assets: true
```

Keep your application's existing `framework.secret` and Doctrine connection
configuration. Avoid stateless routes or firewalls for pages using the banner.

### 3. Import the routes

```yaml
# config/routes/cookie_consent.yaml
cookie_consent:
    resource: '@CookieConsentBundle/config/routes.php'
```

### 4. Choose your categories and vendors

```yaml
# config/packages/cookie_consent.yaml
cookie_consent:
    persist_consent: false
    position: dialog
    consent_configuration:
        consent_categories:
            analytics:
                - google_analytics
            marketing:
                - facebook_pixel
```

These names are examples, not integrations installed by the bundle. Replace them
with the services your application actually uses. Every configured vendor is
optional and is rejected by “Reject all”; there is no reserved “necessary” category.

The consent cookie is HTTPS-only by default. For local HTTP development only:

```yaml
# config/packages/dev/cookie_consent.yaml
cookie_consent:
    consent_configuration:
        consent_cookie:
            secure: false
```

### 5. Prepare the database if logging is enabled

**With `persist_consent: true`, the database schema must be ready before visitors
submit the banner.** This is the bundle's default when the option is omitted.
The quick-start example above explicitly uses `false`, so it needs no consent
log tables and you can skip this step while logging remains disabled.

To enable logging:

1. Configure the [Doctrine entity mapping and MigrationsBundle](docs/configuration.md#database-logging).
2. Generate a migration in your application:

   ```bash
   php bin/console doctrine:migrations:diff
   ```

3. Review the generated migration, then apply it to the target database:

   ```bash
   php bin/console doctrine:migrations:migrate
   ```

4. Set `persist_consent: true` only after the migration succeeds.

Composer installation and `assets:install` do **not** create the tables. A missing
`cookieconsent_log` table causes submissions to fail with HTTP 500 when logging is
enabled. If the schema already exists, check whether an upgrade needs a migration;
do not create an empty migration just to repeat installation steps.

### 6. Install the included assets

```bash
php bin/console assets:install public
```

Repeat this command after upgrading the bundle. No npm build is needed in the
consuming application.

### 7. Render the banner and guard optional scripts

Add the stylesheet in the head of your base layout and render the banner once in
the body. Its template loads the JavaScript module automatically.

```twig
{# Inside your base layout's stylesheets block #}
{% include '@CookieConsent/cookie_consent_styling.html.twig' %}

{# Inside the body, for example before </body> #}
{{ render(path('cookie_consent.view_if_no_consent', {locale: app.request.locale})) }}

{% if cookieconsent_isVendorAllowedByUser('google_analytics', 'analytics') %}
    {# Put your actual analytics script here. #}
{% endif %}
```

Place optional scripts and embeds behind these checks, including integrations
loaded through tag managers. The bundle only records choices.

For server-rendered integrations, reload after saving consent. Add this listener
once in your application's JavaScript:

```javascript
document.addEventListener('cookie-consent-form-submit-successful', () => {
    window.location.reload();
});
```

### 8. Verify the integration

Open a fresh browser session. Confirm that the banner appears, each submit action
returns HTTP 201, and the banner stays hidden after reloading. Check that optional
scripts are absent before consent and after rejection. Use your browser's Network
and Cookies panels to verify the behavior of your own integrations.

## Appearance examples

These screenshots show light and dark styling from an earlier version. They are
visual references, not screenshots of the current 2.0 forms. The current bundle
includes minimal styling; matching these designs requires application CSS and
potentially template overrides. Use `theme: light`, `dark` or `auto` for the current palettes; these do not
reproduce the older form layout.

| Light appearance | Dark appearance |
| --- | --- |
| ![Earlier cookie consent form with a white background and dark text](docs/light_theme.png) | ![Earlier cookie consent form with a black background and light text](docs/dark_theme.png) |

See [styling and assets](docs/integration.md#styles-and-assets) for customization.

## Common settings

Configure these in `config/packages/cookie_consent.yaml` under `cookie_consent`.

| Setting | Default | What it controls |
| --- | --- | --- |
| [`theme`](docs/configuration.md#theme) | `light` | Light, dark or automatic system-based colors. |
| [`position`](docs/configuration.md#position) | `dialog` | Modal dialog, fixed `bottom` banner, or `top` banner with application CSS. |
| [`read_more_route`](docs/configuration.md#privacy-policy-link) | `null` | Optional route name for the privacy-policy link. |
| [`persist_consent`](docs/configuration.md#database-logging) | `true` | Database logging; requires mapping and a migrated schema. |
| [`form_action`](docs/configuration.md#form-submission-route) | `cookie_consent.update` | Route name used to submit the forms. |
| [`csrf_protection`](docs/configuration.md#csrf-protection) | `true` | CSRF validation for consent submissions. |
| [`consent_configuration.consent_cookie`](docs/configuration.md#cookie-options) | See reference | Cookie name, lifetime, domain and security flags. |
| [`consent_configuration.consent_categories`](docs/configuration.md#categories-and-vendors) | Empty map | Optional vendors grouped by application-defined category. |

For example, to display a bottom banner and link to your privacy-policy route:

```yaml
cookie_consent:
    position: bottom
    theme: auto
    read_more_route: app_privacy_policy # Replace with your existing route name.
```

Merge this into your existing configuration so your category and logging settings
remain in place. See the [position guide](docs/configuration.md#position) for all
three modes and the CSS needed for `top`.

## Documentation

- [Configuration reference and database logging](docs/configuration.md)
- [Twig helpers, settings page, events, customization and caching](docs/integration.md)
- [Troubleshooting](docs/troubleshooting.md)
- [Upgrading an existing installation](docs/upgrading.md)
- [Changelog](CHANGELOG.md)
- [Deprecation policy and status](DEPRECATIONS.md)
- [Contributing and running the tests](CONTRIBUTING.md)

## How preferences are stored

A cookie named `consent` by default records `full-consent`, `no-consent` or
`custom-consent`. Detailed vendor choices are stored in the Symfony session.
Both the cookie and session choices must be available for the bundle to recognize
an existing choice. Expiring either causes the banner to appear again.

The default cookie lifetime is 180 days; it does **not** extend the session lifetime.
Database logs are an audit trail and are not used to restore expired sessions.
Logging defaults to `true`, while the quick start explicitly disables it until the
schema is ready. See [database logging](docs/configuration.md#database-logging).

## License

[MIT](LICENSE).
