---
name: Family Hub
description: Warm, organic, mobile-first household ledger for couples managing finance, schedules, and home assets
colors:
  forest-canopy: "#28543e"
  forest-sage: "#bad6ae"
  forest-tint: "#e9efe4"
  warm-linen: "#f6f4ee"
  paper-cream: "#fffefa"
  deep-pine: "#151f1b"
  moss-surface: "#1f2c25"
  pine-ink: "#263c32"
  sage-ink: "#f3f1e8"
  slate-moss: "#637067"
  mist-sage: "#b2beb2"
  parchment-line: "#dedfd4"
  dark-canopy-line: "#3c4b40"
  terracotta-danger: "#ad3f45"
  rose-tint-dark: "#ffafb1"
  ochre-warning: "#87571f"
  amber-tint-dark: "#edc48b"
  fjord-info: "#386578"
  sky-tint-dark: "#a7cad6"
typography:
  display:
    fontFamily: "'Iowan Old Style', 'Palatino Linotype', 'Book Antiqua', Georgia, serif"
    fontSize: "1.875rem"
    fontWeight: 700
    lineHeight: 1.2
    letterSpacing: "-0.02em"
  headline:
    fontFamily: "'Iowan Old Style', 'Palatino Linotype', 'Book Antiqua', Georgia, serif"
    fontSize: "1.25rem"
    fontWeight: 700
    lineHeight: 1.25
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Inter, ui-sans-serif, system-ui, -apple-system, sans-serif"
    fontSize: "1rem"
    fontWeight: 600
    lineHeight: 1.35
    letterSpacing: "-0.01em"
  body:
    fontFamily: "Inter, ui-sans-serif, system-ui, -apple-system, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "0em"
  label:
    fontFamily: "Inter, ui-sans-serif, system-ui, -apple-system, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "0.01em"
rounded:
  input: "0.75rem"
  button: "1rem"
  card: "1.5rem"
  chip: "9999px"
spacing:
  xs: "0.25rem"
  sm: "0.5rem"
  md: "1rem"
  lg: "1.5rem"
  xl: "2rem"
components:
  button-primary:
    backgroundColor: "{colors.forest-canopy}"
    textColor: "#ffffff"
    typography: "{typography.label}"
    rounded: "{rounded.button}"
    padding: "10px 16px"
    height: "44px"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.pine-ink}"
    typography: "{typography.label}"
    rounded: "{rounded.button}"
    padding: "10px 16px"
    height: "44px"
  card-surface:
    backgroundColor: "{colors.paper-cream}"
    textColor: "{colors.pine-ink}"
    rounded: "{rounded.card}"
    padding: "20px"
  input-field:
    backgroundColor: "transparent"
    textColor: "{colors.pine-ink}"
    typography: "{typography.body}"
    rounded: "{rounded.input}"
    padding: "8px 12px"
    height: "44px"
  chip-active:
    backgroundColor: "{colors.forest-canopy}"
    textColor: "#ffffff"
    typography: "{typography.label}"
    rounded: "{rounded.chip}"
    padding: "6px 14px"
    height: "36px"
---

# Design System: Family Hub

## Overview

**Creative North Star: "The Warm Household Ledger"**

Family Hub is designed as a calm, organic, paper-and-ink workspace for couples managing daily home life together. Rather than feeling like an enterprise dashboard or a generic SaaS template, the interface pairs warm linen surfaces with deep forest-green anchors and classic bookish serif headings. Every screen is built mobile-first from `360px` upward so capturing an expense, checking off a chore, or reviewing a bill takes minimal taps with one thumb.

The aesthetic relies on tonal layering, generous corner radii, and clear semantic restraint. Generic SaaS blues and cool slates, heavy 3D drop shadows, and raw Unicode emojis as UI icons are explicitly rejected in favor of warm neutrals, subtle hairline borders, and inline Heroicons SVGs.

**Key Characteristics:**
- Warm linen canvas (`#f6f4ee`) and paper-cream cards (`#fffefa`) in light mode; deep pine (`#151f1b`) and moss surface (`#1f2c25`) in dark mode
- Forest Canopy (`#28543e`) and Forest Sage (`#bad6ae`) as disciplined primary action and active-state anchors
- Local serif display headings paired with clean sans-serif UI copy and `tabular-nums` for all currency figures
- Mobile-first `44px` touch targets, bottom navigation with a center `[+]` FAB, and responsive bottom sheets

## Colors

A warm, botanical palette rooted in paper-and-ink neutrals with forest green as the single primary accent and muted earthy tones for semantic status.

### Primary
- **Forest Canopy** (`#28543e`): Primary action buttons, active navigation pills, selected filter chips, progress bars below 80%, and key brand links in light mode.
- **Forest Sage** (`#bad6ae`): Primary action surfaces (paired with Deep Pine text) and active navigation/accent highlights in dark mode.
- **Forest Tint** (`#e9efe4`): Soft positive callout and tinted badge backing in light mode.

