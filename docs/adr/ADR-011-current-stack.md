# ADR-011: Current stable stack and Laravel Boost

- Status: Accepted by Shadri in chat on 2026-10-26.
- Scope: Planned dependencies and CORE-01 acceptance criteria; no application is installed by this ADR.
- Supersedes: Version choices and Breeze scaffolding in masterplan v0.2; retains the modular-monolith and Livewire architecture of ADR-002.

## Context

### Temporary SQLite-only gate (LOG-023, 2026-10-26)

The reviewer explicitly deferred MariaDB and selected SQLite first. CI now tests SQLite only; the dual-database acceptance gate below is temporarily deferred, not satisfied for new changes. Restore and verify MariaDB before claiming its compatibility. This does not change domain contracts or authorize deployment. Only the tested CORE-01 foundation is merged to main; CORE-03/04 remain on their separate support branch.

### MariaDB CI restored (LOG-025, 2026-10-26)

The reviewer declared CORE-03 complete and asked to include MariaDB in CI again, so the SQLite-only condition above no longer applies to the CI gate. `.github/workflows/ci.yml` again runs the SQLite and MariaDB 10.11 matrix, and the dual-database gate is active as a requirement. No new run exists yet: MariaDB compatibility for the CORE-03/04 changes remains unproven, the earlier `40286560448` MariaDB failure has no recorded cause, and no local MariaDB server is installed. Keep SQLite as the local default and do not claim a MariaDB result before the CI run reports one.

The project has not been scaffolded. PHP 8.5.12 and Node 24.22.1 are available locally. Upstream stable versions have advanced since the original PHP 8.3 / Laravel 12 / Livewire 3 / Pest 3 plan.

Breeze 2.4.2 accepts Laravel 13 at the package level but its Livewire installer requests Livewire 3, Volt 1, and Tailwind 3. It cannot scaffold the approved latest-major combination unchanged. The complete official Livewire starter kit also introduces dependencies outside the approved list, so copying that kit wholesale is not the chosen solution.

## Decision

1. Target PHP 8.5 for local, CI, and production; Laravel 13 (`^13.38`), Livewire 4, bundled Alpine, Tailwind 4, Vite 8 with laravel-vite-plugin 3, and Node 24 LTS.
2. Replace Breeze with Fortify 1 and project-owned Livewire/Blade forms and components. Public registration remains disabled; Core owns invitation acceptance. Do not enable Fortify TOTP/passkeys or add Flux/Volt. Optional email OTP remains subject to SEC-2 and Q-M12.
3. Use Pest 5 with pest-plugin-laravel 5, Pint 1, and Larastan 3 / PHPStan 2 at level 6. Use current stable majors of existing approved domain packages as recorded in masterplan section 3.
4. Approve Laravel Boost 2 (`^2.10.3`) as a development dependency for CORE-01, not an application AI feature. Install after scaffolding and select GitHub Copilot. Preserve existing instructions and skills when reviewing generated files.
5. Keep SQLite as default and MariaDB 10.11+ as the compatibility floor. This decision does not upgrade a production DB engine or install application dependencies.

## Compatibility evidence

Snapshot checked on 2026-10-26 using stable Packagist metadata and npm latest tags:

| Component | Stable snapshot | Relevant constraint |
|---|---|---|
| PHP | 8.5.12 | Supported by Laravel 13; aligns local and planned production |
| Laravel | 13.38.0 | PHP `^8.3` |
| Livewire | 4.4.8 | Illuminate supports `^13.0` |
| Fortify | 1.41.0 | PHP `^8.2`; Illuminate supports `^13.0` |
| Pest / Laravel plugin | 5.3.3 / 5.0.1 | PHP `^8.4`; plugin needs Laravel `^13.23.0`; Pest conflicts with Boost <2.6.0 |
| Boost | 2.10.3 | PHP `^8.2`; Illuminate `^11.45.3|^12.41.1|^13.0` |
| Pint / Larastan | 1.32.1 / 3.13.2 | Pint PHP `^8.3.0`; Larastan supports Laravel 13 and PHPStan `^2.3.0` |
| Vite / Laravel Vite plugin | 8.3.5 / 3.2.0 | Plugin accepts Vite `^8.0.0`; Node `^20.19.0` or >=22.12.0 |
| Tailwind / Vite integration | 4.3.3 / 4.3.3 | Integration accepts Vite 8 |
| Activitylog / Media Library | 5.1.3 / 11.23.10 | Both support Laravel 13; Activitylog requires PHP `^8.4` |
| Web Push | 13.0.1 | Laravel 13 path requires `^13.13` |
| Backup / Excel | 10.3.3 / 4.0.6 | Both support Laravel 13 and PHP 8.3+ |
| RRULE / iCalendar | 3.0.0 / 3.3.1 | iCalendar requires PHP `^8.4` |
| Heroicons | 2.7.0 | Illuminate supports `^13.0` |
| ApexCharts | 8.5.0 | Node 24 supported; review upstream distribution license before use |

