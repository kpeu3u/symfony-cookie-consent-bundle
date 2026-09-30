# Configuration

[Documentation index](index.md) · [Quick start](../README.md#quick-start)

## Where to configure the bundle

Put bundle settings under `cookie_consent` in
`config/packages/cookie_consent.yaml` in the consuming application. Merge the
examples below into that section rather than adding multiple `cookie_consent`
keys in one YAML file. Omitted options use their defaults.

- [Full example and option summary](#full-example)
- [Position: dialog, bottom or top](#position)
- [Theme: light, dark or auto](#theme)
- [Reject-all button](#reject-all-button)
- [Privacy-policy link](#privacy-policy-link)
- [Form submission route](#form-submission-route)
- [CSRF protection](#csrf-protection)
- [Cookie options](#cookie-options)
- [Necessary cookies](#necessary-cookies)
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
    necessary_cookies: {}
    position: dialog
    theme: light
    show_reject_all: true
    persist_consent: true
    form_action: cookie_consent.update
    read_more_route: null
    csrf_protection: true
```

| Option | Default | Meaning |
| --- | --- | --- |
| `position` | `dialog` | Accepts `dialog`, `bottom` and `top`. All positions include bundled styling. |
| `theme` | `light` | `light`, `dark` or `auto` (follows the browser’s color-scheme preference). |
| `show_reject_all` | `true` | Show the reject-all button in both the initial and detailed forms. |
| `necessary_cookies` | Empty map | Informational list of necessary cookies, displayed as always active with no controls. |
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
| `dialog` | Wraps the banner in a native `<dialog class="cookie-consent-dialog">`. JavaScript opens it with `showModal()`, placing it above the page with a backdrop. | Browser modal positioning, with a bundled maximum width of `640px`, constrained to the viewport. |
| `bottom` | Renders a regular `.cookie-consent.cookie-consent--bottom` element, without a modal or backdrop. | Fixed 16px from the bottom, centered, at most 720px wide. |
| `top` | Renders a regular `.cookie-consent.cookie-consent--top` element, without a modal or backdrop. | Fixed 16px from the top, centered, at most 720px wide. |

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
The card adapts to narrow screens and scrolls internally when necessary. A fixed
banner can overlap page content; adjust its stacking order for your site's layout.

### Top banner

```yaml
cookie_consent:
    position: top
```

Load the bundle stylesheet as for the bottom banner. No additional positioning
CSS is required. Both fixed positions use `--cc-z-index: 10000000`; load application
overrides after the bundle stylesheet if your fixed header needs different
spacing or stacking. In all modes, optional scripts still need consent checks.

## Theme

Set `theme` independently of `position`. All three themes work with `dialog`,
`bottom` and `top`.

```yaml
cookie_consent:
    position: dialog
    theme: auto
```

| Value | Appearance |
| --- | --- |
| `light` | Light background and dark text. This is the default. |
| `dark` | Dark background and light text, regardless of system preferences. |
| `auto` | Uses the CSS `prefers-color-scheme: dark` media query; otherwise uses light colors. Updates when the browser preference changes. |

Load the bundled stylesheet and re-run `php bin/console assets:install public`
after upgrading. No extra JavaScript or theme cookie is required. The setting
changes backgrounds, text, links, buttons, checkbox accents and focus outlines
inside the consent UI only; it does not recolor the host page or change consent.
There is no visitor-facing theme toggle.

To customize colors, load your application CSS after the bundle stylesheet:

```css
.cookie-consent[data-cookie-consent-theme="dark"],
.cookie-consent-dialog[data-cookie-consent-theme="dark"] {
    --cc-surface: #18212f;
    --cc-text: #f5f7fa;
    --cc-link: #b4d0ff;
    --cc-control-background: #283548;
    --cc-control-text: #f5f7fa;
    --cc-control-border: #a5b4c8;
    --cc-focus: #b4d0ff;
}
```

Override both the dialog and its inner banner to keep their colors aligned.
For `auto`, target `data-cookie-consent-theme="auto"` and put dark overrides inside
`@media (prefers-color-scheme: dark)`. If you replace the full Twig template,
preserve the `data-cookie-consent-theme` attributes on those elements and pass the
`theme` template variable through. See the [current screenshots](integration.md#light-and-dark-appearance-references)
for both palettes.

## Reject-all button

By default, both forms include a “Reject all” button. To remove it from both the
initial banner and the detailed settings form:

```yaml
cookie_consent:
    show_reject_all: false
```

The button is omitted from the Symfony forms, not hidden with CSS. “Accept all”,
the settings toggle and “Save choices” remain available. Visitors can still save
all vendors unchecked in the detailed form. This setting does not grant consent
or change existing preferences. Restore `true` to display both reject buttons.
Custom templates that access the `reject_all` form field directly should check
whether it exists before rendering it.

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

## Necessary cookies

Use `necessary_cookies` for cookies or services that your application needs
independently of the optional consent choices. This is a separate top-level option,
not a category inside `consent_configuration.consent_categories`.

```yaml
cookie_consent:
    necessary_cookies:
        session:
            name: 'Session cookie'
            description: 'Keeps your session available while you use the site.'
        preferences:
            name: 'Cookie preferences'
            description: 'Remembers the cookie choices you have saved.'
```

Replace these examples with the cookies your application actually uses. Each entry
requires non-empty `name` and `description` strings; its key is an identifier for
configuration, not a command to create a cookie. An empty list (the default) hides
the section. Names and descriptions can also be translation keys in the
`CookieConsentBundle` domain. They are escaped when rendered, so HTML is displayed
as text.

The list appears in detailed settings under the translated “Required cookies”
heading with an “Always active” label. There are no checkboxes. Accept, reject and
save actions do not change this list. The list is not submitted as optional consent
and is not added to the consent log records.

This setting describes necessary cookies; it does not create them, inspect the
browser, prevent their deletion or keep a session alive. Your application owns
that behavior. Do not put necessary cookies behind the optional category/vendor
Twig helpers: those helpers continue to report only the configured optional choices.
Adding this list does not make `isCategoryAllowedByUser('necessary')` return true.

The section can be customized through the `necessary_cookies` Twig block. Its
heading, explanation and status label use `cookie_consent.required_cookies.title`,
`cookie_consent.required_cookies.description` and
`cookie_consent.required_cookies.always_active` translation keys.

### Translating necessary cookies

For a multilingual site, store translation **keys** in configuration instead of
literal text. The configuration stays the same for every language:

```yaml
# config/packages/cookie_consent.yaml
cookie_consent:
    necessary_cookies:
        session:
            name: app.cookies.session.name
            description: app.cookies.session.description
```

Define the keys in your application's translation directory using the exact
`CookieConsentBundle` domain (including capitalization):

```yaml
# translations/CookieConsentBundle.en.yaml
app.cookies.session.name: 'Session cookie'
app.cookies.session.description: 'Keeps your session available while you use the site.'
```

```yaml
# translations/CookieConsentBundle.bg.yaml
app.cookies.session.name: 'Бисквитка за сесията'
app.cookies.session.description: 'Поддържа сесията ви, докато използвате сайта.'
```

Pass the current page locale when rendering the fragment:

```twig
{{ render(path('cookie_consent.view_if_no_consent', {locale: app.request.locale})) }}
```

Use the same `locale` parameter with `cookie_consent.view` on a settings page.
Without this parameter the controller uses the fragment request's locale; pass it
explicitly so a localized page and its banner stay aligned. There is no separate
language selector inside the banner.

The section heading, explanation and “Always active” status already have bundled
translations. Override their `cookie_consent.required_cookies.*` keys in the same
application files if needed. Custom names and descriptions follow Symfony's
translation fallback configuration; if a key is missing in every applicable
catalogue, it is displayed as-is. Literal text remains supported for single-language
sites. Clear the application cache after adding translation files if necessary.

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
