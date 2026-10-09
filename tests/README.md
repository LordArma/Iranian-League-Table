# Tests

| Layer | Where | Run | Needs |
|---|---|---|---|
| Unit | `tests/unit/` | `vendor/bin/phpunit` | PHP 7.4+, `composer install` (WordPress is stubbed in `tests/bootstrap.php`) |
| Integration | `tests/integration/` | `npm run env:start && npm run test:integration` (and `test:integration:multisite`) | Docker + `@wordpress/env` |
| End-to-end | `tests/e2e/` | see `tests/e2e/README.md` | a running WordPress + Playwright |
| Snapshot diff | `tests/render-snapshots.php` | `php tests/render-snapshots.php /tmp/out` | PHP only |

Remote data never comes from the network in unit/integration tests: `tests/fixtures/*.json` are real Varzesh3 responses.

Integration tests without wp-env: point `WP_TESTS_DIR` at a WordPress PHPUnit test library (e.g. the
`wp-phpunit/wp-phpunit` package), set `WP_PHPUNIT__TESTS_CONFIG=tests/integration/wp-tests-config.php` and the
`ILT_DB_*` / `ILT_WP_DIR` env vars it reads, then run `vendor/bin/phpunit -c phpunit-integration.xml.dist`
(`WP_MULTISITE=1` for multisite). The test database is wiped: never use a real site's database.

Other checks: `vendor/bin/phpcs` (WordPress + PHP 7.4 compatibility), `php bin/i18n.php` (translations complete),
`npm run build` (block build must match `src/`).