### Neutral
- **Warm Linen** (`#f6f4ee`): Primary application canvas background in light mode.
- **Paper Cream** (`#fffefa`): Elevated card, sidebar, header, bottom navigation, and modal/bottom-sheet surface in light mode.
- **Deep Pine** (`#151f1b`): Primary application canvas background in dark mode and high-contrast text on Forest Sage buttons.
- **Moss Surface** (`#1f2c25`): Elevated card, sidebar, header, bottom navigation, and modal surface in dark mode.
- **Pine Ink** (`#263c32`) / **Sage Ink** (`#f3f1e8`): Primary body and heading text colors for light and dark modes.
- **Slate Moss** (`#637067`) / **Mist Sage** (`#b2beb2`): Secondary metadata, helper text, and inactive navigation labels.
- **Parchment Line** (`#dedfd4`) / **Dark Canopy Line** (`#3c4b40`): Subtle structural dividers and `border-current/10` hairline card borders.

### Semantic
- **Terracotta Danger** (`#ad3f45` light / `#ffafb1` dark): Overdue items, destructive actions, validation errors, and budgets at `>=100%`.
- **Ochre Warning** (`#87571f` light / `#edc48b` dark): Due-today items, offline banner, and budgets at `80%–99%`.
- **Fjord Info** (`#386578` light / `#a7cad6` dark): Upcoming-soon items (`<=3 days`) and transfer/informational indicators.

**The Single Canopy Accent Rule.** Reserve Forest Canopy (`#28543e` / `#bad6ae`) for primary actions, active navigation, and positive progress; never introduce competing brand hues (`indigo-*`, `emerald-600`, `blue-600`) for primary chrome.

## Typography

**Display & Headline Font:** `'Iowan Old Style', 'Palatino Linotype', 'Book Antiqua', Georgia, serif` (zero external font CDN requests)
**Body & Label Font:** `Inter, ui-sans-serif, system-ui, -apple-system, sans-serif`

**Character:** Editorial serif headings bring warmth and domestic calm to page titles and dialog headers, while crisp sans-serif body text and `tabular-nums` figures keep financial totals and dates effortlessly scannable on small screens.

### Hierarchy
- **Display** (700, `1.5rem–1.875rem`, `line-height: 1.2`, `letter-spacing: -0.02em`, serif): Page-level greeting and primary `<h1>` headings.
- **Headline** (700, `1.125rem–1.25rem`, `line-height: 1.25`, serif): Bottom-sheet/dialog titles and mobile top-bar household title.
- **Title** (600, `1rem`, `line-height: 1.35`, sans-serif): Card header titles (`<x-card>`) and section subheadings.
- **Body** (400, `1rem`, `line-height: 1.5`, sans-serif): Default readable prose, descriptions, and form inputs.
- **Label** (600, `0.75rem`, `line-height: 1.25`, sans-serif): Badges, filter chips, form labels, and mobile bottom-nav captions (`11px`).

**The Tabular Currency Rule.** Every Rupiah (`Rp`) amount rendered via `<x-money>` or `<x-input-money>` must use `tabular-nums` so columns of figures align cleanly across mobile cards and reports.

## Layout

Mobile-first from a `360px` viewport with zero horizontal overflow (`overflow-x: hidden`). Content is single-column on mobile and scales to responsive 2- and 3-column grids inside a centered `max-w-6xl` container (`px-4 sm:px-6 lg:px-8`) on desktop.

- **Mobile (`<1024px`):** Sticky translucent top bar (`min-h-16`), main content with `pb-28` clearance, and a fixed 5-slot bottom navigation bar (`min-h-[72px]` with `env(safe-area-inset-bottom)` padding): `Beranda` · `Keuangan` · `[+]` (elevated center FAB) · `Kalender` · `Lainnya`.
- **Desktop (`>=1024px`):** Fixed `256px` (`w-64`) left sidebar navigation, sticky top header with `Tambah Cepat` action, and `lg:ml-64` main shell.

## Elevation & Depth

Tonal layering on Warm Linen (`#f6f4ee`) and Deep Pine (`#151f1b`) with subtle ambient lift (`shadow-xs`) rather than heavy drop shadows. Surfaces separate primarily through background contrast (`#fffefa` over `#f6f4ee`) and hairline borders (`border-current/10`).

### Shadow Vocabulary
- **Card Ambient Lift** (`box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05)`): Subtle rest-state separation on `<x-card>` and primary buttons.
- **FAB Prominence** (`box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1)`): Lifts the center mobile `[+]` FAB above the bottom navigation bar.
- **Dialog Overlay** (`box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25)`): Focuses native `<dialog>` modals and bottom sheets over a `backdrop-blur-xs` scrim.

