# Composer Dependency Scoping Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let plugins scaffolded from `create-wordpress-plugin` optionally prefix ("scope") their Composer dependencies into `vendor-prefixed/`, loaded both locally and at release, so two template-based plugins no longer collide on shared packages.

**Architecture:** php-scoper (Strauss in reserve), authored-prefixed-imports model (Model B — scoper only ever touches `vendor/`). A namespace-agnostic `.scoper/scoper.inc.php` derives the prefix from `composer.json` at scope time. `configure.php` gains an opt-in (default off) that wires up the machinery; without opt-in the template behaves exactly as today. Release-time scoping is a backward-compatible `scope` input added to `alleyinteractive/action-release`.

**Tech Stack:** PHP 8.2+, Composer, `humbug/php-scoper`, `sniccowp/php-scoper-wordpress-excludes`, GitHub Actions.

---

## File Structure

- **Create** `.scoper/scoper.inc.php` — namespace-agnostic php-scoper config. Reads the root namespace from `composer.json` `extra.wordpress-autoloader.autoload`, sets `prefix` = `<root>\Dependencies`, excludes that namespace + WP globals.
- **Modify** `configure.php` — add `scope_dependencies()` transform + opt-in prompt; delete `.scoper/` when declined.
- **Modify** `composer.json` (template) — no default scoping deps; `configure.php` injects them. (`vendor-prefixed` handled via ignore files.)
- **Modify** `.gitignore` — add `vendor-prefixed`.
- **Modify** `.deployignore` — exclude `vendor` (so source vendor isn't shipped on built branch); `vendor-prefixed` stays.
- **Create** `.github/workflows/test-scoped.yml` — AC #4 CI: install → scope → phpunit against `vendor-prefixed/` (no-op unless scoping enabled).
- **Cross-repo** `alleyinteractive/action-release` `action.yml` — add `scope` input; run `composer scope` + enable the disabled require-pruning step when on.

---

## Task 1: Namespace-agnostic scoper config

**Files:**
- Create: `.scoper/scoper.inc.php`

- [ ] **Step 1: Write `.scoper/scoper.inc.php`**

```php
<?php
/**
 * php-scoper configuration.
 *
 * Namespace-agnostic: the prefix is derived from the plugin's own root
 * namespace (declared in composer.json under
 * extra.wordpress-autoloader.autoload) with a `\Dependencies` suffix, so this
 * file works unchanged for any plugin scaffolded from create-wordpress-plugin.
 *
 * @package create-wordpress-plugin
 */

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

$composer = json_decode( (string) file_get_contents( __DIR__ . '/../composer.json' ), true );

// Derive the root namespace from the WordPress autoloader config.
$autoload_map = $composer['extra']['wordpress-autoloader']['autoload'] ?? [];
$root_namespace = (string) array_key_first( $autoload_map );
$root_namespace = rtrim( $root_namespace, '\\' );

if ( '' === $root_namespace ) {
	fwrite( STDERR, "Unable to derive root namespace from composer.json.\n" );
	exit( 1 );
}

$prefix = $root_namespace . '\\Dependencies';

// WordPress core symbols that must never be prefixed.
$wp_excludes = static function ( string $file ): array {
	$path = __DIR__ . '/../vendor/sniccowp/php-scoper-wordpress-excludes/generated/' . $file;
	return file_exists( $path ) ? (array) ( require $path ) : [];
};

return [
	'prefix'             => $prefix,
	'output-dir'         => __DIR__ . '/../vendor-prefixed',
	'finders'            => [
		Finder::create()
			->files()
			->ignoreVCS( true )
			->notName( '/.*\\.dist|Makefile|composer\\.(json|lock)/' )
			->exclude( [ 'doc', 'docs', 'test', 'tests', 'Tests', 'vendor-bin' ] )
			->in( __DIR__ . '/../vendor' ),
	],
	// Do not prefix the plugin's own namespace (AC #2: src classes keep names).
	'exclude-namespaces' => [
		$root_namespace,
	],
	'exclude-functions'  => $wp_excludes( 'exclude-wordpress-functions.php' ),
	'exclude-classes'    => $wp_excludes( 'exclude-wordpress-classes.php' ),
	'exclude-constants'  => $wp_excludes( 'exclude-wordpress-constants.php' ),
];
```

- [ ] **Step 2: Commit**

```bash
git add .scoper/scoper.inc.php
git commit -m "Add namespace-agnostic php-scoper config (#309)"
```

## Task 2: `configure.php` scoping opt-in

**Files:**
- Modify: `configure.php` (add helper + prompt near the Composer block, ~line 33-56)

- [ ] **Step 1: Add a `scope_dependencies()` helper function**

Add alongside the other helper functions (e.g. after `remove_composer_files()`):

```php
function scope_dependencies( string $root_namespace ): void {
	$prefix = $root_namespace . '\\Dependencies';

	// 1. Add php-scoper tooling to require-dev.
	$composer = (array) json_decode( (string) file_get_contents( 'composer.json' ), true );

	$composer['require-dev']['humbug/php-scoper']                     = '^0.18';
	$composer['require-dev']['sniccowp/php-scoper-wordpress-excludes'] = '^6.0';

	// 2. Add the scope script + regenerate vendor-prefixed on install/update.
	$composer['scripts']['scope'] = [
		'php-scoper add-prefix --config=.scoper/scoper.inc.php --force --quiet',
		'@composer dump-autoload --working-dir=vendor-prefixed --classmap-authoritative',
	];
	$composer['scripts']['post-install-cmd'][] = '@scope';
	$composer['scripts']['post-update-cmd'][]  = '@scope';
	$composer['config']['allow-plugins']['humbug/php-scoper'] = true;

	file_put_contents(
		'composer.json',
		json_encode( $composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
	);

	// 3. Point the plugin loader at the scoped autoloader.
	replace_in_file(
		'plugin.php',
		[
			'/vendor/wordpress-autoload.php' => '/vendor-prefixed/wordpress-autoload.php',
		]
	);

	// 4. Prefix the finite set of vendor imports in src (Model B).
	$rewrites = [
		'src/main.php'                                    => [ 'use Alley\\WP\\Features\\Group;' => "use {$prefix}\\Alley\\WP\\Features\\Group;" ],
		'src/features/class-register-block-manifest.php'  => [ 'use Alley\\WP\\Types\\Feature;' => "use {$prefix}\\Alley\\WP\\Types\\Feature;" ],
		'src/features/class-load-entries.php'             => [ 'use Alley\\WP\\Types\\Feature;' => "use {$prefix}\\Alley\\WP\\Types\\Feature;" ],
		'src/meta.php'                                    => [ 'use function Mantle\\Support\\Helpers\\register_meta_from_file;' => "use function {$prefix}\\Mantle\\Support\\Helpers\\register_meta_from_file;" ],
	];

	foreach ( $rewrites as $file => $replacements ) {
		if ( file_exists( $file ) ) {
			replace_in_file( $file, $replacements );
		}
	}

	// 5. Ignore vendor-prefixed in source; ship it (not vendor) on built branch.
	if ( file_exists( '.gitignore' ) ) {
		$gitignore = (string) file_get_contents( '.gitignore' );
		if ( ! str_contains( $gitignore, 'vendor-prefixed' ) ) {
			file_put_contents( '.gitignore', $gitignore . "vendor-prefixed\n" );
		}
	}
	if ( file_exists( '.deployignore' ) ) {
		$deployignore = (string) file_get_contents( '.deployignore' );
		if ( ! preg_match( '/^vendor$/m', $deployignore ) ) {
			file_put_contents( '.deployignore', $deployignore . "vendor\n" );
		}
	}
}
```

- [ ] **Step 2: Wire the opt-in prompt into the Composer block**

Inside the `if ( confirm( 'Will this plugin be using Composer?...' ) ) { $uses_composer = true; ... }` branch, after `composer install/update` runs, add:

```php
	if ( confirm( 'Do you want to scope (prefix) your Composer dependencies to avoid conflicts when this plugin is loaded alongside others? (Recommended for plugins distributed standalone or via Composer.)', false ) ) {
		scope_dependencies( $namespace );
		echo run( 'composer update' );
		echo run( 'composer scope' );
	} else {
		delete_files( '.scoper' );
	}
```

(`$namespace` is the variable configure.php already computes for the root namespace — confirm its exact name when implementing; it is the value written into `extra.wordpress-autoloader.autoload`.)

- [ ] **Step 3: Delete `.scoper/` on the non-Composer path too**

In the `elseif`/else branches where Composer is not used, ensure `.scoper` is removed:

```php
	if ( file_exists( '.scoper' ) ) {
		delete_files( '.scoper' );
	}
```

- [ ] **Step 4: Commit**

```bash
git add configure.php
git commit -m "Add Composer dependency scoping opt-in to configure.php (#309)"
```

## Task 3: Ignore-file defaults in template

**Files:**
- Modify: `.deployignore`

- [ ] **Step 1:** Leave the template's `.gitignore`/`.deployignore` as-is for the unscoped default; the scoping changes are applied by `configure.php` (Task 2 Step 1.5). No template change required unless verification shows the built branch ships `vendor/` incorrectly — in which case add `vendor` to `.deployignore` guarded by scoping. Skip if not needed.

- [ ] **Step 2:** Commit only if changed.

## Task 4: CI test for scoped builds (AC #4)

**Files:**
- Create: `.github/workflows/test-scoped.yml`

- [ ] **Step 1: Write the workflow**

```yaml
name: Scoped Build Tests

on:
  pull_request:
    branches:
      - develop
      - main

jobs:
  scoped-tests:
    name: "Scoped dependency tests"
    runs-on: ubuntu-latest
    # Only run when the plugin has opted into scoping.
    if: false # configure.php flips this to `true` when scoping is enabled.
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          tools: composer
      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction --no-progress
      - name: Scope dependencies
        run: composer scope
      - name: Assert vendor-prefixed was generated
        run: test -f vendor-prefixed/wordpress-autoload.php
      - name: Run tests against scoped code
        run: composer phpunit
```

`configure.php` (Task 2) should flip `if: false` → `if: true` when scoping is enabled, and `delete_files()` this workflow when scoping is declined. Add to `scope_dependencies()`:

```php
	if ( file_exists( '.github/workflows/test-scoped.yml' ) ) {
		replace_in_file( '.github/workflows/test-scoped.yml', [ 'if: false' => 'if: true' ] );
	}
```

and to the decline branch:

```php
	delete_files( '.github/workflows/test-scoped.yml' );
```

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/test-scoped.yml configure.php
git commit -m "Add scoped-build CI workflow (#309)"
```

## Task 5: action-release `scope` input (cross-repo)

**Files:**
- Modify (separate PR, repo `alleyinteractive/action-release`): `action.yml`

- [ ] **Step 1:** Add input:

```yaml
  scope:
    description: 'Run `composer scope` to prefix dependencies into vendor-prefixed/ before release.'
    required: false
    default: 'false'
```

- [ ] **Step 2:** After the "Composer Install" step, add:

```yaml
    - name: Scope Composer dependencies
      if: inputs.scope == 'true' && inputs.skip-composer-install != 'true'
      shell: bash
      run: composer scope
```

- [ ] **Step 3:** Enable the currently-disabled require-pruning step, gated on `inputs.scope == 'true'`:

```yaml
    - name: Clear composer require/require-dev
      if: inputs.scope == 'true' && inputs.skip-composer-install != 'true' && inputs.skip-composer-require-removal != 'true'
      shell: bash
      run: |
        jq 'del(.require, .["require-dev"])' composer.json > composer.json.tmp
        mv composer.json.tmp composer.json
        rm -rf composer.lock composer.json.bak || true
```

- [ ] **Step 4:** `built-release.yml` in scaffolded plugins passes `scope: true` when scoping is enabled (configure.php adds it). Document in the plugin's workflow.

## Task 6: Local end-to-end verification (the goal)

- [ ] **Step 1:** Scaffold a fresh plugin from this template into a temp dir.
- [ ] **Step 2:** Run `php configure.php` non-interactively (or with args) choosing: Composer = yes, scoping = yes, SQLite testing = yes.
- [ ] **Step 3:** `composer require` a real dependency (e.g. a small Symfony component) to prove third-party prefixing.
- [ ] **Step 4:** `composer scope`; assert `vendor-prefixed/` contains prefixed namespaces (`grep -r "Dependencies\\\\Symfony" vendor-prefixed | head`).
- [ ] **Step 5:** `composer phpunit` (SQLite) — expect PASS, proving the scoped autoloader loads the plugin and dependencies.
- [ ] **Step 6:** If php-scoper's composer-autoloader handling fails to produce a working `vendor-prefixed/wordpress-autoload.php`, pivot to **Strauss** (pre-approved fallback): replace the `scope` script with a Strauss invocation that emits `vendor-prefixed/` + autoloader, keeping the same folder/prefix contract. Re-run Steps 4-5.

---

## Validated Implementation (as built)

Tasks 1–4 and 6 are implemented and verified locally. Key deltas from the
initial task drafts, discovered during prototyping:

- **wp-excludes are JSON, not PHP** — `scoper.inc.php` `json_decode`s
  `exclude-wordpress-{classes,interfaces,functions,constants}.json` (interfaces
  merged into `exclude-classes`).
- **Runtime-only finder** — the finder is built from `composer.lock` `packages`
  (via `->path()`), so dev tools (php-scoper, PHPUnit, Rector) are never scoped.
- **Autoloader regeneration, not php-scoper's** — php-scoper's scoped autoloader
  is unusable (unprefixed `registerFromRules` strings + broken Composer
  bootstrap). `.scoper/scope.php` instead regenerates a `classmap-authoritative`
  autoloader over the scoped files + `../src`, and rebuilds the `files`
  autoloads from each runtime package so helper functions still load.
- **Loader path** — `plugin.php` loads `vendor-prefixed/vendor/autoload.php`.
- **Guarded orchestrator** — `.scoper/scope.php` no-ops when php-scoper is
  absent, so it is safe in `post-install-cmd`/`post-update-cmd`.

**Verification:** scaffolded `wp-scoped-test-plugin` (Alley vendor), opted into
scoping + SQLite, ran `composer require symfony/string` (auto re-scoped to 1606
classes), confirmed all scoped classes + the scoped `register_meta_from_file`
resolve while the unscoped `Alley\WP\Features\Group` does not, and
`composer phpunit` passed (2 tests, 3 assertions) — the Feature test boots the
whole plugin through the scoped autoloader.

**Pre-existing quirk noted (out of scope):** `configure.php`'s
`'alleyinteractive' => $vendor_slug` replacement rewrites the Alley *dependency*
package names when a non-Alley vendor is chosen, breaking `composer update`.
Unrelated to #309; flagged for separate follow-up.

**Task 5 (action-release) remains** — a backward-compatible cross-repo PR, not
yet opened (outward-facing; awaiting go-ahead).

## Self-Review Notes

- **Spec coverage:** AC1 → Tasks 2,5. AC2 → Task 1 (`exclude-namespaces`) + Task 2 finite rewrite. AC3 → Task 2 opt-in default off. AC4 → Task 4. All mapped.
- **Contingency:** Task 6 Step 6 captures the php-scoper-autoloader risk with the Strauss fallback the user pre-approved.
- **Cross-repo:** Task 5 is a backward-compatible (`default: 'false'`) change; safe to land independently.
