# Troubleshooting

[Documentation index](index.md)

| Symptom | Check |
| --- | --- |
| Bundle extension or services cannot be found | Register the bundle, FrameworkBundle, TwigBundle and DoctrineBundle; clear the application cache after configuration changes. |
| Routes return 404 | Import `@CookieConsentBundle/config/routes.php`. Run `php bin/console debug:router` and look for the three `cookie_consent.*` routes. |
| Banner is unstyled or buttons do nothing | Run `php bin/console assets:install public`. Check CSS/JS requests for 404s, browser errors and CSP blocks. The script must load as a module. |
| A POST returns 400 | Use the rendered form, including its CSRF token and clicked submit button. Check that the session survives between rendering and submission. Do not cache another visitor's form. |
| Update returns 405 | The update action requires POST; a browser address-bar visit uses GET. |
| A POST returns 500 | Inspect the application log. If logging is enabled, verify the entity mapping, migration and database connection. `persist_consent: false` skips logging. |
| Banner reappears after reload | Check the consent cookie and session cookie. On local HTTP, set the consent cookie's `secure` option to `false` in development configuration. Check session expiry and storage. |
| Analytics does not start after acceptance | Twig ran before the visitor submitted. Reload on the success event, or initialize the permitted integration explicitly in application code. |
| Cookies are still present after rejection | The bundle does not delete other cookies or stop scripts already loaded. Implement vendor-specific cleanup and guard future loading. |
| Category/vendor names look generic | Update any template overrides and clear the application cache. Add category/vendor translation keys as shown in the integration guide; unknown identifiers use readable fallback labels. |
| Duplicate banners or events | Render one banner per page. Listen to one success event name, not both aliases. |

For a bug report, include PHP and Symfony versions, package version or commit,
a minimal configuration, reproduction steps and relevant redacted error output.
Do not include session cookies, CSRF tokens, database credentials or consent logs.
