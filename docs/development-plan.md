# Development Setup dan Rencana - Family Hub

**Baseline:** 2026-10-26
**Status:** CORE-01 diterima untuk kelanjutan development (LOG-015). CORE-03 dinyatakan selesai reviewer (LOG-025). Tip `main`/`core-03-support` adalah `d4a6986`; kode CORE-04 (`731fb45`) lulus kedua job CI SQLite/MariaDB (LOG-031). LOG-030 menambahkan bootstrap household dan konteks tenant per request; LOG-032 memulai CORE-05 dengan backend undangan partner. CORE-04 tetap parsial di sisi konkurensi dan job context; CORE-05 belum punya email, route registrasi, atau UI.

**CI kedua DB (LOG-029, LOG-031):** job SQLite dan MariaDB lulus untuk `6d6f7bc` (144 test PHP/1562 assertions) dan untuk `731fb45` (156 test PHP/1605 assertions), termasuk migrasi, test JS, Pint, Larastan, dan build. Perbaikan collation LOG-028 dan nama index LOG-027 terbukti pada run nyata. Tidak ada deployment atau UAT.

**Publikasi dan merge (LOG-026):** `core-03-support` di-push (`2137b2b..bcbbe86`) dan `main` di-fast-forward ke SHA yang sama; kedua ref identik. Workflow SQLite/MariaDB berjalan pada push tersebut, tetapi statusnya belum dapat dipantau dari mesin ini (repo privat, tanpa GitHub CLI/API terautentikasi), jadi tidak ada klaim lulus untuk MariaDB maupun SQLite remote. CORE-04 tetap parsial: bootstrap household/history, middleware/job context, admission CORE-05, dan konkurensi belum selesai. Ini bukan acceptance UAT/produksi dan bukan izin deploy.

**Perbaikan kompatibilitas MariaDB (LOG-027):** nama index unik gabungan pada `household_finance_period_settings` melebihi batas identifier 64 karakter MariaDB/MySQL (66 karakter) dan tidak dipotong Laravel; index kini bernama eksplisit `household_period_unique`. Test lokal tetap lulus; bukti MariaDB menunggu run CI berikutnya.

**Perbaikan foreign key MariaDB (LOG-028):** CI `bcbbe86` membuktikan kegagalan MariaDB sebenarnya: DDL mentah `household_members` mewarisi collation default server, tidak cocok dengan `utf8mb4_unicode_ci` pada tabel yang dibuat Laravel, sehingga FK `errno 150`. Tabel kini memakai charset/collation dari konfigurasi koneksi pada driver MySQL; SQLite tidak berubah. Masalah index LOG-027 adalah blocker berikutnya yang belum tercapai. Verifikasi MariaDB tetap menunggu run baru.

**Keputusan sebelumnya (LOG-025):** reviewer menyatakan CORE-03 selesai dan meminta MariaDB dimasukkan kembali ke CI. Matriks SQLite/MariaDB dipulihkan di `.github/workflows/ci.yml`; penundaan LOG-023 berakhir hanya untuk gate CI, sementara kegagalan lama `40286560448` tetap tidak terdokumentasi penyebabnya.

**Keputusan sebelumnya (LOG-023, historis):** reviewer menunda MariaDB dan menjadikan SQLite satu-satunya target CI aktif. `core-01-foundation` sudah di-fast-forward ke main tanpa membawa `core-03-support`. Branch support harus mengintegrasikan main dan lulus pemeriksaan baru sebelum evaluasi merge; kegagalan MariaDB lama tidak boleh disebut lulus.

**Integrasi support (LOG-024):** `main` di-merge ke `core-03-support` secara lokal (merge commit `1cd85ae`); konflik dokumen diselesaikan dengan mempertahankan histori LOG-015 sampai LOG-023 dan keputusan SQLite terbaru. CI branch support saat itu diselaraskan ke SQLite-only plus `npm test`; matriks kedua DB dipulihkan pada LOG-025. Setelah merge 139 test/1546 assertion lulus; hasil CORE-03B dicatat pada paragraf checkpoint di bawah.

**Arahan terbaru (LOG-014):** SQLite dipilih untuk development; tidak memasang MariaDB lokal atau menghapus matriks kompatibilitas CI. Reviewer memilih menuntaskan CI CORE-01 sebelum CORE-03 dan mengizinkan branch khusus, commit, serta push fondasi ke repository miliknya (bukan ke main). Hasil remote harus dicatat setelah run selesai, bukan diasumsikan dari test lokal.

**Kelanjutan (LOG-015):** setelah CI kedua DB lulus dan reviewer meminta melanjutkan sesuai plan, CORE-01 diterima untuk melanjutkan development (bukan acceptance produksi/UAT). CORE-03 dimulai pada branch lokal `core-03-support` dari fondasi yang telah diverifikasi; checkpoint pertama adalah `Money`. PeriodResolver, tenant support, dan komponen Blade belum selesai. Tidak ada merge ke main atau izin publikasi otomatis task baru.

