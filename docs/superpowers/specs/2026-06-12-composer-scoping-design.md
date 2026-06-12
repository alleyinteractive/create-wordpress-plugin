# Composer Dependency Scoping — Design

**Issue:** [#309 — Allow Composer Dependencies to be scoped](https://github.com/alleyinteractive/create-wordpress-plugin/issues/309)
**Date:** 2026-06-12
**Status:** Approved (brainstorm) — pending implementation plan

## Problem

When a `create-wordpress-plugin`-based plugin is loaded as a Composer dependency
in a larger project — or when two such plugins coexist in the same WordPress
install — their unscoped `vendor/` directories collide. Same namespaces,
different versions; whoever autoloads first wins, and the other plugin breaks.

The dominant source of collision is **Alley's own shared packages**
(`alleyinteractive/wp-type-extensions`, `mantle-framework/support`) shipping at
different versions across plugins, not just third-party libraries like Symfony.

The fix: optionally prefix ("scope") a plugin's Composer dependencies so each
plugin carries its own isolated copy under a unique namespace.

## Goals

1. Scaffolded plugins can **opt in** to scoping at configure time (AC #3).
2. The plugin's own `src/` is never renamed; only its *references* to vendor
   classes point at the scoped names (AC #2).
3. Scoping runs **locally** (a `composer` script) and **at release**
   (action-release), both producing a separate `vendor-prefixed/` folder that
   the plugin loads from — dev/prod parity.
4. Released artifacts **prune** `require`/`require-dev` so dependencies are not
   re-resolved (and re-conflicted) when the plugin is required (AC #1).
5. A CI test proves the scoped build loads and passes on every PR (AC #4).

## Non-goals

- Surviving a consuming project's `composer install` of the plugin's **source**
  (develop) branch. The conflict-free guarantee applies to the **released**
  artifact (built branch / tag / .org zip), which bundles `vendor-prefixed/` and
  has pruned requires.
- Scoping in the template repo itself for release. The template never publishes
  a built branch (action-release skips
  `github.repository == 'alleyinteractive/create-wordpress-plugin'`).

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Tool | **php-scoper** (`humbug/php-scoper`) | Named in the issue; matches prior art (Yoast, Gato). **Strauss held in reserve** if the in-place subfolder pattern proves painful. |
| Import model | **B — authored prefixed imports** | Only model that gives local/release parity *and* never auto-rewrites committed `src/` (AC #2 satisfied literally). php-scoper only ever runs over `vendor/`. |
| Prefix | `{RootNamespace}\Dependencies` (e.g. `Alley\WP\My_Plugin\Dependencies`) | configure.php already computes the namespace; prefix nests cleanly under the plugin. |
| Output folder | `vendor-prefixed/` | Community convention; also Strauss's default, easing the fallback. |
| WP globals | Excluded via `sniccowp/php-scoper-wordpress-excludes` + the plugin's own namespace | Never prefix `WP_*` / `wp_*` / core constants, or the plugin's own classes. |
| Per-package policy | **Opt-out** (scope all of `vendor/`, exclude as needed) | php-scoper's native model; opt-in per package would require enumerating the whole transitive tree and is a footgun. |
| configure.php default | **Opt-in, default OFF** | Most template plugins live inside a project where scoping is unneeded overhead; scoping is purely additive. (AC #3's "opt to not scope" wording was considered; default-off chosen deliberately.) |

## Architecture

Two modes, selected at configure time:

- **Unscoped (default):** today's behavior, unchanged. `src/` uses normal
  imports; `vendor/` loaded directly.
- **Scoped (opt-in):** `configure.php` applies an additive transform; the plugin
  loads from `vendor-prefixed/`; scoping regenerates that folder on every
  install and at release.

### Components

**1. Template ships unscoped.** No change to the committed template's runtime
behavior. The template's own tests continue to run unscoped.

**2. `configure.php` scoping opt-in.** When the developer answers yes:
- Add `humbug/php-scoper` and `sniccowp/php-scoper-wordpress-excludes` to
  `require-dev`.
- Write `.scoper/scoper.inc.php` (config home already reserved in
  `.deployignore`). Config sets `prefix`, `output-dir` (`vendor-prefixed`),
  excludes the plugin's own namespace, and pulls WP excludes from the
  `php-scoper-wordpress-excludes` package.
- Add a `composer scope` script and wire `post-install-cmd` /
  `post-update-cmd` so `vendor-prefixed/` is always regenerated.
- Rewrite `plugin.php`'s loader path
  `vendor/wordpress-autoload.php` → `vendor-prefixed/wordpress-autoload.php`
  (and the parent-project fallback comment accordingly).
- Rewrite the **finite, known set** of vendor imports in `src/` to the prefixed
  namespace:
  - `src/main.php`: `use Alley\WP\Features\Group;`
  - `src/features/class-register-block-manifest.php`,
    `src/features/class-load-entries.php`: `use Alley\WP\Types\Feature;`
  - `src/meta.php`: `use function Mantle\Support\Helpers\register_meta_from_file;`
  Each gains the `{RootNamespace}\Dependencies\` prefix.
- Add `vendor-prefixed` to `.gitignore` (source). Ensure `.deployignore`
  excludes `vendor/` but **keeps** `vendor-prefixed/` on the built branch.

**3. `composer scope` script.** Runs php-scoper over `vendor/ → vendor-prefixed/`,
then `composer dump-autoload` (scoped) so the classmap targets prefixed classes.
Never touches `src/`.

**4. action-release change (cross-repo: `alleyinteractive/action-release`).**
Add a `scope` input. When enabled, after the existing
`composer install --no-dev --optimize-autoloader`:
- run `composer scope`,
- **enable the currently-disabled "Clear composer require/require-dev" step**
  (it is commented out in `action.yml` citing this exact issue),
- commit `vendor-prefixed/` (not `vendor/`) to the `-built` branch.

**5. CI test for scoped code (AC #4).** A job — gated on scoping being enabled —
that runs `composer install` → `composer scope` → `phpunit` against
`vendor-prefixed/`, proving the scoped build loads and passes on every PR.

## Data flow

```
Dev (scoped mode):
  composer install            # vendor/ (unscoped) + php-scoper in require-dev
    └─ post-install-cmd: composer scope
         └─ php-scoper vendor/ → vendor-prefixed/  (prefix applied, WP excluded)
         └─ composer dump-autoload (scoped)
  plugin.php → vendor-prefixed/wordpress-autoload.php
  src/ imports reference {RootNamespace}\Dependencies\...

Release (action-release, scope: true):
  composer install --no-dev --optimize-autoloader
  composer scope                       → vendor-prefixed/
  clear require/require-dev            (pruned: no re-resolution on require)
  commit vendor-prefixed/ to *-built   (vendor/ excluded via .deployignore)
  consumer `composer require`s release → self-contained, conflict-free
```

## Risks & mitigations

- **DX cost of Model B:** verbose imports; `composer install` must always run
  the scope step or prefixed imports won't resolve. → Mitigated by the
  `post-install-cmd`/`post-update-cmd` hooks.
- **String-based class resolution:** php-scoper cannot rewrite class names
  referenced as runtime strings. → The AC #4 CI test exercises the scoped build
  and catches breakage.
- **Cross-repo coordination:** runtime work spans this template and
  `action-release`. → Two separate PRs; the action-release input is backward
  compatible (defaults off).

## Acceptance criteria mapping

| AC | Covered by |
|---|---|
| 1. Scaffold includes php-scoper for release | configure.php opt-in + action-release `scope` input + require pruning |
| 2. `src/` not scoped, only references changed | Model B: php-scoper touches only `vendor/`; configure.php rewrites the finite import set |
| 3. Developer can opt out of scoping | configure.php opt-in, default off |
| 4. PRs test against scoped code | New CI job: install → scope → phpunit on `vendor-prefixed/` |

## Open items for the plan

- Exact `scoper.inc.php` contents (patchers for any package that needs them).
- Whether `composer dump-autoload` scoped needs `--classmap-authoritative`.
- action-release: input name/semantics and built-branch file selection.
- configure.php import-rewrite mechanism (targeted replacement of the known set).
