# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Primary users:** Husband and wife managing a shared household together (maximum 2 members per household: `owner` and `partner`).
- **Secondary users:** System Admin (self-hosted server owner) for household provisioning and invitation management only, with no access to household domain data.

## Product Purpose

Family Hub is a self-hosted, mobile-first Progressive Web App (PWA) for couples to manage daily household operations in one place:
1. **Finance** — accounts, transactions, category budgets, 5-bucket monthly allocations, recurring bills/income, loans & installments, and reports (IDR).
2. **Organizer** — shared household tasks, notes, shopping lists, unified calendar (`.ics` export), and reminders.
3. **Asset & Maintenance** — household/vehicle inventory, warranties/documents, preventive maintenance schedules, service history, vendors, and issue reports.

Success means fast daily capture on a phone (≤ 3 taps for common actions like recording an expense) and zero missed bills, maintenance schedules, or shared chores.

## Positioning

A private, self-hosted household hub where Finance, Organizer, and Asset Management are natively integrated rather than siloed:
- Loan installments and recurring bills automatically populate the monthly finance plan, appear on the unified calendar, and trigger H-3 reminders.
- Recording an asset service automatically logs an expense transaction and recalculates the next maintenance due date.
- Completing a shopping list can create a corresponding expense transaction.
- Full financial transparency between spouses (no private wallets) with strict tenant isolation between households.

## Operating Context

- **Mobile-device-first:** Designed from a `360px` mobile viewport upward for one-handed use (bottom navigation bar, center `[+]` FAB, bottom sheets, sticky bottom save actions, minimum `44px` touch targets); desktop (`≥1024px`) is an adaptation with a left sidebar.
- **Locale & currency:** Indonesian UI (`lang/id/*.php`, ready for `lang/en`), Indonesian date/time formatting (`27 Okt 2026`, `14.30`), and integer IDR currency only.
- **Hosting & connectivity:** Self-hosted on bare-metal Linux (Nginx + PHP-FPM + SQLite/MariaDB) exposed via HTTPS tunnel; PWA supports **read-only offline mode** (network-first cache; mutations disabled when offline).
- **Access model:** Public registration is disabled (`REGISTRATION_MODE=invite`); households are provisioned by System Admin and partners join via single-use 7-day invitation tokens.

## Capabilities and Constraints

- **Included:** Core household tenancy & reminders, Finance, Organizer, Asset & Maintenance, Telegram bot lightweight expense entry, local receipt OCR, and CSV/ICS exports.
- **Explicit non-goals (without a new ADR):** Automated bank scraping/open banking, 2-way Google Calendar sync, offline writes, child/helper/technician/guest roles, native Android/iOS apps, investment/stock/crypto tracking, and multi-currency support.
- **Technical constraints:** No external font/CSS CDNs; server-friendly lightweight stack (Laravel 13, Livewire 4, Alpine.js, Tailwind CSS v4).

## Brand Commitments

- **Product name:** Family Hub.
- **Language & copy:** All user-facing UI text in Indonesian via `lang/id/*.php` (avoid hardcoded English labels in views).

## Evidence on Hand

- Product specifications: `docs/00-MASTERPLAN.md`, `docs/01-MODULE-FINANCE.md`, `docs/02-MODULE-ORGANIZER.md`, `docs/03-MODULE-ASSET.md`, `docs/04-IMPROVEMENT-PLAN.md`, `GLOSSARY.md`.
- Architecture decisions: `docs/adr/ADR-011-current-stack.md`, `ADR-012`, `ADR-013`.
- Incumbent web application: `routes/web.php`, `routes/modules/*.php`, `resources/views/`.

## Product Principles

1. **Mobile-first, one-handed speed:** Optimize every primary workflow for thumb reach and minimal taps on a phone screen first.
2. **Connected household modules:** Prefer automatic cross-module links (Finance ↔ Organizer ↔ Asset) over duplicate manual entry.
3. **Full spouse transparency, strict household isolation:** Both partners see all household data, while separate households and the System Admin remain strictly isolated.
4. **Self-hosted simplicity and privacy:** Keep dependencies lean, offline mode safely read-only, and all data on the owner's server.

## Accessibility & Inclusion

- Keyboard navigation with skip link (`Lewati ke konten utama`), visible focus indicators, minimum `44px` (`h-11`) touch targets, `prefers-reduced-motion` support, and automatic + manual light/dark theme switching.
