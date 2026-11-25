# Family Hub UI Unification Across Core Shell, Dashboard, and All Modules

Written against: `2ae003c`

## Evidence chain

- Surface: Core App Shell & Dashboard (`/`, `resources/views/components/app-layout.blade.php`, `resources/views/dashboard.blade.php`, `resources/views/components/copyable-link.blade.php`), Finance (`routes/modules/finance.php`, `resources/views/modules/finance/**`), Organizer (`routes/modules/organizer.php`, `resources/views/modules/organizer/**`), and Asset (`routes/modules/asset.php`, `resources/views/modules/asset/**`).
- Problem:
  1. **Core & Dashboard**: `dashboard.blade.php` renders `{{ strtoupper($alert->level) }}` inside `<x-badge-status>` (producing English `DANGER`/`WARNING`/`INFO` instead of Indonesian `Bahaya`/`Peringatan`/`Info`). `app-layout.blade.php`, `dashboard.blade.php`, and `copyable-link.blade.php` use off-token `#059669`/`#10b981` instead of `#28543e`/`#bad6ae`.
  2. **Dashboard Widgets**: `modules/organizer/widgets/summary.blade.php`, `modules/asset/widgets/attention.blade.php`, and `modules/finance/widgets/summary.blade.php` bypass `<x-card>` (`rounded-3xl`, `#fffefa`/`#1f2c25`) and use mismatched palettes (`slate-*`, `stone-*`, `emerald-600`) and raw emojis (`📋`, `🛒`, `🎉`, `🛠️`).
  3. **Finance Module**: Views (`overview.blade.php`, `transactions/index.blade.php`, and sibling Finance views) use `emerald-600`, `rose-600`, `blue-600`, and hand-rolled `rounded-xl` filter buttons / empty states instead of §9.4 semantic tokens (`#28543e`/`#bad6ae`, `#ad3f45`/`#ffafb1`, `#386578`/`#a7cad6`) and §9.5 primitives (`<x-card>`, `<x-empty-state>`, `<x-filter-chips>`).
  4. **Organizer Module**: All 9 views (`tasks/*`, `calendar/index.blade.php`, `notes/*`, `shopping/*`, `reminders/index.blade.php`, `widgets/summary.blade.php`) use a foreign `bg-white dark:bg-slate-800` + `indigo-*` theme, inline `<svg>`, `<i class='bx ...'>`, and raw emojis (`📋`, `🛒`, `🎉`, `🎂`, `💍`, `⚠️`, `✓`, `✕`) instead of §9.4 warm organic tokens, `<x-card>`, and Heroicons.
  5. **Asset Module**: All 17 views (`assets/*`, `maintenance/*`, `documents/*`, `issues/*`, `vendors/*`, `locations/index.blade.php`, `services/form.blade.php`, `widgets/attention.blade.php`) use a foreign `bg-white dark:bg-stone-900` + `emerald-600` theme, `<i class='bx ...'>`, raw emojis (`⚠️`, `🔧`, `📄`, `📦`, `✨`, `🚨`, `📍`, `👨‍🔧`, `🛠️`), and raw `Rp {{ number_format(...) }}` / `->translatedFormat(...)` instead of §9.4 tokens, `<x-card>`, `<x-badge-status>`, `<x-empty-state>`, `<x-money>`, `<x-date>`, and Heroicons.
- Design evidence:
  - `docs/00-MASTERPLAN.md` §9.4 (Design Tokens), §9.5 (Komponen Blade Wajib & Heroicons), §8 CS-2 (Indonesian UI copy), §8 CS-10 (`<x-date>` / `<x-datetime>`).
  - `resources/css/app.css` (`--bg-canvas: #f6f4ee`/`#151f1b`, `--bg-surface: #fffefa`/`#1f2c25`, `--color-ink: #263c32`/`#f3f1e8`, `--color-muted: #637067`/`#b2beb2`, `--color-brand: #28543e`/`#bad6ae`, `--color-danger: #ad3f45`/`#ffafb1`, `--color-warning: #87571f`/`#edc48b`, `--color-info: #386578`/`#a7cad6`).
