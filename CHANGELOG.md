# Changelog

All notable changes to `methorz/mezzio-hexagonal-skeleton` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.1.0] - 2026-10-22

### Added
- Generated apps render uncaught exceptions as JSON through Mezzio's `ErrorHandler`, which is now always piped. Exceptions with a `getStatusCode()` method keep their 4xx/5xx status, so domain errors such as "article not found" return 404 instead of 500 without methorz/http-problem-details. Server errors are written to the PHP error log and only show details in development mode. methorz/http-problem-details still takes over when it is installed.
- Hexagonal projects store articles in `data/articles.json` through the new `FileArticleRepository`, so the Article API works across requests without a database. `InMemoryArticleRepository` remains available for tests, `DbArticleRepository` for swift-db.
- Minimal projects ship a `HealthCheckHandler` test.
- Every project ships a test that boots the real container, pipeline and routes, so wiring errors fail `composer test`.

### Changed
- Environment settings moved from `config/autoload/{development,production,testing}.global.php` to `config/environment/{development,testing,production}.php`. `config.php` loads exactly one of them based on `APP_ENV` and falls back to `production`.

### Fixed
- `debug` was enabled in every environment, production included: all three environment files matched the `*.global.php` pattern and `testing.global.php`, loaded last, set `debug => true`. Error responses in production therefore contained stack traces.
- The production Docker image can be built: the build context is the project root, so the Dockerfile can copy `backend/`. A `.dockerignore` keeps vendor, runtime data and development-mode config out of the image. The obsolete `version` key is removed from `docker-compose.prod.yml`.
- Production containers no longer time out at startup: the image installs the MySQL client that `start.sh` uses to wait for the database, and `start.sh` keeps the image's optimized autoloader instead of regenerating it with dev classes.
- `backend/config/development.config.php` is no longer tracked, so generated projects only run in development mode after `composer development-enable` (which `setup.sh` runs).
- README examples for the database configuration and the project layout match the generated files.

### Removed
- The unused `updateHandlerForDTO()` installer step and its templates (`Resources/handlers`, `request`, `response`, `config-provider`). The minimal templates already generate the http-dto variant.
- Stale `MezzioInstaller` exclusions from the PHP_CodeSniffer configuration.

## [3.0.0] - 2026-10-22

### Changed
- **BREAKING**: PHP requirement raised to `~8.5.12` (patched for php2shell: CVE-2026-109618, CVE-2026-109619). Docker images are pinned to `php:8.5.12` and Composer 2.10.3, with php2shell defense-in-depth settings in production.
- Updated Mezzio, Laminas, Symfony and tooling constraints. Optional MethorZ packages use tagged releases instead of `dev-main` (http-dto ^2.2.1, jwt-auth-middleware ^1.2.1, http-request-logger ^1.2.1, http-problem-details ^1.1, http-cache-middleware ^1.1, openapi-generator ^1.1, swift-db ^1.3).
- Config files follow the `.global.php` naming convention (`app.{env}.php` became `{env}.global.php`; `database.php` became `database.local.php.dist`).
- The Monolog `LoggerInterface` factory is only registered through the logging option.

### Added
- Templates can vary per package group: `Name.<group>.php` is copied only when the group is selected, `Name.no-<group>.php` only when it is not.
- Plain PSR-15 handlers and responses for installs without methorz/http-dto, request classes without validator attributes for installs without Symfony Validator; `DbArticleRepository` is only copied with swift-db.
- Hexagonal projects declare `ramsey/uuid` directly and ship a HealthCheck handler test.
- A pinned installer image used by `setup.sh` and the Makefile.

### Fixed
- Article module: invalid IDs and status filters raise domain errors (400), missing articles return 404, and `DbArticleRepository` validates row values and handles a NULL `updated_at`.
- Removed the unsupported CORS `allowed_methods` setting; code style fixes across the skeleton.

## [2.0.0] - 2025-12-31

### Changed
- **BREAKING**: Minimal and hexagonal projects share one structure: `backend/composer.json` is the only Composer file, Composer always runs from `backend/`, and the root `composer.json` and quality configs are removed after installation.

### Fixed
- "Cannot read config from App\Application\Config\ConfigProvider" errors caused by autoloader path mismatches.

## [1.0.6] - 2025-12-31

### Fixed
- The root `composer.json` is no longer written for hexagonal projects.

## [1.0.5] - 2025-12-30

### Fixed
- The root `composer.json` and quality configs are removed for hexagonal projects.

## [1.0.4] - 2025-12-30

### Changed
- Hexagonal projects no longer keep a root `composer.json`.

### Fixed
- Optional packages are synced to `backend/composer.json` for hexagonal projects.

## [1.0.3] - 2025-12-30

### Fixed
- Hexagonal autoloader paths in Docker: Composer runs from `backend/` and the root `vendor-dir` setting is removed.

## [1.0.2] - 2025-12-30

### Changed
- README documents the architecture choice; the lock file is no longer tracked.

## [1.0.1] - 2025-12-30

### Fixed
- "Cannot read config from HealthCheck\Application\Config\ConfigProvider" in hexagonal projects: explicit namespace mappings, `backend/composer.json` is updated as well, and `start.sh` regenerates the autoloader.

## [1.0.0] - 2025-12-26

### Added
- Initial release: Mezzio skeleton with an interactive installer for minimal or hexagonal architecture and optional packages.
