# Upgrading to 2.0.0

[Documentation index](index.md) · [Changelog](../CHANGELOG.md) · [Deprecations](../DEPRECATIONS.md)

Version 2.0.0 is being prepared and is not yet published. This guide is intended
for existing installations, including 1.0.3. Choose an available
release that contains these changes before updating a production application.

## Before updating

Identify the installed version or source reference and review your application's
custom templates, configuration and event listeners:

```bash
composer show kpeu3u/symfony-cookie-consent-bundle
```

Keep the application's current lock file and deployment artifact available for
rollback. Back up the database before applying schema changes. Test the upgrade
in a staging environment using the same session and database setup as production.

No previous-release number is assumed here. The older configuration forms below
appeared in repository documentation; they are not a claim that every form was
supported by a published version.

## Migration checklist

1. Check runtime compatibility: Symfony 7.4 requires PHP 8.2+; Symfony 8.x requires
   PHP 8.4+. The package allows both Symfony major versions.
2. Compare application configuration with the [reference](configuration.md).
   Categories belong under `consent_configuration.consent_categories`. Old
   `cookie_settings`, `name_prefix` and top-level category examples are obsolete.
3. Import the current routes. Replace any references to
   `cookie_consent.show_if_cookie_consent_not_set` with
   `cookie_consent.view_if_no_consent`.
4. Review template overrides. The forms now contain nested category/vendor models;
   old form blocks and assumptions about category cookies do not apply. Preserve
   the JavaScript selectors and CSRF widgets listed in the integration guide.
5. Decide whether to enable logging. `persist_consent: true` now performs real
   writes; installations that previously relied on the unfinished implementation
   need a working mapping and schema. Generate and review an application migration,
   or explicitly disable logging until ready.
6. Reinstall assets with `php bin/console assets:install public`, clear the
   application cache, and check the browser has received the new module.
7. Test accept, reject, individual vendor choices, revisiting settings and session
   expiry. Confirm that scripts follow the stored choice on a fresh page load.

The bundle uses one consent-state cookie plus session data. It no longer matches
old documentation describing separate category cookies and a `consent-key` cookie.
There is no automatic migration or deletion of those older cookies. If your
application created them, plan their removal using their original path/domain.

Both success event names remain available, but subscribe to only one:
`cookie-consent-form-submit-successful` is the documented name and
`cookie-consent.form-submit-successful` is the compatibility alias.

The repository lock file remains on Symfony 7.4 for PHP 8.2 compatibility. Composer
uses your application's dependency constraints when installing the bundle; the
library's lock file does not pin the Symfony version in consuming applications.


## Database migration before enabling logging

Version 2.0 performs real writes when `persist_consent` is `true` (the default).
An older installation may not have the required table even if this option was
already enabled. Complete the [Doctrine mapping setup](configuration.md#database-logging),
then generate a migration in the application:

```bash
php bin/console doctrine:migrations:diff
```

Review the generated SQL against the existing database, then apply it before
serving the upgraded application with logging enabled:

```bash
php bin/console doctrine:migrations:migrate
```

If the existing schema is already compatible, no new migration is needed. If you
are not ready to migrate, explicitly set `cookie_consent.persist_consent: false`.
Neither Composer updates nor asset installation create the consent tables.

## Old examples and current equivalents

| Older example or assumption | Current equivalent / action |
| --- | --- |
| `cookie_settings` / `name_prefix` | Use `consent_configuration.consent_cookie`; set its `name` explicitly if needed. There is no general cookie-name prefix option. |
| Top-level `consent_categories` | Use `consent_configuration.consent_categories`, with each category mapped to vendor names. |
| `cookie_consent.show_if_cookie_consent_not_set` | Use `cookie_consent.view_if_no_consent`. No alias is provided for the old example. |
| `same_site: none` | Only `lax` and `strict` are supported. |
| An absolute date or `+180 days` for `expires` | Use a PHP `DateInterval` duration such as `P180D`. |
| Separate category cookies | Use Twig helpers backed by the consent cookie and Symfony session. |
| `consent_form` / `required_cookies_category` template blocks | These blocks are absent; use the documented blocks or a full template override. |
| Dotted success event | Still supported; prefer the hyphenated event in new code. This is not a removal. |

## Deployment and verification

After selecting a published package constraint appropriate for your application,
resolve and test the update before deployment:

```bash
composer update kpeu3u/symfony-cookie-consent-bundle --with-all-dependencies
php bin/console assets:install public
php bin/console cache:clear
```

Composer respects your application's current version constraint; this command
does not automatically opt into an unreleased branch or a new major release.
Review the lock-file changes before deploying. Apply only the schema migration
you generated and reviewed for your application when enabling persistence.

Verify both a fresh session and a session with existing preferences. Test all three
submit actions, an invalid CSRF token, reopening settings, script gating after a
reload, and database logging if enabled. A rejection must not trigger optional
integrations on the next page load.

For rollback, restore the previous application artifact and lock file and reinstall
its assets. Database changes need a separately reviewed rollback plan; do not
assume that reverting the package safely reverses a migration.
