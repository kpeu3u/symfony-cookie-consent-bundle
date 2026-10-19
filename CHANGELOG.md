# Changelog

Notable user-facing changes are recorded here. Changes under **Unreleased** are
not a published release. See the [upgrade guide](docs/upgrading.md) for required
application changes and the [deprecation policy](DEPRECATIONS.md) for API status.

## Unreleased

No changes recorded yet.

## 2.1.0 — 2026-10-19

### Fixed

- Remove the unmaintained Symfony 8.0 CI job whose dependencies are blocked by
  Composer security advisories. Retain Symfony 7.4 and 8.1 coverage and advisory blocking.

### Added

- Optional `preference_storage: signed_cookie` retains accept, reject and custom
  vendor choices independently of the session, signed with the application secret.
  The default remains `session` for compatibility.
- `consent_version` and an automatic category/vendor fingerprint invalidate old
  signed choices. Invalid or expired cookies deny optional consent.
- Preference storage interface with session and signed cookie implementations,
  plus storage-neutral read and clear methods on the consent service.

### Upgrade considerations

- Existing 2.0.0 installations keep session storage by default; no configuration
  or database schema change is required for this release.
- Opting into signed cookie storage requires visitors to choose again. Keep
  `kernel.secret` stable across deployments and instances, and retain session
  support when using session-based CSRF protection.
- See the [2.1.0 upgrade instructions](docs/upgrading.md#upgrading-from-200-to-210)
  for configuration and deployment checks. No APIs are newly deprecated.

## 2.0.0 — 2026-10-01

### Added

- A separate `necessary_cookies` list displayed as always active in detailed
  settings, with translated labels and no optional-consent controls.

- `show_reject_all` configuration (default `true`) to control the reject-all
  button in both consent forms.

- Configurable `light`, `dark` and system-driven `auto` consent themes, with scoped
  CSS palettes for all banner positions. The default is `light`.

- Support for DoctrineMigrationsBundle `^4.0` alongside `^3.3`, with explicit CI coverage.

- Explicit DoctrineBundle `^2.19 || ^3.3` dependency and CI coverage for 3.3
  with Symfony 7.4, 8.0 and 8.1 on PHP 8.4.

- Support for Symfony 8.x on PHP 8.4+, alongside Symfony 7.4 on PHP 8.3+.
- Database logging of submitted vendor choices, with a working Doctrine repository
  and IPv6 address anonymization.
- Regression coverage for invalid submissions, saved choices, database persistence
  and JavaScript behavior; CI dependency matrices for Symfony 7.4, 8.0 and 8.1.
- Installation, configuration, integration, troubleshooting and contributor guides.

### Changed

- Preselect optional vendors in the settings form when no saved choice exists;
  actual permissions remain denied until submission, and saved choices are preserved.

- Categories with one vendor show only the category switch, retaining the vendor
  name and description. Categories with multiple vendors retain individual switches.

- Responsive consent cards with light/dark styling, readable translated category
  and vendor switches, a back button and scoped form themes. Both top and bottom
  positions now include fixed positioning and scrolling for small screens.

- Minimum requirements are now Symfony 7.4 and PHP 8.3; Symfony 8 requires PHP 8.4+.
- PHP and frontend dependencies have been updated. Asset development requires
  Node.js 22.14+; consuming applications use the included built assets.
- The banner script loads as a JavaScript module and uses the configured form action.
- Asset builds run without the development WebSocket server; Sass uses modules.
- The example recipe disables persistence until an application schema is ready.
  The bundle's `persist_consent` default remains `true`.

### Fixed

- Add explicit PHP 8.3 CI coverage and syntax checks; run all matrix jobs even
  when another job fails.

- HTTP 500 errors when detecting submitted forms; malformed requests now receive
  HTTP 400, and the update endpoint rejects non-POST requests with HTTP 405.
- CSRF configuration is passed to both forms; invalid detailed submissions do not
  mutate saved session choices.
- Submitted vendor-name changes are ignored, and unknown vendors deny consent.
- Duplicate JavaScript submissions, dialog dismissal and category toggles.
- Saved choices are prefilled when reopening settings.
- Consent fragment responses prevent shared caching; expired or missing consent
  cookies deny stale session choices, including when using a custom cookie name.

### Upgrade considerations

- Enabling persistence now performs actual database writes. Configure mapping and
  apply a reviewed migration, or explicitly set `persist_consent: false`.
- Reinstall public assets and review custom templates before deployment.
- Choices still depend on the Symfony session. Database logs do not restore them.
- No API is newly marked deprecated in 2.0.0. The dotted success
  event remains a supported compatibility alias; no removal release is scheduled.

## Earlier versions

Historical release boundaries have not been reconstructed here. Consult the Git
history and the documentation matching your installed reference. Older examples
are discussed in the upgrade guide without implying that each was a released API.
