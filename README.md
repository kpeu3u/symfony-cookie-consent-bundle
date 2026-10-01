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

**Create the configuration files in steps 3 and 4 manually if they are missing.**
This also applies when installing `dev-develop`: Composer installs the package,
but does not copy its configuration examples into your application.

The repository's [recipe example](recipe/) is not an automatically discovered
Flex recipe. Flex obtains recipes from configured recipe repositories, not from
a `recipe/` directory inside an installed package. Automatic configuration
requires a published recipe in `symfony/recipes-contrib` or a custom Flex endpoint
configured in the consuming application. Setting `allow-contrib: true` alone does
not publish or register this local example.

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

Create `config/routes/cookie_consent.yaml` (and its parent directory if needed):

```yaml
# config/routes/cookie_consent.yaml
cookie_consent:
    resource: '@CookieConsentBundle/config/routes.php'
```

### 4. Choose your categories and vendors

Create `config/packages/cookie_consent.yaml`:

```yaml
# config/packages/cookie_consent.yaml
cookie_consent:
    persist_consent: false
    position: dialog
    theme: auto
    consent_configuration:
        consent_categories:
            analytics:
                - google_analytics
            marketing:
                - facebook_pixel
```

These names are examples, not integrations installed by the bundle. Replace them
with the services your application actually uses. Every configured vendor is
optional and is rejected by “Reject all”. Describe necessary cookies separately
with [`necessary_cookies`](docs/configuration.md#necessary-cookies); there is no
reserved category name that automatically changes consent behavior.

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

Merge these examples into your existing base layout (usually
`templates/base.html.twig`); do not add a second `<head>` or `<body>`.

**Inside `<head>`:** include the bundle stylesheet in your existing `stylesheets`
block. Optional scripts belong where their integration requires them. For example,
if you place Google Analytics in `<head>`, wrap its entire loading and
initialization code in the consent check there:

```twig
{# Inside <head>, in your existing stylesheets block #}
{% include '@CookieConsent/cookie_consent_styling.html.twig' %}

{# Also inside <head>, where your analytics integration belongs #}
{% if cookieconsent_isVendorAllowedByUser('google_analytics', 'analytics') %}
    {# Put your complete Google Analytics loading and initialization code here. #}
{% endif %}
```

**Inside `<body>`:** render the banner once, for example immediately before
`</body>`. Its template loads the bundle's JavaScript module automatically:

```twig
{{ render(path('cookie_consent.view_if_no_consent', {locale: app.request.locale})) }}
```

The consent check does not require a specific location: it surrounds the relevant
script or embed in `<head>` or `<body>`. Guard optional integrations loaded through
tag managers too. The bundle records choices; it does not block scripts you load
outside these checks.

**Optional reload after saving:** Twig checks run on the server when the page is
rendered. Reloading after a successful submission lets those checks use the new
choice immediately. Add this listener once to your application's main JavaScript
file:

```javascript
document.addEventListener('cookie-consent-form-submit-successful', () => {
    window.location.reload();
});
```

Alternatively, put the listener in a `<script>` element before `</body>` in your
base layout, if your Content Security Policy permits inline scripts. Use one
location, not both, and register the listener before visitors can submit the form.

You can omit this listener: choices are still saved and the banner closes. Your
Twig-guarded integrations will use the new choice on the next page load. Starting
or stopping integrations immediately without reloading requires application-specific
JavaScript; rejecting cookies does not automatically stop scripts already loaded
or delete their cookies.

### 8. Verify the integration

Open a fresh browser session. Confirm that the banner appears, each submit action
returns HTTP 201, and the banner stays hidden after reloading. Check that optional
scripts are absent before consent and after rejection. Use your browser's Network
and Cookies panels to verify the behavior of your own integrations.

## Appearance examples

These screenshots show the current detailed settings in a real application, with
Bulgarian translations. Both palettes include category and vendor switches,
responsive action buttons and keyboard focus styles. Select `theme: light`,
`dark` or `auto`; no custom form theme is needed.

| Light appearance | Dark appearance |
| --- | --- |
| ![Cookie consent settings with a white background and dark text](docs/light_theme.jpg) | ![Cookie consent settings with a black background and light text](docs/dark_theme.jpg) |

See [styling and assets](docs/integration.md#styles-and-assets) for customization.

## Common settings

Configure these in `config/packages/cookie_consent.yaml` under `cookie_consent`.

| Setting | Default | What it controls |
| --- | --- | --- |
| [`theme`](docs/configuration.md#theme) | `light` | Light, dark or automatic system-based colors. |
| [`show_reject_all`](docs/configuration.md#reject-all-button) | `true` | Show or omit the reject-all button in both forms. |
| [`necessary_cookies`](docs/configuration.md#necessary-cookies) | Empty map | Names and descriptions of necessary cookies, shown as always active. |
| [`position`](docs/configuration.md#position) | `dialog` | Modal dialog, fixed `bottom` banner, or fixed `top` banner. |
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
    show_reject_all: true
```

Merge this into your existing configuration so your category and logging settings
remain in place. See the [position guide](docs/configuration.md#position) for all
three modes and their positioning.

For multilingual necessary-cookie descriptions, use translation keys for `name`
and `description`, and define them in your application's
`translations/CookieConsentBundle.<locale>.yaml` files. See the
[English/Bulgarian example](docs/configuration.md#translating-necessary-cookies).

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