**The Quiet Surface Rule.** Cards and list rows stay flat at rest with hairline borders (`border-current/10`) and `shadow-xs`; reserve deeper shadows exclusively for floating FABs and modal dialogs.

## Shapes

Generous, approachable corner radii scale proportionally with container size:
- **Cards & Dialogs:** Large rounded corners (`24px` / `1.5rem`, `rounded-3xl`) with `1px` `border-current/10` strokes; mobile bottom sheets flatten their bottom corners (`rounded-t-3xl rounded-b-none`).
- **Buttons & Nav Pills:** Medium-large rounded corners (`16px` / `1rem`, `rounded-2xl`).
- **Inputs & Icon Badges:** Soft rectangular corners (`12px` / `0.75rem`, `rounded-xl`).
- **Status Badges, Filter Chips & Avatars:** Full pill/circle (`9999px`, `rounded-full`).

## Components

### Buttons
Tactile, thumb-friendly controls with a minimum `44px` (`min-h-11`) hit target and smooth `150–200ms` opacity/color transitions.
- **Shape:** Rounded rectangle (`16px` / `rounded-2xl`).
- **Primary:** Forest Canopy (`#28543e`) fill with `#ffffff` text in light mode; Forest Sage (`#bad6ae`) fill with Deep Pine (`#151f1b`) text in dark mode; `hover:opacity-90` and `active:scale-95`.
- **Secondary / Ghost:** Transparent or `bg-current/5` fill with `border-current/15`–`20` stroke and `hover:bg-current/10`.

### Cards / Containers (`<x-card>`)
Calm paper containers that frame module summaries, forms, and agenda lists.
- **Corner Style:** `24px` (`rounded-3xl`).
- **Background:** Paper Cream (`#fffefa`) in light mode; Moss Surface (`#1f2c25`) in dark mode.
- **Border & Shadow:** `1px` `border-current/10` with `shadow-xs`.
- **Internal Padding:** `20px` (`p-5`).

### Inputs / Fields (`<x-input-money>`, form controls)
Clear top-aligned labels with inline error messages below in Terracotta Danger (`#ad3f45` / `#ffafb1`).
- **Style:** `1px` `border-current/25` stroke, transparent background, `12px` (`rounded-xl`) radius, `min-h-11` (`44px`) height, `px-3`.
- **Focus:** `2px` visible `outline-current` (`outline-offset-2`) or `#28543e` / `#bad6ae` border emphasis.

### Chips & Status Badges (`<x-filter-chips>`, `<x-badge-status>`)
- **Filter Chips:** `rounded-full` (`9999px`), `min-h-9` (`36px`), `px-3.5 py-1.5`; active state uses Forest Canopy / Forest Sage fill; inactive uses `bg-current/5` with `border-current/15`.
- **Status Badges:** `rounded-full` (`9999px`), `px-2.5 py-0.5`, `text-xs font-semibold`, semantic `15%`–`20%` tinted fill with `20%`–`30%` border (`Terlambat` danger, `Hari ini` warning, `Segera` info, `Selesai` primary).

### Navigation & Bottom Sheet (`<x-app-layout>`, `<x-fab>`, `<x-bottom-sheet>`)
- **Bottom Nav (`<1024px`):** Fixed `bg-[#fffefa]/95` (`dark:bg-[#1f2c25]/95`) with `backdrop-blur-md`, 5 items, and elevated `-mt-4` `52px` (`size-13`) center FAB.
- **Bottom Sheet / Modal:** Native `<dialog>` that docks to the bottom edge on `max-sm` (`rounded-t-3xl`) and centers at `min(520px, calc(100%-24px))` on larger viewports.

## Do's and Don'ts

### Do:
- **Do** reuse Core Blade primitives (`<x-card>`, `<x-badge-status>`, `<x-empty-state>`, `<x-filter-chips>`, `<x-money>`, `<x-date>`, `<x-bottom-sheet>`, `<x-confirm>`) across every module.
- **Do** enforce minimum `44px` (`min-h-11`) touch targets on interactive mobile buttons, links, and list checkboxes.
- **Do** use Heroicons SVG (`<x-heroicon-o-*>` / `<x-heroicon-s-*>`) consistently for all UI iconography and keep all UI copy in Indonesian.
- **Do** pair `prefers-reduced-motion` support with lightweight `transform`/`opacity` transitions (`150–200ms`).

### Don't:
- **Don't** introduce off-system palettes (`slate-*`, `stone-*`, `indigo-*`, `bg-white`, `emerald-600`) or Boxicons (`<i class='bx ...'>`) in application views.
- **Don't** use raw Unicode emojis (`📋`, `🛒`, `⚠️`, `🛠️`) as UI icons.
- **Don't** load external font or CSS CDNs, or add heavy 3D shadows and layout-shifting hover scales.