**Verifikasi checkpoint Money:** 41 kasus unit/46 assertion lulus; regression total 59 test/112 assertion, Pint, dan Larastan lulus lokal. CI task baru belum dijalankan. Sisa CORE-03 dikerjakan sebelum beralih ke CORE-04; status acceptance CORE-01 lama di bagian historis checklist telah digantikan oleh keputusan LOG-015 ini.

**Checkpoint periode (LOG-016):** `Period` immutable dan `PeriodResolver` berbasis history/timezone tersedia dengan current, fromKey, forDate, previous, next. Test meliputi S=1/25/28, tahun kabisat/akhir tahun, transisi pendek/panjang, validasi history/key, timezone, dan kontinuitas 400 hari; regression 73 test/1362 assertion, Pint, Larastan lulus. Ini mesin perhitungan, belum integrasi household/DB: factory `for($household)` dan tenant support memerlukan model CORE-04. Komponen uang/tanggal juga belum tersedia. CORE-03 tetap parsial; berikutnya komponen bersama dan penyelarasan dependensi tenant dengan CORE-04, tanpa menyatakan integrasi sudah selesai.

**Checkpoint komponen (LOG-017):** `x-money` dan `x-date` tersedia dengan 9 test komponen/22 assertion; regression total 82 test/1384 assertion, Pint/Larastan/build lulus. Browser fixture output Blade + stylesheet lokal diperiksa pada 360px/1280px light/dark, tanpa halaman demo/route permanen. `x-date` membedakan tanggal kalender dengan instant/timezone; pemanggil nantinya wajib memasok timezone household. Berikutnya `x-input-money` dengan test Livewire/browser; integrasi tenant/history household tetap belum ada. CORE-03 belum selesai.

**Checkpoint input uang (LOG-018):** reviewer memilih integer Livewire dengan batas aman browser ±9.007.199.254.740.991. `x-input-money`, parser JS berbasis BigInt, dan rule server SafeRupiah tersedia; input buruk/kosong mengosongkan model. Form pemanggil harus memakai required/nullable + rule ini dan menampilkan error server. Hasil lokal 94 test PHP/1407 assertion, 4 test JS (`npm test`), Pint/Larastan/build lulus. Fixture Livewire loopback membuktikan binding, submit, reset server, batas presisi dan error DOM; interaksi klik/keyboard tool tidak konsisten sehingga sebagian diverifikasi dengan event/panggilan Livewire programatik, bukan bukti pointer/keyboard penuh. Input dan pesan error tidak overflow pada 360px/1280px light/dark; fixture/server sementara dibersihkan. CI kini mencantumkan npm test tetapi perubahan CORE-03 belum di-push/run. Sisa CORE-03: tenant support dan adapter household; perlu menyelaraskan dependensi CORE-04 sebelum menutup task.

Dokumen ini menggabungkan setup environment aktual dan rencana implementasi. Riwayat pekerjaan tetap dicatat di [logbook](logbook.md); keputusan stack mengikuti [ADR-011](adr/ADR-011-current-stack.md) dan [Masterplan](00-MASTERPLAN.md).

**Keputusan terbaru dan status aktif (LOG-019):** reviewer menyetujui CORE-03A → CORE-04 → CORE-03B. Catatan sebelumnya yang melarang mulai CORE-04 sebelum integrasi tenant selesai digantikan urutan ini; gap pointer/keyboard tetap terbuka. CORE-04 dimulai sebagai checkpoint skema/model pada branch kerja lokal yang sama, tanpa mencampur ID task dalam commit/PR (belum ada publikasi task baru). User dan sessions kini menggunakan ULID, tersedia Household, HouseholdMember, HouseholdFinancePeriodSetting, enum MemberRole dan constraint slot/unique membership. Model bukan lagi placeholder integer. Migrasi User awal diubah hanya karena belum ada DB aplikasi yang dimigrasikan atau rilis produksi; database integer yang sudah berisi data tidak memiliki jalur upgrade pada checkpoint ini. Test SQLite in-memory saja; tidak membuat DB aplikasi lokal. Hasil lokal: 101 test PHP/1421 assertion, 4 test JS, Pint/Larastan lulus. Policy, action/transaksi membership, bootstrap household/history dan konkurensi SQLite/MariaDB belum selesai; berikutnya tutup permukaan akses/penulisan tersebut sebelum mengklaim CORE-04 lengkap, lalu integrasikan tenant/resolver 03B.

## Setup environment

**Checkpoint policy (LOG-020):** HouseholdPolicy didaftarkan eksplisit ke Laravel Gate. Owner/partner mengikuti matriks Masterplan untuk view/update/invite/cancel/remove/transfer/delete/leave; outsider, guest, dan admin tanpa membership ditolak. Query membership terbaru menghindari akses dari role/relasi stale dan household terhapus. 19 test policy/46 assertion serta regression total 120 test/1467 assertion, Pint/Larastan lulus lokal. Policy tidak menjalankan aksi, memilih slot, memverifikasi token undangan, atau memberi akses admin domain. CORE-04 tetap parsial: berikutnya action/transaksi membership, bootstrap histori, rollback dan konkurensi kedua DB; integrasi 03B menyusul. Tidak ada route baru atau migration DB aplikasi.

