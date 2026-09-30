# Configuration

[Documentation index](index.md) · [Quick start](../README.md#quick-start)

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

### Cookie options

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
