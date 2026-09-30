# Deprecations

## Current status

The current unreleased update introduces **no formal API deprecations** and emits
no bundle-specific deprecation notices. A compatibility alias, an old example or
an unfinished feature is not automatically a deprecated public API.

| Item | Current status | Guidance |
| --- | --- | --- |
| `cookie-consent.form-submit-successful` | Supported compatibility alias; no removal scheduled | Prefer `cookie-consent-form-submit-successful` in new code. Subscribe to only one, because both fire for a submission. |
| `BrowserCookie` and `CookieSettings` entities | Still present and mapped; unused by the current submission flow | Do not remove existing tables solely because they are described as legacy. Review schema changes in your application. |
| Old configuration and route examples | Obsolete documentation, not a supported compatibility layer | Follow the mappings in the upgrade guide. |
| Consent-cookie `enabled` setting | Exposed by the configuration tree but not honored at runtime | Treat this as a known limitation, not a deprecation. See the configuration guide. |

## Policy for future changes

When deprecating a supported API, document:

- The exact class, method, option, event or template block affected.
- The release introducing the deprecation.
- The replacement and a migration example.
- Whether a runtime notice is emitted and how users can find affected code.
- The planned removal release, or explicitly state that it is not yet scheduled.

List the change in the changelog's `Deprecated` section and explain migration in
the upgrade guide. When removal happens, record it under `Removed` and name the
first release without the old API. Avoid announcing removal dates or versions
before they have been decided.

See [CHANGELOG.md](CHANGELOG.md) and [the upgrade guide](docs/upgrading.md).