**Checkpoint action (LOG-021):** `RemoveHouseholdMember` menangani partner keluar atau owner mengeluarkan partner; `TransferHouseholdOwnership` menukar role tanpa mengubah slot, warna, atau joined_at. Kedua action mengunci household aktif, memanggil Gate dan membaca membership terkini di dalam transaksi. Audit Spatie ditulis sinkron dalam transaksi yang sama, memakai ULID untuk log dan morph reference; kegagalan audit membatalkan mutasi. Test baru 19 kasus/79 assertion; total 139 test PHP/1546 assertion, Pint/Larastan lulus. Migrate/rollback/re-migrate semua tabel lulus pada SQLite in-memory, bukan DB aplikasi. Ini fondasi backend membership CORE-04 untuk caller CORE-14 nanti, bukan penyelesaian UI/pengaturan CORE-14. Berikutnya admission/slot melalui undangan CORE-05, bootstrap household/history dan bukti konkurensi SQLite/MariaDB; CORE-04 tetap parsial dan 03B belum selesai. Tidak ada route/action publik untuk bergabung atau provisioning bebas.

**Checkpoint tenant dan resolver (LOG-024):** `HouseholdContext`, helper `currentHousehold()`, trait `BelongsToHousehold` + `HouseholdScope`, dan `PeriodResolver::for($household)` tersedia. Scope dan auto-fill/validasi `household_id` gagal tertutup bila konteks tidak ada; resolver membaca histori/timezone household tanpa bergantung konteks ambient. 5 test tenant baru/16 assertion; total 144 test PHP/1562 assertion, 4 test JS, Pint/Larastan/build lulus. Trait baru diterapkan pada `HouseholdFinancePeriodSetting`; `HouseholdMember` masih scoped eksplisit via policy/action dan `Activity` tidak memiliki `household_id`. Belum ada middleware/job context, bootstrap histori household, admission CORE-05, atau bukti konkurensi; CORE-03/04 tetap parsial.

**Checkpoint bootstrap dan konteks request (LOG-030):** `CreateHousehold` membuat household + membership owner slot 1 + periode keuangan awal dalam satu transaksi dengan validasi nama/start day/timezone dan penolakan user yang sudah punya household, lalu menulis audit `core.household.created`. Middleware `UseHouseholdContext` pada grup `web` memasang konteks dari membership user terautentikasi dan selalu melupakannya setelah respons; household terhapus lunak diabaikan. 12 test baru/43 assertion; total 156 test PHP/1605 assertion, 4 test JS, Pint/Larastan/build lulus. Belum ada konteks untuk job/command, backfill histori periode, admission CORE-05, atau bukti konkurensi; CORE-04 tetap parsial.

**Checkpoint undangan partner (LOG-032):** `household_invitations` menyimpan `token_hash` SHA-256 (token 64 karakter heksadesimal hanya keluar sekali), `expires_at` 7 hari, `accepted_at`/`accepted_by`, dan index unik `household_invitation_pending_idx`; `InviteHouseholdMember`, `AcceptInvitation`, dan `RevokeHouseholdInvitation` tersedia dengan otorisasi `invitePartner`/`cancelInvitation`, penolakan token kedaluwarsa/dipakai ulang/asing, batas dua member, dan audit. 15 test baru/54 assertion; total 171 test PHP/1659 assertion, Pint, Larastan, dan `migrate --pretend` SQLite lulus. Belum ada email, route registrasi, UI, atau invite household baru oleh admin; skema undangan menyimpang dari §5.1 masterplan (`email`, `type`, `revoked_at`, household_id nullable) dan menunggu keputusan reviewer sebelum slice berikutnya.

### Installed baseline

- PHP 8.5.12 with local development extensions enabled.
- Composer 2.10.3, installed for the current Windows user from the official installer after SHA-384 verification.
- Node.js 24.22.1 LTS and npm 11.19.1.
- Root [composer.json](../composer.json) and [composer.lock](../composer.lock): Laravel 13, Fortify, Livewire 4, approved domain packages, Heroicons, Pest 5 and Laravel plugin, Pint, Larastan, and development-only Boost/Mockery. Mockery was approved for Laravel's database-testing lifecycle in the ADR-011 addendum.
- Root [package.json](../package.json) and [package-lock.json](../package-lock.json): Vite 8, Laravel Vite plugin 3, Tailwind 4, and its Vite integration.
- The [mockup](../mockup/package.json) remains a separate npm project; its files and dependencies are unchanged.

The official Laravel 13.0.0 skeleton is integrated with Laravel Framework 13.38.0. Artisan, HTTP bootstrap, config, welcome, factory/seeder, PSR-4, tests, Vite, and Composer scripts are present. LOG-019 replaces the stock integer User/session identifiers with ULIDs and adds household/member/period-history schema and models. Account provisioning and authorized membership workflows are not implemented; the stock factory/seeder is not an approved admin bootstrap.

