# Family Hub Repository Instructions

## Project status and source of truth

This workspace currently contains approved specifications in `Build Docs/`; implementation files and dependency manifests are not present yet. Treat the architecture and commands below as the approved plan, not as evidence that the application is already scaffolded.

Before implementation work, read `Build Docs/00-MASTERPLAN.md`. For `FIN-*`, `ORG-*`, or `AST-*` work, also read the corresponding module spec: `Build Docs/01-MODULE-FINANCE.md`, `Build Docs/02-MODULE-ORGANIZER.md`, or `Build Docs/03-MODULE-ASSET.md`. If instructions conflict, follow the latest reviewer decision, then the relevant module spec, then the masterplan. Report unresolved conflicts instead of silently choosing.

## Build, test, and lint

The planned stack is Laravel 12, PHP 8.3, Pest 3, Laravel Pint, Larastan, and Vite. No `composer.json`, `package.json`, or test suite exists in this workspace yet, so these commands are not currently runnable.

The masterplan's planned CI checks are:

- `composer test` — full test suite
- `pint --test` — formatting check
- `phpstan` — static analysis
- Run tests against both SQLite and MariaDB in CI.

Once Pest is installed, use its file and filter selectors to target tests, for example `./vendor/bin/pest tests/Feature/Finance` or `./vendor/bin/pest --filter="test name"`. Confirm the eventual Composer scripts and CI configuration when the project is scaffolded.

## Architecture

Family Hub is a household-oriented application planned as a Laravel modular monolith, not a set of services. Core owns authentication/invitations, household membership and authorization, shared support utilities, reminders, notifications, and the calendar/alert aggregation contracts. Finance, Organizer, and Asset are separately owned domain modules, with module routes, providers, actions, models, policies, events, and Livewire UI.

Cross-module dependencies must remain behind the target module's public contract in `app/Modules/<Module>/Contracts/` or events/listeners. Do not query another module's tables or call its actions directly. For Finance integrations, use `FinanceGateway`; keep Organizer and Asset functional when household Finance integration is disabled by using the null implementation. Calendar and alerts are assembled through Core source contracts rather than copied into shared domain tables.

Household data isolation is a core security boundary: domain queries and authorization must be scoped to the active household, including routes, Livewire actions, exports, search, and private media. The planned database supports SQLite by default and MariaDB as an option, so use portable Eloquent/query-builder operations rather than database-specific SQL.

## Repository conventions

- Keep business rules in one-use-case Actions (`handle(...)`), not in Livewire components. Livewire handles input, form validation, Action calls, and rendering. Models hold relationships, casts, scopes, and simple accessors; Policies authorize; Events/Listeners coordinate modules.
- New PHP files use `declare(strict_types=1);`; format with PSR-12/Pint. Code, identifiers, tables, and columns are English. User-facing strings belong in `lang/id/*.php` (with English localization support planned), not hardcoded in Blade.
- Validate with Livewire Form Objects and Indonesian validation messages. Actions that write activity should record an activity log. Multi-table writes belong in a database transaction.
- Use ULIDs for primary keys. Store Rupiah as integer `bigInteger` amounts, never floating-point or decimal values. Store enum values as strings backed by PHP enums. Persist datetimes in UTC and display them in the household timezone. Keep migrations portable across SQLite and MariaDB.
- Every domain table carries `household_id`, nullable `created_by`, timestamps, and soft deletes unless it is a pivot or log table. Add indexes for household IDs, foreign keys, and filtered date columns. After production migrations ship, add a new migration rather than editing an old one; every migration needs a `down()`.
- Soft-delete by default; archive financially related records where the module spec requires it. For edits, detect stale `updated_at` values and warn users rather than silently overwriting another household member's changes.
- Feature coverage includes the happy path, validation failure, cross-household authorization, and key business rules. Avoid N+1 queries; the plan enables `Model::preventLazyLoading()` outside production.
- Use the shared `Money` and `PeriodResolver` utilities for currency and Finance periods. Render dates using the Indonesian date helpers/components specified in the masterplan.
- Do not add Composer or npm dependencies outside the approved stack without an accepted ADR and reviewer approval. Keep module-specific work to one documented task ID and its acceptance criteria.