- Owner: `resources/views/components/app-layout.blade.php` and `resources/views/components/*.blade.php` (Core shell & primitives); module views under `resources/views/modules/{finance,organizer,asset}/`.
- Scope and affected surfaces: All authenticated Core, Dashboard, Finance, Organizer, and Asset Blade views.
- Uncertainty: None for token, primitive, copy, and icon rules (all explicitly documented in `docs/00-MASTERPLAN.md` §8–§9). Verify existing Livewire/Pest feature tests after each module batch to ensure no test asserts removed CSS classes or raw emoji strings.

## Design decision

Unify all Core, Dashboard, Finance, Organizer, and Asset views onto the single documented Family Hub design system (`docs/00-MASTERPLAN.md` §9.4–§9.5 and §8 CS-2/CS-10) by reusing existing Core Blade components (`<x-card>`, `<x-badge-status>`, `<x-empty-state>`, `<x-filter-chips>`, `<x-money>`, `<x-date>`, `<x-datetime>`, `<x-progress-bar>`, `<x-confirm>`, `<x-bottom-sheet>`) and Heroicons (`<x-heroicon-o-*>`, `<x-heroicon-s-*>`), eliminating parallel `slate-*`, `stone-*`, `indigo-*`, `emerald-600`, Boxicons, and emoji patterns.

## Reuse

- `resources/views/components/card.blade.php` (`<x-card>`): `rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-xs transition-colors dark:bg-[#1f2c25]`
- `resources/views/components/badge-status.blade.php` (`<x-badge-status>`): semantic status pills (`danger`, `warning`, `info`, `done`) with Indonesian default labels
- `resources/views/components/empty-state.blade.php` (`<x-empty-state>`): dashed `rounded-3xl` empty states with Heroicons and primary CTA
- `resources/views/components/filter-chips.blade.php` (`<x-filter-chips>`): `rounded-full` filter pills with `#28543e`/`#bad6ae` active state
- `resources/views/components/money.blade.php` (`<x-money>`), `date.blade.php` (`<x-date>`), `datetime.blade.php` (`<x-datetime>`), `progress-bar.blade.php` (`<x-progress-bar>`)
- Exemplar: `resources/views/dashboard.blade.php` (for `<x-card>` + `<x-datetime>` composition) and `resources/views/components/app-layout.blade.php` (for `#28543e`/`#bad6ae` active states and Heroicons).

No new shared primitive is required; the existing `resources/views/components/` suite already expresses every required surface, badge, chip, money, date, and empty-state contract.

## Changes

1. **Core Shell & Dashboard (`resources/views/dashboard.blade.php`, `resources/views/components/app-layout.blade.php`, `resources/views/components/copyable-link.blade.php`)**
   - Change:
     - In `dashboard.blade.php` lines 26–28, remove `{{ strtoupper($alert->level) }}` so `<x-badge-status :status="$badgeColor" />` renders its Indonesian default label (`Bahaya`, `Peringatan`, `Info`).
     - Replace `#059669`/`#10b981` with `#28543e`/`#bad6ae` in `app-layout.blade.php` (line 251), `dashboard.blade.php` (line 34), and `copyable-link.blade.php` (lines 25, 29, 43, 49, adding `dark:text-[#151f1b]` on `#bad6ae` filled button).
   - Preserve: `member-avatar.blade.php` per-member identity fallback color (`#059669` / `#7C3AED`), all existing routes, `wire:*` directives, and Alpine bindings.
   - Verify: Dashboard alert badges show Indonesian labels; primary accents across shell, dashboard, and copyable-link match `#28543e` (light) / `#bad6ae` (dark).