A local ignored `.env` and app key exist. Session/cache use files so the welcome/login pages render without a database; SQLite remains configured, but no local application database has been created, migrated, or seeded. Tests migrate only isolated in-memory databases. Queue stays configured for the database driver but no worker is running. Fortify is enabled with an empty feature list: login/logout and password confirmation work, while registration/reset/TOTP/passkeys remain disabled. Passkeys auto-discovery stays excluded. Boost MCP configuration for Copilot is installed in `.vscode/mcp.json`; stdio initialization and its documentation tool were tested, but the actual VS Code Copilot connection is not yet confirmed.

Open `/login` for the Indonesian Livewire/Blade form, styled with Tailwind and bundled Alpine. `.env.example` and the existing local `.env` use `APP_LOCALE=id`; English auth labels are also available. Native POST forms delegate authentication to Fortify, including lowercase email, session rotation, and a five-per-minute limiter keyed by normalized email and IP. Success returns to the default `/` welcome page, not a household dashboard. There is no persistent account or authorized account-creation workflow yet; do not invent demo credentials or migrate/seed the placeholder schema just to try login manually. Invitation/account provisioning belongs to later Core tasks.

Core/Modules placeholder directories and `config/modules.php` exist, with all unreleased modules disabled. Non-production Eloquent lazy-loading prevention is enabled. Permanent Pint PSR-12 and Larastan level 6 configurations and Composer `lint`/`analyse` scripts are available. CI is defined in `.github/workflows/ci.yml` for PHP 8.5 with SQLite and MariaDB 10.11.

