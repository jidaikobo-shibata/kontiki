# Kontiki v1 architecture

## Decision

Kontiki v1 is one updateable Composer package with an explicitly invoked CLI.

```bash
composer require jidaikobo/kontiki:^1.0
php vendor/bin/kontiki install
```

The package is not a `create-project` root template. It is a Composer library
installed in `vendor/`, so subsequent `composer update` operations can update
the CMS implementation.

The v0.9 installer and framework remain unchanged on their respective
`0.9-maintenance` branches.

## Ownership boundary

### Package-owned, updateable files

- `src/`: CMS implementation and CLI classes
- `resources/views/`: built-in administration views and assets
- `resources/skeleton/`: templates copied only during installation
- `db/migrations/`: ordered, backward-compatible database migrations
- `bin/kontiki`: stable CLI entry point

Package-owned files must not be edited by an installed site. Composer may
replace them during an update.

### Site-owned, persistent files

- `composer.json` and `composer.lock`
- `config/<environment>/.env`
- `app/`: site-specific routes, controllers, models, locale, and view overrides
- `public/`: HTTP entry points and uploads
- `var/` or the configured database path: SQLite database and runtime state

The installer creates site-owned files only when they do not already exist.
It must fail safely instead of overwriting an existing configuration, database,
entry point, or application file.

## CLI contract

The installed executable is `vendor/bin/kontiki`.

- `kontiki install`: create site-owned configuration and run initial migrations
- `kontiki migrate`: apply package-owned migrations explicitly
- `kontiki status`: report configuration, runtime, and migration state

Installation is never triggered automatically by a Composer lifecycle script.
Interactive prompts are the default for a terminal. Automation uses explicit
options or environment variables with `--no-interaction`.

Planned common options:

- `--project-dir`
- `--environment`
- `--base-url`
- `--admin-path`
- `--language`
- `--timezone`
- `--dry-run`
- `--no-interaction`

Secrets must not be accepted in command-line arguments where they would be
stored in shell history. Generated credentials are displayed once or accepted
through an interactive hidden prompt or protected environment input.

## Database migrations

Phinx reads migrations from the installed package, not from copies in the site.
The SQLite database and Phinx history belong to the site.

- New installs run every package migration in order.
- Existing installs retain their current `phinxlog` history.
- Published migration identifiers are immutable.
- Migrations must be safe for the known v0.9 site states.
- Composer updates do not run migrations automatically.
- `kontiki migrate` is a separate, explicit maintenance step.

## Update and compatibility policy

- `main` develops v1.
- `0.9-maintenance` protects existing sites using `^0.9`.
- v1 changes cannot enter a v0.9 tag accidentally.
- Security or data-integrity fixes found in v1 are reproduced on v0.9 before a
  minimal backport is prepared.
- A v0.9 tag is published only after existing-site and clean-install checks.
- A v1 migration path is tested against copies of real v0.9 layouts before a
  production site changes its Composer constraint.

## Incremental integration sequence

1. Convert `kontiki/main` from an automatic create-project wrapper into a
   library with `bin/kontiki` and an explicit `install` command.
2. Keep `kontiki-framework` as a temporary dependency while proving the new CLI
   from a separate consumer project.
3. Move framework-owned `src/`, views, and migrations into `kontiki` while
   retaining framework Git history as migration provenance.
4. Remove the temporary framework dependency and verify that only one Kontiki
   package is installed.
5. Add explicit `migrate` and `status` commands.
6. Test an empty project and multiple existing v0.9 site states in `kontiki-dev`.
7. Publish prereleases before considering `v1.0.0` stable.

## First implementation boundary

The first v1 commit introduces the library package metadata and stable CLI
entry point but deliberately retains `jidaikobo/kontiki-framework ^0.9.64` as a
temporary dependency. This keeps the change reviewable: CLI installation can be
tested independently before framework code ownership moves.

No v1 tag is created during this phase.