2. **Dashboard Module Widgets (`resources/views/modules/finance/widgets/summary.blade.php`, `resources/views/modules/organizer/widgets/summary.blade.php`, `resources/views/modules/asset/widgets/attention.blade.php`)**
   - Change:
     - Wrap each widget in `<x-card>` (or match `rounded-3xl border border-current/10 bg-[#fffefa] p-5 shadow-xs transition-colors dark:bg-[#1f2c25]`).
     - Replace `slate-*`, `stone-*`, `indigo-*`, `sky-*`, `emerald-*`, `rose-*`, `amber-*`, `orange-*` with §9.4 tokens (`#263c32`/`#f3f1e8`, `#637067`/`#b2beb2`, `#28543e`/`#bad6ae`, `#87571f`/`#edc48b`, `#ad3f45`/`#ffafb1`, `#386578`/`#a7cad6`, `bg-current/5`, `border-current/10`).
     - Replace emojis (`📋`, `🛒`, `🎉`, `🛠️`) with `<x-heroicon-o-check-circle>`, `<x-heroicon-o-shopping-bag>`, `<x-heroicon-o-calendar-days>`, and `<x-heroicon-o-wrench>`.
   - Preserve: All widget links, counts, `<x-money>` formatting, and conditional rendering when modules are disabled.
   - Verify: All three widgets visually match `<x-card>` blocks on `/` in light and dark modes with zero emojis.

3. **Finance Module (`resources/views/modules/finance/**`)**
   - Change:
     - Across all 14 Finance views (`overview.blade.php`, `transactions/{index,form,show}.blade.php`, `accounts/index.blade.php`, `budgets/index.blade.php`, `categories/index.blade.php`, `plan/{index,_obligation_row}.blade.php`, `recurring/index.blade.php`, `loans/{index,form,show}.blade.php`, `reports/index.blade.php`):
       - Replace `bg-emerald-600`/`hover:bg-emerald-700`/`text-emerald-600`/`dark:text-emerald-400` with `#28543e` / `dark:bg-[#bad6ae] dark:text-[#151f1b]` (for primary buttons) and `text-[#28543e] dark:text-[#bad6ae]` (for links/highlights).
       - Replace `rose-*`, `amber-*`, `blue-*` status/action accents with `#ad3f45`/`#ffafb1`, `#87571f`/`#edc48b`, `#386578`/`#a7cad6`, and use `<x-badge-status>` for status pills.
       - Replace hand-rolled empty states (e.g., `overview.blade.php` line 82) with `<x-empty-state>` and hand-rolled `rounded-2xl` cards with `<x-card>`.
       - Replace `✓ Mode Split Aktif` text check in `transactions/form.blade.php` line 208 with `<x-heroicon-s-check class="size-4" />`.
   - Preserve: All Livewire `wire:*` bindings, `x-input-money` / `x-money` / `x-period-switcher` / `x-progress-bar` usage, CSV export links, and form validation blocks.
   - Verify: Zero `emerald-600`/`rose-600`/`slate-*`/`stone-*` classes remain in `resources/views/modules/finance/`.

4. **Organizer Module (`resources/views/modules/organizer/**`)**
   - Change:
     - Across all 8 remaining Organizer views (`tasks/{index,form}.blade.php`, `calendar/index.blade.php`, `notes/{index,editor}.blade.php`, `shopping/{index,show}.blade.php`, `reminders/index.blade.php`):
       - Replace `bg-white dark:bg-slate-800`, `border-slate-200 dark:border-slate-700`, `text-slate-900 dark:text-white`, `text-slate-500 dark:text-slate-400`, and `bg-indigo-600` with `<x-card>` / `#fffefa`/`#1f2c25`, `#263c32`/`#f3f1e8`, `#637067`/`#b2beb2`, `#28543e`/`#bad6ae`, and `border-current/10` / `bg-current/5`.
       - Replace inline `<svg>` icons (`tasks/index.blade.php`), `<i class='bx ...'>` (`calendar/index.blade.php`), and emojis/unicode symbols (`🎂`, `💍`, `⚠️`, `✓`, `✕`) with `<x-heroicon-o-*>` / `<x-heroicon-s-*>`.
       - Use `<x-empty-state>` for empty states and `<x-badge-status>` for due/status badges.
   - Preserve: All Livewire `wire:click`, `wire:model`, `wire:submit.prevent` bindings, 44px (`h-11` / `min-h-[44px]`) touch targets, and `.ics` export link.
   - Verify: Zero `slate-*`, `indigo-*`, `bg-white`, `<i class='bx`, or emojis remain in `resources/views/modules/organizer/`.

