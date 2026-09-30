# Changelog

Notable user-facing changes are recorded here. Changes under **Unreleased** are
not a published release. See the [upgrade guide](docs/upgrading.md) for required
application changes and the [deprecation policy](DEPRECATIONS.md) for API status.

## Unreleased — planned 2.0.0

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
- No API is newly marked deprecated in this unreleased update. The dotted success
  event remains a supported compatibility alias; no removal release is scheduled.

## Earlier versions

Historical release boundaries have not been reconstructed here. Consult the Git
history and the documentation matching your installed reference. Older examples
are discussed in the upgrade guide without implying that each was a released API.