These are declared constraints, not a full transitive Composer solve. Exact installed versions belong in lockfiles, not an assumption based on this table.

Subsequent evidence (2026-10-26): LOG-004 in the [logbook](../logbook.md) records a successful Composer solve and local platform check. LOG-009 records a standard Laravel scaffold, passing boot tests/build and local HTTP responses. The original decision context above is historical; these checks do not prove Family Hub behavior, MariaDB CI, Linux backup, or Boost MCP connectivity.

LOG-010 subsequently verifies Boost MCP initialization and its documentation tool through a temporary stdio client, plus local foundation tests/lint/Larastan/build. Copilot MCP configuration and a SQLite/MariaDB CI workflow now exist, but the actual Copilot UI connection and remote CI run remain unverified. MCP-only installation was chosen to preserve existing agent guidance; the original architecture decision is unchanged.

LOG-014 adds remote CI evidence: [run 40268711517](https://github.com/shadri-zulkurniadi/Net/actions/runs/40268711517) at `ffe4afe` passes both SQLite/MariaDB jobs, including install, platform requirements, build, tests, Pint, and Larastan. This supersedes the earlier remote-CI limitation only; native UI remains unverified and the approved terminal transport below applies. Local development uses SQLite; Linux deployment/backup and domain schema remain future work.

## Consequences and verification

### Boost transport addendum (reviewer approved 2026-10-26, LOG-013)

The reviewer cannot activate Boost from VS Code's MCP server list and authorizes terminal-mediated MCP use instead. A bounded stdio client spawning `php artisan boost:mcp`, performing initialize/initialized, discovering tools, and successfully calling `search-docs` satisfies the Boost connectivity gate for CORE-01. Native Copilot UI integration remains unverified but is not a blocker. Start the child only for a needed operation, report errors/timeouts, and stop it afterward; no public listener or persistent wrapper service is needed. Existing MCP configuration may remain for later use. Send only generic documentation queries and package context, not project source, household data, or secrets. This addendum supersedes the UI-specific verification requirement below, not the remaining CI or runtime gates.

### Testing addendum (reviewer approved in chat)

Mockery (`mockery/mockery`, development-only) is approved for Laravel's database-testing lifecycle. Authentication tests using `RefreshDatabase` failed before any assertion with `Class "Mockery" not found`; the framework uses Mockery internally during migration setup even when tests declare no mocks. This approval does not add a runtime mocking dependency or change the application stack.

- No existing backend requires migration; implementation must follow Livewire 4 and current package APIs rather than old-version examples.
- At CORE-01, solve dependencies on PHP 8.5 with required extensions, run `composer check-platform-reqs`, build assets, and run tests on SQLite and MariaDB plus Pint and Larastan. Do not suppress platform requirements.
- Verify Fortify routes preserve invite-only registration and the existing authorization model.
- Run `composer require laravel/boost:"^2.10.3" --dev`, then `php artisan boost:install`. Review generated changes and start the resulting MCP server through VS Code's `MCP: List Servers`. Confirm the agent can use its documentation tool.
- Boost can inspect the app and execute tools. Restrict it to trusted development with synthetic data. Do not connect it to production, expose MCP publicly, or send household data/secrets to documentation services.
- Boost is excluded from production dependencies with `composer install --no-dev`. Its availability does not authorize an AI SDK, cloud OCR, or additional application features.
- Runtime compatibility, PHP extensions, backup behavior on Linux, and Copilot MCP startup remain unverified until CORE-01 and the relevant deployment work.

## Sources

- [Laravel 13 support policy](https://laravel.com/docs/13.x/releases)
- [PHP supported versions](https://www.php.net/supported-versions.php)
- [Node release metadata](https://nodejs.org/dist/index.json)
- [Breeze Livewire installer](https://github.com/laravel/breeze/blob/2.x/src/Console/InstallsLivewireStack.php)
- [Fortify documentation](https://laravel.com/docs/13.x/fortify)
- [Official Livewire starter kit manifest](https://github.com/laravel/livewire-starter-kit/blob/main/composer.json)
- [Boost installation and Copilot setup](https://laravel.com/docs/13.x/boost)
- [Packagist metadata API](https://packagist.org/apidoc): checked `https://repo.packagist.org/p2/{vendor}/{package}.json` for the Composer packages listed above.
- [npm registry](https://registry.npmjs.org/): checked latest metadata for Vite, laravel-vite-plugin, Tailwind, @tailwindcss/vite, Alpine, and ApexCharts.
