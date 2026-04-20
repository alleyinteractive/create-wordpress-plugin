# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A **skeleton/template WordPress plugin** maintained by Alley Interactive. Consumers click "Use template" on GitHub, then run `make` (or `php ./configure.php`) to replace placeholders (plugin name, author, namespace, etc.) throughout the files. Most changes in this repo are to the template itself — keep placeholder tokens like `create-wordpress-plugin`, `Create_WordPress_Plugin`, `author_name`, `author_username` intact unless the task is to change them.

The HTML `<!--delete-->…<!--/delete-->` and `<!--front-end-->…<!--/front-end-->` comment markers in `README.md` (and similar guards elsewhere) are consumed by `configure.php` to strip template-only content on configure. Preserve them.

## Common commands

**Local dev environment** (wp-env + webpack in parallel):
```sh
npm install
composer dev          # runs `wp-env start` + `npm run start` concurrently
```

**Build / watch front-end assets:**
```sh
npm run build         # production build via @alleyinteractive/build-tool
npm run start         # watch mode
npm run start:hot     # HMR
```

**Tests & lint:**
```sh
npm run test          # check-types + eslint + stylelint + jest
npm run jest          # jest only (passes with no tests)
npm run check-types   # tsc --noEmit
composer test         # runs @lint then @phpunit
composer phpunit      # PHPUnit only
composer phpstan      # PHPStan at level `max` (paths: blocks/, entries/, src/, plugin.php)
composer phpcs        # alley-coding-standards
composer rector       # dry-run; `composer rector:fix` to apply
composer lint:fix     # rector:fix + phpcbf
```

Run a single PHPUnit test: `vendor/bin/phpunit --filter ExampleUnitTest` (or pass a path).

**Scaffolding:**
```sh
npm run create-entry     # new directory under entries/
npm run create-slotfill  # new slotfill entry
npm run create-block     # new block under blocks/
npm run scaffold         # run @alleyinteractive/scaffolder (reads .scaffolder/)
```
`.scaffolder/plugin-feature/` generates a new `Feature` class in `src/features/` plus a matching test in `tests/Features/`.

**Release:** `npm run release` bumps the version in `plugin.php` and pushes; GitHub Actions (`built-release.yml`) compiles and tags a `*-built` branch containing the front-end assets.

## Architecture

### Bootstrap flow
`plugin.php` (the WordPress entry file) loads Composer's `vendor/wordpress-autoload.php` (from `alleyinteractive/composer-wordpress-autoloader`, which maps `Alley\WP\Create_WordPress_Plugin\` → `src/`), then requires `src/assets.php`, `src/meta.php`, `src/main.php` and calls `main()`, `register_post_meta_from_defs()`, `register_term_meta_from_defs()`.

If `vendor/` is absent but a parent project has already loaded Composer (i.e. the plugin is a Composer dependency), bootstrap continues silently; otherwise an admin notice is shown.

### Feature-based composition
`src/main.php` constructs an `Alley\WP\Features\Group` (from `alleyinteractive/wp-type-extensions`) containing `Feature` implementations and calls `boot()`. Each feature is a class in `src/features/` implementing `Alley\WP\Types\Feature` with a `boot(): void` method. **To add plugin behavior, create a new `Feature` class (via `npm run scaffold`) and register it in `main()`** — don't add hooks directly in procedural files.

Built-in features:
- `Register_Block_Manifest` — registers blocks from the Gutenberg block manifest produced by `alley-build --blocks-manifest`.
- `Load_Entries` — globs `build/*/index.php` and `require_once`s each. Cached via APCu when not on `local` env.

### Front-end entries
Every directory under `entries/` is a webpack entry point compiled to `build/<name>/` with an `index.asset.php` dependency/version map. An optional `entries/<name>/index.php` is copied to `build/<name>/index.php` and auto-loaded by `Load_Entries` — that's how an entry registers/enqueues itself. Helpers in `src/assets.php` (`get_entry_asset_url`, `get_asset_dependency_array`, `get_asset_version`) read `index.asset.php` and resolve URLs.

### Blocks
Dynamic blocks live in `blocks/<name>/` (scaffolded by `npm run create-block`). The build produces a blocks manifest which `Register_Block_Manifest` consumes via `wp_register_block_types_from_metadata_collection()` (WP 6.7+) or falls back to per-block registration.

### Meta
`config/post-meta.json` and `config/term-meta.json` drive `register_meta_from_file()` from `mantle-framework/support`. Add meta by editing these JSON files — no PHP changes needed. Schema: `https://raw.githubusercontent.com/alleyinteractive/mantle-framework/HEAD/src/mantle/support/schema/meta.json`.

### Tests
- PSR-4: `Alley\WP\Create_WordPress_Plugin\Tests\` → `tests/` (note: this is `autoload-dev`, not the runtime autoloader).
- Base class: `tests/TestCase.php` extends `Mantle\Testkit\Test_Case` and uses `Prevent_Remote_Requests`. New tests should extend this, not `Test_Case` directly.
- `tests/bootstrap.php` uses `Mantle\Testing\manager()` with `maybe_rsync_plugin()` — the test runner rsyncs this plugin into a WordPress install before booting.
- Feature tests live in `tests/Feature/`; unit tests in `tests/Unit/`. Scaffolder generates into `tests/Features/` (note casing difference — scaffolded tests go to a separate dir).

## Conventions to respect

- PHP 8.2+, strict types, PHPStan **level max** across `blocks/`, `entries/`, `src/`, `plugin.php`. New PHP code must pass level max.
- Namespace is `Alley\WP\Create_WordPress_Plugin\...` with feature classes under `...\Features\`.
- Coding standard: `alleyinteractive/alley-coding-standards` (WordPress-VIP-flavored). Inline `phpcs:ignore` is used sparingly for unavoidable VIP rules (e.g. dynamic includes in `Load_Entries`).
- Class files follow WordPress `class-{slug}.php` naming (not PSR-4 filename casing) — the wordpress-autoloader handles both.
- Node 22 / npm 10 (see `engines` and `.nvmrc`).
