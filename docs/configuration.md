# Configuration

[Documentation index](index.md) · [Quick start](../README.md#quick-start)

## Where to configure the bundle

Put bundle settings under `cookie_consent` in
`config/packages/cookie_consent.yaml` in the consuming application. Merge the
examples below into that section rather than adding multiple `cookie_consent`
keys in one YAML file. Omitted options use their defaults.

- [Full example and option summary](#full-example)
- [Position: dialog, bottom or top](#position)
- [Privacy-policy link](#privacy-policy-link)
- [Form submission route](#form-submission-route)
- [CSRF protection](#csrf-protection)
- [Cookie options](#cookie-options)
- [Categories and vendors](#categories-and-vendors)
- [Database logging](#database-logging)
- [Inspecting the effective configuration](#inspecting-the-effective-configuration)

## Full example

The following shows the defaults, except for the example categories (the default
category list is empty). Configure only the vendors your application actually uses.

```yaml
# config/packages/cookie_consent.yaml
cookie_consent:
    consent_configuration:
        consent_cookie:
            name: consent
            expires: P180D
            domain: null
            secure: true
            http_only: true
            same_site: lax
        consent_categories:
            analytics:
                - google_analytics
            marketing:
                - facebook_pixel
    position: dialog
    persist_consent: true
    form_action: cookie_consent.update
    read_more_route: null
    csrf_protection: true
```

| Option | Default | Meaning |
| --- | --- | --- |
| `position` | `dialog` | Accepts `dialog`, `bottom` and `top`. `top` needs application CSS for positioning. |
| `persist_consent` | `true` | Write submitted vendor choices to the database. Requires the schema described below. |
| `form_action` | `cookie_consent.update` | Symfony route **name**, not a URL. The route must accept the bundle's POST form payload. Keep the default unless replacing the endpoint. |
| `read_more_route` | `null` | Optional privacy-policy route name. The template generates it without route parameters. |
| `csrf_protection` | `true` | Enable CSRF validation on both forms. Requires Symfony's CSRF feature and a session. |
| `consent_configuration.consent_categories` | Empty map | Category identifiers mapped to lists of vendor identifiers. |

## Position

`position` controls the banner's presentation. It does not change the available
choices, cookie lifetime or logging. The default is `dialog`; allowed values are
exactly `dialog`, `bottom` and `top`.

| Value | Markup and behavior | Included positioning |
| --- | --- | --- |
| `dialog` | Wraps the banner in a native `<dialog class="cookie-consent-dialog">`. JavaScript opens it with `showModal()`, placing it above the page with a backdrop. | Browser modal positioning, with a bundled maximum width of `80vw`. |
| `bottom` | Renders a regular `.cookie-consent.cookie-consent--bottom` element, without a modal or backdrop. | Fixed to the bottom of the viewport, full width. |
| `top` | Renders a regular `.cookie-consent.cookie-consent--top` element, without a modal or backdrop. | No dedicated top-positioning CSS is included yet; add it in your application. |

### Modal dialog

```yaml
cookie_consent:
    position: dialog
```

Use this for a modal presentation. Interaction with the rest of the page is blocked
while the native dialog is open. Browser dismissal (for example Escape) does not
save a consent choice. The visitor remains undecided until a form is submitted.

### Bottom banner

```yaml
cookie_consent:
    position: bottom
```

Load the bundle stylesheet using
`{% include '@CookieConsent/cookie_consent_styling.html.twig' %}`. The banner stays
at the bottom while the visitor scrolls. The rest of the page remains interactive.
The supplied CSS is minimal: adapt its background, padding and stacking order to
your site's layout. A fixed banner can overlap page content.

### Top banner

```yaml
cookie_consent:
    position: top
```

Add application CSS **after** the bundle stylesheet to make it a fixed top banner:

```css
.cookie-consent--top {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    padding: 1rem;
    background: #fff;
    color: #111;
    box-shadow: 0 2px 12px rgb(0 0 0 / 15%);
}
```

Adjust the example's stacking order and spacing around any fixed site header.
Without your CSS, `top` only changes the class name; it does not move the banner
to the top. In all modes, optional scripts still need consent checks.

## Privacy-policy link

`read_more_route` is a Symfony route name, not a URL or path. The default `null`
hides the link. To show a link to an existing application route:

```yaml
cookie_consent:
    read_more_route: app_privacy_policy
```

The route must exist and must not require parameters without defaults. The label
comes from `cookie_consent.read_more` in the `CookieConsentBundle` translation
domain. For an external URL or custom route parameters, override the template's
`read_more` block instead. See [template customization](integration.md#template-overrides).

## Form submission route

Keep the default for the bundle's built-in handling:

```yaml
cookie_consent:
    form_action: cookie_consent.update
```

The form action is generated from this route name. JavaScript uses that generated
URL, so an application route prefix does not require changing the JavaScript.
Setting another name does not create an endpoint: your route and controller must
already exist and handle the form payload, CSRF validation, session preferences
and response cookies. A missing route name causes rendering to fail.

## CSRF protection

Protection is enabled by default for both forms:

```yaml
cookie_consent:
    csrf_protection: true
```

Also enable `framework.csrf_protection` and sessions in the application. Render
and submit the generated token with the form; custom templates must preserve it.
Invalid tokens return HTTP 400. Keep protection enabled for normal browser use.
Setting the bundle option to `false` removes this validation but does not remove
the session requirement for storing preferences.

## Cookie options

All options below are under `consent_configuration.consent_cookie`.

| Option | Default | Meaning |
| --- | --- | --- |
| `name` | `consent` | Consent-state cookie name. Changing it makes existing visitors appear undecided. |
| `expires` | `P180D` | PHP `DateInterval` duration added to the submission time, e.g. `P30D` or `P1Y`. A date or `+180 days` string is not supported. |
| `domain` | `null` | Host-only cookie by default. Set an explicit domain only if needed; session sharing must also be configured separately. |
| `secure` | `true` | Send the cookie over HTTPS. Use `false` only in local HTTP configuration. |
| `http_only` | `true` | Prevent JavaScript access to the consent cookie. Use the events and server-side helpers instead. |
| `same_site` | `lax` | Accepts `lax` or `strict`; `none` is not supported. |

The cookie path is `/` and is not configurable. The configuration tree exposes an
`enabled` flag for this node, but runtime cookie creation does not honor it; do not
use `consent_cookie: false` as a way to disable the banner. Omit the Twig fragment
where the banner should not appear.

## Categories and vendors

Identifiers are application-defined strings, not a built-in vendor catalog. Keep
vendor names unique within each category and stable between releases. All vendors
start unchecked; “Accept all” selects all configured vendors and “Reject all”
clears them all. A category helper returns true only if every stored vendor in
that category is allowed. Unknown categories and vendors return false.

There is no special treatment for a category called `functional` or `necessary`.
Keep essential application behavior separate from the optional integrations you
guard with consent checks.

Existing session choices are not versioned against configuration changes. When
adding vendors, use per-vendor checks so a newly added vendor remains disallowed
until selected. A category-wide check on an old session is not a reliable gate for
a newly introduced vendor. To require a new choice from everyone, change the
consent cookie name as part of the rollout.

## Database logging

**Database setup is required before using `persist_consent: true`.** The default
is `true`; omitting this option does not disable logging. With an explicit
`persist_consent: false`, no consent-log schema is needed.

Installing or updating the Composer package does not run database migrations.
Complete the mapping and migration steps below before enabling logging or serving
requests with it enabled. For an existing installation, inspect the schema changes
required by the new version and apply a migration if necessary.

DoctrineBundle remains a runtime dependency when logging is disabled. The bundle
accepts DoctrineBundle `^2.19 || ^3.3`. DoctrineBundle 3.3 requires PHP 8.4 and
Doctrine DBAL 4; DoctrineBundle and DBAL have separate version numbers. This
package does not require DoctrineBundle 4.

DoctrineMigrationsBundle is a separate dependency and supports `^3.3 || ^4.0`.
Its 4.x line requires PHP 8.4, DoctrineBundle 3.x and DBAL 4. You do not need to
downgrade an application already using DoctrineMigrationsBundle 4. With
`persist_consent: false`, submissions skip database writes. With it enabled, a
missing table or database failure causes the submission to fail with HTTP 500.

Register DoctrineMigrationsBundle if your application does not already use it:

```php
// Entry inside config/bundles.php
Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
```

Merge this mapping with your existing Doctrine configuration:

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        mappings:
            CookieConsentBundle:
                is_bundle: true
                type: attribute
                dir: src/Entity
                prefix: CookieConsentBundle\Entity
```

With multiple entity managers, put the mapping under the manager that owns the
consent records. If your application already discovers these entities through
bundle auto-mapping, do not register a duplicate mapping.

Generate and inspect a migration in your application before running it:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

Mapping the entire entity directory also includes the legacy `BrowserCookie` and
`CookieSettings` entities. The migration may therefore include
`cookieconsent_cookies` and `cookieconsent_settings` as well as `cookieconsent_log`.
The current submission flow writes only to `cookieconsent_log`. Review generated
changes against your application's existing schema; do not apply the repository's
historical SQLite-specific migration to another database.

Then set `cookie_consent.persist_consent: true`.

Each submission writes one log record per category, containing vendor choices,
a timestamp, a random identifier retained in the session, and an anonymized IP.
IPv4 logging masks the last octet; IPv6 logging retains the first 48 bits. An
unavailable address is recorded as `unknown`. The bundle does not set a separate
`consent-key` cookie or restore preferences from these records.

There is no built-in retention policy, cleanup command or log viewer. Manage
retention and access in the consuming application.


## Inspecting the effective configuration

Run these commands in the consuming application:

```bash
# Available options and defaults
php bin/console config:dump-reference cookie_consent

# Merged application configuration (including environment overrides)
php bin/console debug:config cookie_consent --env=dev
```

Use the environment you are actually testing. For example,
`config/packages/dev/cookie_consent.yaml` only overrides development settings.
After deployment or if old settings persist, clear the matching environment's
cache with `php bin/console cache:clear --env=dev` (or `--env=prod`).

For a visual position check, open a session without saved consent or render
`cookie_consent.view` on your settings page: `view_if_no_consent` deliberately
renders nothing for a visitor whose choice is already saved.