**Bukti CI terbaru (LOG-029):** run fondasi [40268711517](https://github.com/shadri-zulkurniadi/Net/actions/runs/40268711517) pada `ffe4afe` lulus kedua job (LOG-014). Untuk tip `6d6f7bc`, reviewer melaporkan job SQLite dan MariaDB lulus setelah perbaikan collation/index; tautan run tidak dicatat. Tidak ada server MariaDB lokal yang dipasang.

ApexCharts is deferred until the reporting module needs it and its license is reviewed. Alpine comes from Livewire; no separate Alpine npm dependency is installed. No Flux, Volt, Sail, Docker, or global Laravel installer is needed.

### Restore dependencies

Open a new terminal after installation so the updated user PATH is loaded. From the repository root:

```powershell
php --version
composer --version
node --version
npm --version
composer install --no-scripts --no-interaction --prefer-dist
composer dump-autoload
npm ci --ignore-scripts
npm run build
npm test
```

For a fresh clone only, copy `.env.example` to `.env` if absent, then run `php artisan key:generate`. Never overwrite an existing `.env` or regenerate a key for data that depends on it.

### Run the local scaffold

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --tries=1
```

Alternatively run `composer serve`. Laravel uses `artisan serve`, not CodeIgniter's `spark serve`. Open `http://127.0.0.1:8000`; health check is `/up`. The server was left running on loopback for reviewer use, not exposed to the LAN or internet. It is a development server, not a production deployment. If the port is already occupied, reuse the existing instance or select another port; do not terminate unrelated processes.

Use the repository lockfiles. Do not run `composer update` or `npm update` just to restore an environment. Composer permits only the Pest plugin; installation skips application scripts. npm lifecycle scripts are disabled; Vite and its plugins were verified to load on this Windows x64 environment.

The local PHP installation is managed by WinGet. Its `php.ini` is in the same directory as `php.exe`; find it with `php --ini`. Enabled DLL extensions: curl, openssl, mbstring, intl, fileinfo, exif, gd, zip, pdo_sqlite, sqlite3, pdo_mysql, and sodium. PHP also supplies built-in XML, DOM, bcmath, and other required modules. This is a development configuration with error display enabled, not a production PHP configuration. Recheck the INI after a WinGet upgrade.

Composer is installed in `%LOCALAPPDATA%\Programs\Composer` with a `composer.cmd` launcher on the user PATH. `vendor` and `node_modules` are ignored by Git; lockfiles and the reviewed foundation were committed/pushed on the approved `core-01-foundation` branch at `ffe4afe` (LOG-014), and the foundation plus the CORE-03/04 checkpoint are now on `main` at `bcbbe86` (LOG-023/LOG-026). No credentials belong in the manifests or source control.

### Checks completed

- `composer validate`: manifest and lockfile valid; warning that the project license has not been declared. No license was assumed on the owner's behalf.
- `composer check-platform-reqs`: all installed dependency requirements pass on PHP 8.5.12.
- `composer audit --locked`: no vulnerability advisories reported at setup time.
- Pint and PHPStan version commands execute.
- `npm ls --depth=0`: direct dependencies installed without tree errors.
- `npm exec -- vite --version`: Vite 8.3.5 executes.
- Node imports of `@tailwindcss/vite` and `laravel-vite-plugin` succeed.
- `npm audit`: no vulnerabilities reported, including optional dependencies.
- PHP dependency autoload and an in-memory SQLite query succeed without generating an application database.
- Scaffold package discovery and `php artisan --version` succeed (Laravel Framework 13.38.0).
- `composer test`: current CORE-03 local regression passes 144 tests/1562 assertions; foundation/auth, Money, PeriodResolver, tenant isolation, static components, and money-input/range validation. Auth tests use synthetic users and SQLite in-memory. Published CORE-01 CI evidence remains at `ffe4afe`; newer local tests are not claimed as remote CI coverage.
- `npm test`: 4 Node tests pass for safe integer parsing, invalid/range input, stale-value clearing, cursor placement and incremental typing/backspace. No additional test dependency.
- PSR-12 Pint check passes on scaffold PHP source.
- `npm run build` succeeds; live welcome, `/up`, and referenced local built assets return HTTP 200 on the loopback server.
- `composer lint` and `composer analyse` pass using permanent project configurations. Composer manifest/lock validation passes after metadata refresh without package upgrades; the license warning remains.
- Boost MCP initialization/tool listing and `search-docs` succeed through a temporary stdio client, without running generated guidelines/skills installers or altering existing agent instructions.
- LOG-013: reviewer memilih terminal perantara sebagai transport Boost. Uji ulang initialize, daftar 10 tool, dan `search-docs` lulus; child dihentikan setelah selesai. Gunakan client stdio terbatas saat membutuhkan tool, bukan server background permanen. Gate koneksi Boost terpenuhi melalui jalur ini; koneksi native UI tidak diklaim.
- Browser `/login` loads with Indonesian labels and bundled Alpine/Livewire; at 360px light/dark and 1280px desktop there is no horizontal overflow. Password show/hide and native required-input validity were checked; hiding used a programmatic browser click after the interaction tool timed out. This is not a full accessibility audit, Lighthouse measurement, or physical-device UAT.

### Deliberately pending

- Basic auth/frontend integration is implemented; invitation, reset, household schema, admin, and domain features/tests do not exist. Placeholder module directories do not implement modules.
- Boost stdio smoke test is not evidence of a native Copilot UI connection. Per LOG-013, the terminal path is approved instead; UI activation is optional, not a blocker. Guidelines/skills generation was deliberately skipped to preserve repository guidance.
- No local MariaDB server, SMTP, queue worker, scheduler, production host, or backup service has been provisioned. MariaDB tests now pass in GitHub CI, not on a local server. Browser smoke checks were run through the integrated browser; no persistent browser-test suite was added.
- CORE-01 technical gates pass, including remote CI on both databases (LOG-014); final reviewer acceptance remains pending. The approved terminal path satisfies the Boost gate. Do not start CORE-03 until acceptance; no delegated agents are needed for this checkpoint.

## 1. Cara menggunakan rencana

- [Masterplan](00-MASTERPLAN.md) dan dokumen modul tetap sumber acceptance criteria/dependensi. Rencana ini mengurutkan pelaksanaan, bukan mengganti spesifikasi.
- [Logbook](logbook.md) adalah bukti pekerjaan aktual. Setiap perubahan status rencana harus menunjuk entri logbook yang menjelaskan verifikasi dan batasnya.
- Status: **Selesai** = cakupan tahap terbukti; **Parsial** = baru sebagian; **Belum dimulai** = belum ada implementasi; **Terblokir** = prasyarat eksplisit belum terpenuhi.
- Tiap rentang task di bawah dikerjakan sebagai task terpisah, satu task ID per branch/PR; bukan satu perubahan besar untuk seluruh tahap.
- Arahan reviewer terbaru: lanjutkan sesuai plan dengan hemat credit; tuntaskan CORE-01 dan verifikasi sebelum mengambil task berikutnya. Saldo credit tidak dapat dilihat agent.
- Setiap batch perubahan wajib mengikuti [skill sinkronisasi dokumentasi](../.github/skills/development-docs-sync/SKILL.md), memperbarui logbook dan dokumen ini; perubahan struktural memerlukan review seluruh dokumentasi proyek dan pembaruan semua yang terdampak.

## 2. Posisi saat ini

| Tahap | Status | Bukti / batas |
|---|---|---|
| A. Repository, discovery, keputusan stack | Selesai untuk persiapan | [LOG-001 sampai LOG-003](logbook.md#log-001---repository-dan-tooling-dasar); keputusan open questions berikutnya tetap diperlukan |
| B. Dependency dan dokumentasi | Selesai untuk persiapan | [LOG-004](logbook.md#log-004---manifest-dan-instalasi-dependency-tanpa-coding-aplikasi), [LOG-005](logbook.md#log-005---konsolidasi-dokumentasi), [LOG-006](logbook.md#log-006---logbook-dan-rencana-development), [LOG-007](logbook.md#log-007---penggabungan-setup-dan-rencana), [LOG-008](logbook.md#log-008---skill-sinkronisasi-dokumentasi) |
| C. CORE-01 | Diterima untuk kelanjutan development | [LOG-011](logbook.md#log-011---checkpoint-auth-core-01), [LOG-013](logbook.md#log-013---boost-melalui-terminal-perantara), [LOG-014](logbook.md#log-014---publikasi-fondasi-untuk-ci-core-01); arahan lanjut reviewer LOG-015; bukan acceptance produksi |
| D. Core lainnya | CORE-03/04 parsial; CORE-05 backend undangan | LOG-019: urutan 03A → 04 → 03B disetujui; LOG-024: tenant scope/konteks dan `PeriodResolver::for` tersedia, trait terbatas pada period settings; LOG-030: bootstrap household dan middleware konteks request; LOG-032: undangan partner dengan token hash/kedaluwarsa/revoke; konteks job, backfill histori, konkurensi, email/route/UI undangan, dan invite admin belum |
| E. Finance | Belum dimulai | FIN-01 sampai FIN-16 |
| F. Organizer | Belum dimulai | ORG-01 sampai ORG-13 |
| G. Asset | Belum dimulai | AST-01 sampai AST-12 |
| H. Hardening dan go-live | Belum dimulai | UAT, Lighthouse, deploy, dan restore belum dibuktikan |

## 3. Langkah berikutnya: selesaikan CORE-01

Scaffold standar/server selesai pada LOG-009; tambahan tooling dan fondasi pada LOG-010; auth/frontend dasar pada LOG-011. Langkah 2-6 telah diimplementasikan dan diverifikasi lokal. Gate Boost langkah 7 dipenuhi lewat terminal per LOG-013; langkah 8 lulus di GitHub pada LOG-014. Branch baseline telah dipublikasikan dengan izin reviewer. Langkah berikutnya adalah acceptance reviewer CORE-01, bukan mengulang scaffold atau langsung memulai CORE-03:

1. Tinjau working tree persiapan bersama reviewer; pertahankan manifest, lockfile, mockup, dan dokumentasi yang ada. Tentukan baseline branch/commit tanpa menimpa pekerjaan yang belum di-commit.
2. Tambahkan scaffold Laravel 13 secara terkontrol ke root repository; jangan menjalankan generator yang menimpa repository atau mengganti lockfile tanpa peninjauan.
3. Siapkan bootstrap, entrypoint Artisan/HTTP, provider, route dasar, PSR-4, struktur Core/Modules, feature flags nonaktif untuk modul yang belum dirilis, dan `.env.example` tanpa secret.
4. Hubungkan Fortify dengan registrasi publik nonaktif. Implementasi invitation/household tetap task CORE-04/05, bukan diperluas ke CORE-01.
5. Hubungkan Livewire 4, Blade, Tailwind 4, dan Vite 8 dengan entrypoint minimal. Jangan membuat fitur domain atau mengimpor seluruh mockup sebagai aplikasi jadi.
6. Konfigurasikan Pest, Pint, Larastan level 6, script Composer/npm, serta smoke test boot aplikasi. Jangan mengklaim coverage fitur dari smoke test.
7. Jalankan installer Boost setelah Laravel boot; tinjau diff agar instruksi proyek tetap utuh, lalu uji initialize/daftar tool/tool dokumentasi melalui terminal stdio yang disetujui (atau koneksi native Copilot jika tersedia).
8. Buat CI GitHub Actions PHP 8.5 dengan SQLite dan MariaDB; jalankan platform check, build frontend, test, Pint, dan Larastan.
9. Catat hasil aktual pada entri logbook baru, perbarui setup dan status CORE-01, lalu minta verifikasi reviewer.

**Gate keluar:** seluruh AC CORE-01 pada Masterplan lulus, termasuk CI kedua DB dan koneksi Boost. Instalasi dependency yang sudah ada tidak menggantikan langkah 2-9.

## 4. Phase 0: Core

Urutan berikut memenuhi dependensi utama; pekerjaan yang independen tetap dapat diprioritaskan ulang dengan persetujuan reviewer.

| Urutan | Task | Implementasi / bukti yang diharapkan |
|---|---|---|
| 1 | CORE-03 | Money, PeriodResolver, tenant support, komponen uang/tanggal; uji parser, perubahan periode, tahun kabisat, dan histori. Status LOG-024: Money/komponen/tenant+resolver lokal selesai; LOG-030 menambahkan middleware konteks request, konteks job belum |
| 2 | CORE-04 | Household/member, role/policy, slot atomik maksimal dua anggota; uji isolasi dan konkurensi SQLite/MariaDB |
| 3 | CORE-05 | Invite-only, hash token, expiry/reuse/revoke, alur login/reset; uji registrasi tanpa undangan ditolak. Status LOG-032: backend undangan partner (hash, 7 hari, sekali pakai, revoke) selesai; email, route registrasi, dan invite admin belum |
| 4 | CORE-06 | Onboarding household, timezone, periode, undang partner opsional |
| 5 | CORE-07 | Layout/navigasi/FAB, komponen bersama, light/dark; uji mobile 360px dan keyboard |
| 6 | CORE-08 | Recurrence wrapper/preset/humanize; uji akhir bulan dan batas recurrence |
| 7 | CORE-11 | Calendar/Alert contracts dan aggregator; uji sumber dummy, filter, urutan, timezone, batas rentang |
| 8 | CORE-09 | Reminder/delivery per penerima, quiet hours, cancel/idempotensi/retry; **putuskan Q-M9 sebelum mulai** |
| 9 | CORE-10 | Notification center, preferensi channel, web push/email, notifikasi uji |
| 10 | CORE-12 | Dashboard kerangka dari layout dan aggregator; widget modul nonaktif tidak ditampilkan |
| 11 | CORE-13 | PWA install/offline read-only, update, clear cache pada perubahan auth; uji tidak ada kebocoran cache |
| 12 | CORE-14 | Profil, household/member, activity log, keamanan; **putuskan Q-M12 sebelum implementasi OTP** |
| 13 | CORE-15 | Admin household/invite tanpa akses data domain household lain; uji batas akses |
| 14 | CORE-02 | Siapkan dan uji host Linux bare-metal, Nginx/PHP-FPM, worker/scheduler, zrok, restart service, dan prosedur deploy |
| 15 | CORE-16 | Backup terenkripsi, ekspor household, alert kegagalan, restore terisolasi; finalisasi bukti operasional |

CORE-02 dapat dipersiapkan setelah CORE-01 sesuai Masterplan; ditempatkan akhir urutan implementasi ini agar alur aplikasi dapat diuji end-to-end. AC CORE-02 meminta backup aktif, sedangkan implementasi backup ada di CORE-16 yang bergantung pada CORE-02. Perlakukan sebagai dua gate: infrastruktur CORE-02 tersedia untuk mengerjakan CORE-16, tetapi **jangan tandai CORE-02 sepenuhnya selesai** sebelum backup dan restart end-to-end terbukti. Jika reviewer menghendaki perubahan dependensi formal, perbarui Masterplan terlebih dahulu.

**Gate Phase 0:** seluruh task dan AC selesai; login/undangan berfungsi di HP melalui HTTPS, PWA dapat dipasang, notifikasi uji diterima, dan batas household dibuktikan. Gunakan data sintetis sampai syarat operasional/privasi terpenuhi.

## 5. Phase 1: Finance

Rujukan: [spesifikasi Finance](01-MODULE-FINANCE.md). Mulai setelah gate Core lulus.

1. **FIN-01, FIN-02:** skema/model/enum/factory/provider, kategori dan demo Indonesia.
2. **FIN-03, FIN-04:** akun/saldo/arsip dan kategori dua level, mapping alokasi, gabung/arsip.
3. **FIN-05, FIN-06:** transaksi/transfer/quick add/undo/lampiran, daftar/filter/detail.
4. **FIN-07:** anggaran dan lima alokasi; uji pembulatan, basis nol, transfer tabungan neto, dan anti-dobel hitung.
5. **FIN-08, FIN-13:** PlannedItem, bayar/partial/skip/recompute, lalu FinanceGateway dan null implementation untuk integrasi modul berikutnya.
6. **FIN-09, FIN-10:** tagihan/pemasukan rutin dan pinjaman; uji generator idempoten, tanggal 31, cicilan terakhir, paid-before-tracking, dan pelunasan.
7. **FIN-11, FIN-12:** Rencana Bulanan, kalender/alert/reminder/widget/notifikasi Core.
8. **FIN-14:** overview/laporan/ekspor dan rekonsiliasi. Tinjau lisensi ApexCharts sebelum memasang paket chart yang telah disetujui.
9. **FIN-15:** putuskan ADR engine OCR lokal (Q-F8), lalu implementasikan draft merchant/tanggal/total dengan review wajib; tidak ada unggah ke layanan OCR eksternal.
10. **FIN-16:** polish dan UAT, mobile/light/dark/offline; cocokkan saldo dengan hitungan manual.

**Gate Phase 1:** AC dan test modul lulus; reviewer menggunakan transaksi selama dua minggu, jadwal/rencana benar, saldo cocok dengan rekening nyata pada lingkungan yang sudah memenuhi syarat data nyata. Jangan memaksa data nyata masuk lingkungan uji yang belum aman.

## 6. Phase 2: Organizer

Rujukan: [spesifikasi Organizer](02-MODULE-ORGANIZER.md).

1. **ORG-01:** skema/model/provider/feature flag.
2. **ORG-02, ORG-03, ORG-04:** tugas, recurrence/rotasi/undo, dan penyelesaian dengan pengeluaran melalui FinanceGateway.
3. **ORG-05:** catatan/checklist, privat, konflik edit, arsip/tempat sampah; uji route, pencarian, ekspor, dan notifikasi.
4. **ORG-06, ORG-07:** belanja kolaboratif/parser/poll dan transaksi paling banyak sekali; null gateway tidak menghalangi selesai.
5. **ORG-08, ORG-09, ORG-10:** acara/exception, sources, lalu kalender Bulan/Agenda.
6. **ORG-11, ORG-12:** ekspor ICS, pengingat/alert; uji UID stabil, all-day lokal/end eksklusif, batas rentang, nominal disembunyikan default.
7. **ORG-13:** widget/exporter/polish dan UAT dua kali belanja.

**Gate Phase 2:** kalender menggabungkan sumber aktif; impor ICS diuji; alur tugas/catatan/belanja dan integrasi Finance nonaktif lulus.

## 7. Phase 3: Asset

Rujukan: [spesifikasi Asset](03-MODULE-ASSET.md).

1. **AST-01, AST-02, AST-03:** skema/seed, inventaris/foto/detail, lokasi/vendor/WhatsApp.
2. **AST-04, AST-05:** dokumen/perpanjang serta meter/offset reset kumulatif.
3. **AST-06, AST-07, AST-08:** rencana perawatan/template/snooze, catatan servis, dan kerusakan.
4. **AST-09, AST-10:** transaksi dan rencana Finance; kaitkan pembayaran tanpa transaksi ganda, batalkan rencana lama dengan benar.
5. **AST-11:** kalender, alert, reminder, widget, dan job.
6. **AST-12:** pelepasan aset, exporter, polish, UAT.

**Gate Phase 3:** minimal sepuluh aset dan satu siklus servis diuji; biaya/saldo dan jadwal berikutnya benar; meter reset/historis dan integrasi null terlindungi test.

## 8. Phase 4: Hardening, UAT, dan rilis

1. Jalankan regression test lintas modul pada SQLite/MariaDB, Pint, Larastan, build, dan pemeriksaan dependency.
2. Uji browser pada mobile 360px dan desktop, light/dark, keyboard, reduced motion, input cepat, offline, serta transisi login/logout.
3. Ukur Lighthouse mobile: Performance >=85, Accessibility >=90, Best Practices >=90 dan installability sesuai Masterplan.
4. Tutup Q-M11 (retensi/purge backup) sebelum data nyata; verifikasi keputusan Q-M9/Q-M12, SMTP, target backup, serta host Linux.
5. Ikuti [runbook deploy](runbook/deploy.md) dan [backup/restore](runbook/backup-restore.md); buktikan HTTPS zrok, trusted proxy, worker/scheduler, backup terenkripsi/off-site, rollback yang kompatibel, dan pemulihan setelah reboot.
6. Luluskan [checklist UAT/go-live](uat-go-live.md) bersama owner/partner; jangan mengisi checklist dari asumsi.
7. Catat commit/tag rilis, hasil penerimaan, known issues dan prosedur operasional di logbook; buka akses nyata hanya setelah semua gate rilis terpenuhi.

Tidak perlu menunggu Phase 4 untuk menguji keamanan, aksesibilitas, backup, atau performa: setiap task harus menjaga standar tersebut sejak implementasi.

## 9. Keputusan dan penghambat yang harus dilacak

| Hal | Batas waktu | Status awal |
|---|---|---|
| Kelanjutan CORE-01 | Sebelum menutup task | Teknis/CI kedua DB lulus; reviewer mengizinkan lanjut CORE-03 pada LOG-015 |
| Lisensi proyek | Sebelum deklarasi metadata/distribusi | Belum diputuskan; warning Composer diketahui |
| Host Linux, akses admin, SMTP (Q-M2/Q-M3) | Sebelum uji deploy/email terkait | Detail aktual belum diverifikasi |
| Retry reminder (Q-M9) | Sebelum CORE-09 | Open |
| Email OTP/recovery (Q-M12) | Sebelum CORE-14 | Open |
| Retensi/purge backup (Q-M11) | Sebelum data nyata/go-live | Open |
| Engine OCR (Q-F8) | Sebelum FIN-15 | ADR dan persetujuan diperlukan |
| Lisensi ApexCharts | Sebelum pemasangan/pemakaian chart | Ditunda |
| Ambang migrasi tunnel (Q-M1) | Sebelum migrasi Cloudflare | Bukan blocker rilis awal dengan zrok |

## 10. Sinkronisasi setiap batch perubahan

**Workflow UI wajib (LOG-012):** khusus repository ini, batch yang memengaruhi tampilan atau perilaku browser menggunakan `premium-frontend-ui` untuk implementasi, `web-design-reviewer` untuk review visual, lalu `webapp-testing` untuk perilaku/regresi; perbaikan diikuti pengecekan ulang yang terdampak. Ketiganya berbagi sesi browser/bukti, bukan mengedit paralel. Baseline 360px/1280px light/dark, viewport tambahan sesuai dampak. Aturan desain/stack Family Hub mengalahkan efek generik skill premium; tidak ada instalasi dependency otomatis. Perubahan dokumentasi/backend tanpa dampak browser tidak memicu workflow. Aturan lengkap dan fallback discovery ada di [instruksi repository](../.github/copilot-instructions.md#mandatory-coordinated-ui-workflow). Ini instruksi agent, bukan watcher setiap save; discovery host berikutnya belum diverifikasi. CORE-01 tetap parsial.

1. Cocokkan hasil dengan task/AC dokumen sumber; tulis apa yang belum terbukti.
2. Tambahkan entri logbook dengan tanggal, task, perubahan, command/hasil, blocker, dan referensi commit/PR jika benar-benar tersedia.
3. Perbarui tabel posisi saat ini hanya berdasarkan bukti tersebut; tautkan entri logbook terbaru. Jika hanya sebagian, gunakan Parsial.
4. Perbarui setup/runbook/ADR jika perilaku, tooling, atau keputusan berubah.
5. Minta reviewer memverifikasi sebelum menutup task dan mengambil task berikutnya.
6. Untuk perubahan struktural, tinjau seluruh dokumen proyek, perbarui semua yang terdampak, dan catat hasil review termasuk dokumen yang tetap sesuai. Ikuti [development-docs-sync](../.github/skills/development-docs-sync/SKILL.md); satu batch cukup satu entri log tanpa rekursi pencatatan.