5. **Asset Module (`resources/views/modules/asset/**`)**
   - Change:
     - Across all 16 remaining Asset views (`assets/{index,show,form}.blade.php`, `maintenance/{index,form}.blade.php`, `documents/{index,show,form}.blade.php`, `issues/{index,show,form}.blade.php`, `vendors/{index,show,form}.blade.php`, `locations/index.blade.php`, `services/form.blade.php`):
       - Replace `bg-white dark:bg-stone-900`, `border-stone-200 dark:border-stone-800`, `text-stone-900 dark:text-white`, `text-stone-500 dark:text-stone-400`, and `bg-emerald-600` with `<x-card>` / `#fffefa`/`#1f2c25`, `#263c32`/`#f3f1e8`, `#637067`/`#b2beb2`, `#28543e`/`#bad6ae`, and `border-current/10` / `bg-current/5`.
       - Replace `<i class='bx ...'>` and all emojis (`⚠️`, `🔧`, `📄`, `📦`, `✨`, `🚨`, `📍`, `👨‍🔧`) with Heroicons (`<x-heroicon-o-*>` / `<x-heroicon-s-*>`). (Star ratings `★`/`☆` in `vendors/*` may use `<x-heroicon-s-star>` / `<x-heroicon-o-star>`.)
       - Replace raw `Rp {{ number_format(...) }}` with `<x-money :amount="..." />` and raw `->translatedFormat(...)` dates with `<x-date :value="..." />`, and use `<x-badge-status>` and `<x-empty-state>` where applicable.
   - Preserve: All Livewire `wire:*` bindings, route links, and 44px touch targets.
   - Verify: Zero `stone-*`, `emerald-600`, `bg-white`, `<i class='bx`, or emojis remain in `resources/views/modules/asset/`.

## Scope

- Inherit: All authenticated routes rendered inside `x-app-layout` (`/`, `/finance/*`, `/calendar`, `/tasks/*`, `/notes/*`, `/shopping/*`, `/reminders`, `/assets/*`).
- Verify: `resources/views/livewire/settings/*`, `resources/views/livewire/notifications/*`, and `resources/views/auth/*` (already separate from module views; verify no regressions from `copyable-link.blade.php` or `app-layout.blade.php`).
- Exclude: `resources/views/components/member-avatar.blade.php` member identity fallback colors (`#059669` / `#7C3AED`); backend PHP logic, routes, migrations, and `mockup/`.

## Validation

- Product: All Core, Dashboard, Finance, Organizer, and Asset pages render without missing components, broken Livewire bindings, or untranslated alert badges.
- Interface: Inspect `/`, `/finance`, `/finance/transactions`, `/tasks`, `/calendar`, `/assets`, and `/assets/maintenance` at 375px and 1440px in light and dark mode (no horizontal scroll, consistent `#f6f4ee`/`#fffefa` light and `#151f1b`/`#1f2c25` dark surfaces).
- System: Grep `resources/views/modules/` for `slate-`, `stone-`, `indigo-`, `bg-white`, `emerald-600`, `<i class='bx`, and emojis → 0 matches.
- Repository: `composer test && vendor/bin/pint --test && vendor/bin/phpstan analyse && npm run build` → all pass.

## Stop conditions

- Stop if a Blade view or Livewire component test explicitly asserts a removed Tailwind utility class or emoji string that requires updating test expectations beyond the view layer, or if a module view relies on a custom non-Heroicon icon not present in `blade-ui-kit/blade-heroicons`.

## Design documentation

- After acceptance and validation: Sync `docs/logbook.md`, `docs/development-plan.md`, and `docs/pending.md` per `development-docs-sync` if any documented UI standard or component usage notes change. No changes to `docs/00-MASTERPLAN.md` §9 are needed because this plan brings implementation into conformance with the existing §9 standard.
