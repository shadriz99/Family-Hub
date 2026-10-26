# Family Hub Repository Instructions

## Project status and source of truth

This workspace contains specifications in `docs/`, installed dependencies, a Laravel scaffold with passing foundation/auth tests/build/lint/static analysis, and a separate prototype in `mockup/`. The reviewer authorized continuing the development plan with attention to Copilot credit. Work one task/checkpoint at a time without unnecessary agents, broad rereads, or repeated checks; agent access does not reveal the user's credit balance. Default User/model/migrations remain upstream placeholders, not the approved ULID/tenant schema; only isolated test databases have been migrated, with no local application migrations or seeds. SQLite is the selected local development DB; MariaDB remains a CI compatibility target. Basic Fortify login/logout and password confirmation with Livewire/Blade UI are integrated; invitation/household/domain features are not. CORE-01 technical checks pass, including GitHub CI on both databases at `ffe4afe` (LOG-014); final reviewer acceptance is pending. Boost terminal transport is approved and verified (LOG-013). Do not start CORE-03 before that acceptance. See `docs/development-plan.md`.

Before implementation work, read `docs/00-MASTERPLAN.md`. For `FIN-*`, `ORG-*`, or `AST-*` work, also read the corresponding module spec: `docs/01-MODULE-FINANCE.md`, `docs/02-MODULE-ORGANIZER.md`, or `docs/03-MODULE-ASSET.md`. If instructions conflict, follow the latest reviewer decision, then the relevant module spec, then the masterplan. Report unresolved conflicts instead of silently choosing.

Read `docs/logbook.md` and `docs/development-plan.md` before continuing work. After each task, append an evidence-based log entry and synchronize the plan status with it. Distinguish dependency preparation, implementation, runtime verification, and reviewer acceptance; never mark a task complete from installation alone.

### Current continuation checkpoint (LOG-015)

After the passing CORE-01 CI, the reviewer requested continuing the plan; treat this as acceptance to proceed with development, not production/UAT acceptance. This supersedes the pending-acceptance statements at the previous checkpoint above and below. CORE-03 has started on local branch `core-03-support`: Money parse/format and its 41 unit cases are implemented (LOG-015). No local database migration/seed or account provisioning has occurred. Publication authorization for CORE-01 is not blanket permission to commit/push subsequent tasks.

LOG-016 extends that checkpoint: immutable Period and history-based PeriodResolver arithmetic are implemented, with 73 tests/1362 assertions, Pint, and Larastan passing. Household-backed `for($household)` and tenant support require the real CORE-04 model/history relation; do not invent placeholder models or claim that integration exists. Blade components remain pending. CORE-03 remains partial; no local migrations or publication have occurred.

LOG-017 adds static `x-money` and `x-date`, with 82 tests/1384 assertions, Pint/Larastan/build passing and temporary browser-fixture checks. `x-input-money` remains pending. Date-only strings preserve calendar dates; DateTimeInterface values convert to the explicit household timezone (default Asia/Jakarta is not automatic tenant resolution). No domain page or local DB changes.

LOG-018 adds `x-input-money` with reviewer-approved safe browser integer range +/-9007199254740991, integer/null Livewire binding, and localized errors. All consuming forms must validate `required`/`nullable` plus `App\Rules\SafeRupiah` server-side and display server validation errors; JavaScript validation is not a trust boundary. PHP Money keeps its full integer range. Local regression: 94 PHP tests/1407 assertions, 4 JS tests, Pint/Larastan/build pass. Run `npm test` for JS changes; CI now includes it but CORE-03 has not been published. Programmatic browser events/calls verified integration where interaction tools were unreliable; full pointer/keyboard checks remain a reported gap. Household/tenant integration remains pending.

## Mandatory documentation synchronization

Current LOG-019 decision supersedes earlier placeholder/task-order notes: reviewer approved CORE-03A -> CORE-04 -> CORE-03B. Real household/member/period-history models, MemberRole, and ULID User/session schema now exist locally; no local application migration/seed has run. Tests use SQLite in-memory (101 PHP tests/1421 assertions, 4 JS tests; Pint/Larastan pass). CORE-04 remains partial: policies, transactional membership actions/slot allocation, initial history, concurrency on both DBs and migration rollback verification are pending. Tenant/resolver integration 03B follows the real models; do not mark it complete. Native pointer/keyboard gaps remain. There are no new domain routes, persistent accounts, or publication authorization for these tasks. Initial User migration was adjusted only before any application DB deployment, not as a safe upgrade for existing integer-key databases.

For every change batch (code, configuration, dependencies, tooling, skills, instructions, or documentation), load and follow `.github/skills/development-docs-sync/SKILL.md`. Update both `docs/logbook.md` and `docs/development-plan.md` before completion, including documentation-only work. Read-only investigations require no artificial log entry. One coherent batch gets one entry; tracking updates do not recursively require more entries.

For structural changes, review every maintained document under `docs/` and relevant instructions/skills or other documentation outside it. Synchronize every affected specification, ADR, plan, runbook, UAT checklist, path, link, command, and example in the same batch; record the review scope and unaffected documents in the logbook. Do not make cosmetic edits to unaffected files or falsify historical records. This is mandatory agent behavior, not an installed watcher or CI enforcement.

## Mandatory coordinated UI workflow

LOG-020 adds an explicitly registered HouseholdPolicy with the approved owner/partner ability matrix and no system-admin bypass. Fresh membership queries reject stale/revoked roles and deleted households; actions/routes must call Gate when implemented and still validate use-case targets and concurrency. Local regression: 120 PHP tests/1467 assertions, Pint/Larastan pass; 19 policy tests cover access boundaries. Transactional membership actions, bootstrap history, rollback/concurrency evidence, and tenant adapter remain pending. No new public route or persistent account.

LOG-021 adds RemoveHouseholdMember and TransferHouseholdOwnership backend Actions for future CORE-14 callers. Household lock, fresh Gate/membership checks, role mutations and synchronous Spatie audit share a transaction. Activity uses ULID PK/morphs and buffering is disabled; do not expose unscoped audit queries. Slots/colors/joined_at stay unchanged on ownership transfer; removal does not delete accounts/domain records. Regression: 139 PHP tests/1546 assertions, Pint/Larastan pass; isolated SQLite migrate/rollback/re-migrate passed. This supersedes pending-action/rollback notes above, not admission/bootstrap/parallel SQLite-MariaDB/03B gaps. No public caller, persistent account, application migration or publication.

For every change batch affecting rendered UI or browser behavior (Blade, Livewire, CSS/JS, layout, UI translations, assets, or routes/backend behavior consumed by a page), use all three skills without waiting for a separate review request. Documentation-only and backend-only changes with no browser impact do not trigger this workflow.

1. Load `premium-frontend-ui` before UI implementation, then load `web-design-reviewer` and `webapp-testing` for their respective phases. If a repository skill is absent from discovery, read `.github/skills/<name>/SKILL.md` directly and follow it; do not claim it was registered. If the personal premium skill is unavailable, report that limitation and follow project UI standards without installing or copying skills automatically.
2. Apply premium design guidance only within the approved Family Hub design, stack, accessibility, and performance constraints. Do not introduce preloaders, scroll hijacking, custom cursors, cinematic animation, external fonts/CDNs, or new dependencies merely to satisfy generic skill examples. Preserve native controls and existing UX.
3. Use `web-design-reviewer` to inspect the affected pages visually and through the accessibility snapshot: layout, overflow, contrast, focus, labels, and touch targets. Check 360px mobile and 1280px desktop in light/dark; add tablet/wide checks when affected breakpoints warrant them.
4. Use `webapp-testing` to verify affected interactions, validation/error states, navigation, and relevant regression paths. Reuse the same browser session, synthetic fixtures, and evidence from the visual phase; do not duplicate checks or run agents editing the same UI in parallel. Prefer available integrated browser tools; no automatic Playwright installation.
5. Fix issues in scope, then rerun affected visual/behavior checks against the final code. Never substitute a programmatic click for proof that pointer/keyboard interaction works; report such fallbacks and remaining gaps explicitly. If the app/browser/fixtures are unavailable, record the blocked checks rather than claim success.
6. Summarize skill usage, pages/viewports/states checked, failures, and skipped checks in the batch logbook/plan. Follow the shared project summary format rather than producing three duplicate reports. Do not close reviewer-owned/shared browser pages or stop a server left running for the reviewer.

This is an agent instruction for repository work, not a save-time watcher, CI gate, or guarantee of skill discovery in every Copilot host. Do not rerun UI checks recursively for the documentation updates that record the same batch.

## Build, test, and lint

The approved baseline is PHP 8.5, Laravel 13, Livewire 4 with its bundled Alpine, Fortify with project-owned Blade UI, Tailwind 4, Vite 8 with laravel-vite-plugin 3 on Node 24 LTS, Pest 5 with its Laravel plugin 5 and development-only Mockery for Laravel database tests, Pint 1, and Larastan 3 (PHPStan 2, level 6). See `docs/adr/ADR-011-current-stack.md`. Restore with `composer install --no-scripts`, `composer dump-autoload`, and `npm ci --ignore-scripts`; build with `npm run build`, test with `composer test`, lint with `composer lint`, analyse with `composer analyse`, serve with `composer serve`. GitHub CI SQLite/MariaDB passes at the LOG-014 foundation checkpoint. Fortify is discovered with an explicitly empty feature list; Passkeys auto-discovery remains disabled. Native Fortify endpoints own credential validation and sessions; the Livewire login component renders the form, with bundled Alpine for password visibility, rather than duplicating backend authentication.

Do not use Breeze's Livewire installer: it still installs Livewire 3, Volt, and Tailwind 3. Do not add Flux/Volt or duplicate Alpine. Keep public registration disabled and implement invitation acceptance through Core; Fortify's TOTP/passkey features are not approved substitutes for the planned optional email OTP.

Boost MCP config is installed for Copilot in `.vscode/mcp.json`. Per reviewer decision LOG-013, use a bounded terminal stdio client when Boost tools are needed; UI MCP activation is unavailable to the reviewer and is no longer a CORE-01 blocker. Initialize MCP, send the initialized notification, discover the tool schema, call the needed tool, surface errors/timeouts, and terminate the child after the operation. Initialization, tool listing, and `search-docs` pass through this path; do not claim a native Copilot UI connection or leave an idle stdio server running. Only send generic documentation queries/package context, never source, household data, or secrets. Guidelines/skills generation is intentionally deferred; do not overwrite project instructions. Composer/platform checks and remote CI passed without `--ignore-platform-reqs`; final CORE-01 reviewer acceptance remains pending.

The masterplan's planned CI checks are:

- `composer test` — full test suite
- `npm test` — money-input JavaScript regression tests (Node built-in runner)
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
