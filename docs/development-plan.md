# Development Setup dan Rencana - Family Hub

**Baseline:** 2026-10-26
**Status:** CORE-01 diterima untuk kelanjutan development (LOG-015). CORE-03 dinyatakan selesai reviewer (LOG-025). Kode CORE-04 (`731fb45`) lulus kedua job CI SQLite/MariaDB (LOG-031); commit setelah itu belum punya bukti remote. LOG-030 menambahkan bootstrap household dan konteks tenant per request, LOG-032/LOG-034 menambahkan backend undangan partner sesuai §5.1, dan LOG-036 menambahkan email undangan, halaman penerimaan partner, serta alur reset kata sandi. LOG-040 menambahkan limiter 10 undangan/jam, LOG-041 menetapkan 1 undangan aktif dan admin bukan member, serta LOG-042 menetapkan profil optimasi host Odroid-C2 (2GB RAM, zero-daemon Redis, SQLite WAL + memory pragmas). Semua konflik dan keputusan terbuka dilacak di [daftar tertunda](pending.md) (LOG-033); CORE-04 tetap parsial di sisi job context dan backfill histori periode, sedangkan CORE-05 belum punya Alur A admin.

**CI kedua DB (LOG-029, LOG-031):** job SQLite dan MariaDB lulus untuk `6d6f7bc` (144 test PHP/1562 assertions) dan untuk `731fb45` (156 test PHP/1605 assertions), termasuk migrasi, test JS, Pint, Larastan, dan build. Perbaikan collation LOG-028 dan nama index LOG-027 terbukti pada run nyata. Tidak ada deployment atau UAT.

**Alur kerja (LOG-035):** pengembangan solo berjalan langsung di `main` tanpa branch task; satu task ID per commit. Verifikasi lokal wajib sebelum push karena tidak ada review gate, dan kegagalan CI diperbaiki maju di `main`.

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

**Checkpoint undangan partner (LOG-032, diselaraskan LOG-034 dan LOG-036):** `household_invitations` mengikuti §5.1 masterplan: `household_id` nullable, `email`, `type` (`new_household`/`partner`), `token_hash` SHA-256 unik, `expires_at` 7 hari, `accepted_at`, dan `revoked_at`; token 64 karakter hanya keluar sekali. `InviteHouseholdMember` (email partner wajib valid, otorisasi `invitePartner`, menolak household penuh, mengirim email undangan), `AcceptInvitation` (menolak token tidak dikenal/kedaluwarsa/dipakai ulang/dicabut/asing/`new_household`/email akun tidak cocok/household terhapus/user sudah punya household/household penuh, mengisi slot bebas secara atomik, audit `core.household.partner_joined`), dan `RevokeHouseholdInvitation` (otorisasi `cancelInvitation`, hanya undangan pending, menandai `revoked_at` tanpa menghapus baris) tersedia. LOG-036 menambahkan `HouseholdInvitationMail`, route `invitation.show`, form Livewire penerimaan yang membuat akun lewat `RegisterInvitedPartner`, dan alur reset kata sandi Fortify dengan jawaban seragam untuk email tak dikenal. LOG-040 menambahkan batas 10 undangan/jam per aktor sesuai SEC-3; LOG-041 menetapkan hanya satu undangan aktif per household dan melarang akun system admin menjadi anggota household. LOG-043 menuntaskan Alur A undangan household baru oleh admin (`InviteNewHousehold`, `AcceptNewHouseholdInvitation`, `RegisterInvitedOwner`, command `core:create-admin` yang menutup P-17, serta form penerimaan Livewire untuk nama keluarga). Total **214 test PHP/1870 assertion**, 4 test JS, Pint, Larastan, build, dan browser verification lulus. Sisa antrean mail dilacak di CORE-10/P-14.

### Installed baseline

- PHP 8.5.12 with local development extensions enabled.
- Composer 2.10.3, installed for the current Windows user from the official installer after SHA-384 verification.
- Node.js 24.22.1 LTS and npm 11.19.1.
- Root [composer.json](../composer.json) and [composer.lock](../composer.lock): Laravel 13, Fortify, Livewire 4, approved domain packages, Heroicons, Pest 5 and Laravel plugin, Pint, Larastan, and development-only Boost/Mockery. Mockery was approved for Laravel's database-testing lifecycle in the ADR-011 addendum.
- Root [package.json](../package.json) and [package-lock.json](../package-lock.json): Vite 8, Laravel Vite plugin 3, Tailwind 4, and its Vite integration.
- The [mockup](../mockup/package.json) remains a separate npm project; its files and dependencies are unchanged.

The official Laravel 13.0.0 skeleton is integrated with Laravel Framework 13.38.0. Artisan, HTTP bootstrap, config, welcome, factory/seeder, PSR-4, tests, Vite, and Composer scripts are present. LOG-019 replaces the stock integer User/session identifiers with ULIDs and adds household/member/period-history schema and models. Partner admission works only through the invitation flow; admin provisioning and membership-management UI are not implemented, and the stock factory/seeder is not an approved admin bootstrap.

A local ignored `.env` and app key exist. Session/cache use files so the welcome/login pages render without a database; SQLite remains configured, but no local application database has been created, migrated, or seeded. Tests migrate only isolated in-memory databases. Queue stays configured for the database driver but no worker is running; invitation mail is sent synchronously until CORE-10. Fortify enables only password reset: login/logout and password confirmation work, reset is available from `/forgot-password`, while registration/TOTP/passkeys remain disabled. Passkeys auto-discovery stays excluded. Boost MCP configuration for Copilot is installed in `.vscode/mcp.json`; stdio initialization and its documentation tool were tested, but the actual VS Code Copilot connection is not yet confirmed.

Open `/login` for the Indonesian Livewire/Blade form, styled with Tailwind and bundled Alpine. `.env.example` and the existing local `.env` use `APP_LOCALE=id`; English auth labels are also available. Native POST forms delegate authentication to Fortify, including lowercase email, session rotation, and a five-per-minute limiter keyed by normalized email and IP. Success returns to the default `/` welcome page, not a household dashboard. Accounts can only be created through an invitation link (`/invitation/{token}`); there is no demo account, so do not invent credentials or migrate/seed the placeholder schema just to try login manually. `/forgot-password` sends a reset link for existing accounts.

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
- `composer test`: current local regression passes 202 tests/1825 assertions; foundation/auth, password reset, invitation acceptance, invitation throttle, single-active-invitation and admin-exclusion guards, slot-race backstop, Money, PeriodResolver, tenant isolation, static components, and money-input/range validation. Auth tests use synthetic users and SQLite in-memory. Published CI evidence remains at `731fb45`; newer local tests are not claimed as remote CI coverage.
- `npm test`: 4 Node tests pass for safe integer parsing, invalid/range input, stale-value clearing, cursor placement and incremental typing/backspace. No additional test dependency.
- PSR-12 Pint check passes on scaffold PHP source.
- `npm run build` succeeds; live welcome, `/up`, and referenced local built assets return HTTP 200 on the loopback server.
- `composer lint` and `composer analyse` pass using permanent project configurations. Composer manifest/lock validation passes after metadata refresh without package upgrades; the license warning remains.
- Boost MCP initialization/tool listing and `search-docs` succeed through a temporary stdio client, without running generated guidelines/skills installers or altering existing agent instructions.
- LOG-013: reviewer memilih terminal perantara sebagai transport Boost. Uji ulang initialize, daftar 10 tool, dan `search-docs` lulus; child dihentikan setelah selesai. Gunakan client stdio terbatas saat membutuhkan tool, bukan server background permanen. Gate koneksi Boost terpenuhi melalui jalur ini; koneksi native UI tidak diklaim.
- Browser `/login` loads with Indonesian labels and bundled Alpine/Livewire; at 360px light/dark and 1280px desktop there is no horizontal overflow. Password show/hide and native required-input validity were checked; hiding used a programmatic browser click after the interaction tool timed out. This is not a full accessibility audit, Lighthouse measurement, or physical-device UAT.

### Deliberately pending

- Auth/frontend integration is implemented for login, password confirmation/reset, and partner invitation acceptance; household schema exists, while the admin flow and domain features/tests do not. Placeholder module directories do not implement modules.
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
| B. Dependency dan dokumentasi | Selesai untuk persiapan | [LOG-004](logbook.md#log-004---manifest-dan-instalasi-dependency-tanpa-coding-aplikasi), [LOG-005](logbook.md#log-005---konsolidasi-dokumentasi), [LOG-006](logbook.md#log-006---logbook-dan-rencana-development), [LOG-007](logbook.md#log-007---penggabungan-setup-dan-rencana), [LOG-008](logbook.md#log-008---skill-sinkronisasi-dokumentasi), [LOG-071](logbook.md#log-071---instalasi-skill-frontend-design--improve-ui-serta-audit-ui-menyeluruh-82-blade-views), [LOG-072](logbook.md#log-072---pemasangan-skill-caveman-juliusbrusseecaveman), [LOG-073](logbook.md#log-073---remediasi-hasil-audit-ui-batch-1-sd-3-resolusi-rekursi-blade-dan-verifikasi-test-terisolasi), [LOG-075](logbook.md#log-075---pemanfaatan-skill-ui-ux-pro-max--persistensi-design-system-master-file), [LOG-076](logbook.md#log-076---sesi-grilling-round-1-penutupan-p-22-adr-012-2fa-otp-adr-013-telegram-bot--inisialisasi-glossarymd), [LOG-077](logbook.md#log-077---sesi-grilling-round-2-penajaman-spesifikasi-teknis-gelombang-1--penutupan-frontier) |
| C. CORE-01 | Diterima untuk kelanjutan development | [LOG-011](logbook.md#log-011---checkpoint-auth-core-01), [LOG-013](logbook.md#log-013---boost-melalui-terminal-perantara), [LOG-014](logbook.md#log-014---publikasi-fondasi-untuk-ci-core-01); arahan lanjut reviewer LOG-015; bukan acceptance produksi |
| D. Core lainnya | CORE-03 selesai; CORE-04 parsial; CORE-05 selesai Alur A & B; CORE-06 selesai onboarding; CORE-07 selesai layout & navigasi; CORE-08 selesai recurrence wrapper; CORE-11 selesai calendar & alert aggregator | LOG-019: urutan 03A → 04 → 03B disetujui; LOG-024: tenant scope/konteks dan `PeriodResolver::for` tersedia; LOG-030: bootstrap household dan middleware konteks request; LOG-032/LOG-034: undangan partner §5.1; LOG-036: email undangan partner, penerimaan, reset kata sandi; LOG-039: konkurensi slot partner terbukti; LOG-040: limiter 10/jam; LOG-041: 1 undangan aktif, admin bukan member; LOG-042: optimasi host Odroid-C2, SQLite WAL; LOG-043: alur A undangan household baru admin dan command `core:create-admin` (menutup P-17); LOG-044: onboarding wizard 3 langkah CORE-06; LOG-045: layout shell mobile/desktop, bottom nav 5 item, FAB, dark mode, 12 komponen §9.5 CORE-07; LOG-046: recurrence wrapper `Recurrence` di sekitar php-rrule CORE-08; LOG-047: kontrak dan aggregator `CalendarAggregator` (kuota 400 hari, tag `calendar.sources`) dan `AlertAggregator` (prioritas level, default 5 item, tag `alert.sources`) CORE-11. Konteks job, backfill histori, dan antrean mail belum |
| E. Finance | **Selesai** (FIN-01 sampai FIN-16) | [LOG-055](logbook.md#log-055---phase-1-finance-skema--model-fin-01-dan-seeder-kategori-indonesia--demo-fin-02) (skema, model, enum, seeder kategori & demo); [LOG-057](logbook.md#log-057---phase-1-finance-akun-transaksi-anggaran-rencana-alokasi-recurring-pinjaman-laporan--integrasi-fin-03-sampai-fin-16) (seluruh fitur domain, UI Livewire, integrasi kalender/alert/dashboard/ekspor, dan 9 test file feature lulus); [LOG-058](logbook.md#log-058---penataan-5-kategori-utama-pengeluaran-anggaran-berbasis-persentase-100-dan-input-sub-kategori-manual-dinamis) (5 kategori utama pengeluaran, anggaran berbasis persentase total 100%, dan subkategori manual dinamis); [LOG-069](logbook.md#log-069---penyempurnaan-ocr-struk-ekstraksi-total-nominal-tangguh-multi-format-angka--segmentasi-psm) (penyempurnaan OCR struk: segmentasi PSM 6, parsing multi-format nominal, pemisahan subtotal/diskon/tunai/kembali); [LOG-074](logbook.md#log-074---implementasi-chatbot-telegram-pengeluaran--ocr-struk-deterministik-ringan-odroid-option-a) (Chatbot Telegram pengeluaran deterministik ringan ODROID, parser pola bahasa alami, integrasi foto OCR struk, inline buttons, poller CLI & webhook) |
| F. Organizer | **Selesai** (Phase 2) | [LOG-059](logbook.md#log-059---phase-2-organizer--fondasi-skema-model-enum-provider--feature-flag-org-01), [LOG-060](logbook.md#log-060---phase-2-organizer-selesai-org-02-sampai-org-13) (ORG-01 sampai ORG-13 selesai: tasks & recurrence & undo & rotasi & expense, notes & markdown & multi-color & conflict, collaborative shopping & parser & real-time & FinanceGateway expense, calendar month & agenda & unified sources, ICS export RFC 5545, reminders page, dashboard widget, household backup exporter, 19 feature test lulus) |
| G. Asset | **Selesai** (Phase 3) | [LOG-061](logbook.md#log-061---phase-3-asset-selesai-ast-01-sampai-ast-12) (AST-01 sampai AST-12 selesai: skema 9 tabel, UpdateMeter monotonik & offset kumulatif reset, WhatsApp link generator normalisasi 62xxx & pesan template, RecordService/RenewDocument/DisposeAsset dengan sinkronisasi Finance expense & PlannedItem, UI Livewire aset/perawatan/dokumen/kerusakan/vendor/lokasi, sumber kalender/alert, widget dashboard, backup exporter, artisan commands, 23 feature test modul Asset lulus, total 390 test green) |
| H. Hardening dan go-live | Parsial (Langkah 1 sampai 5 selesai) | [LOG-064](logbook.md#log-064---phase-4-hardening-penutupan-safety-net-test-gap--bugfix-streamed-context) (Langkah 1: test gap & bugfix streamed context); [LOG-065](logbook.md#log-065---phase-4-hardening-verifikasi-browser-responsivitas-mobile-360px--aksesibilitas) (Langkah 2: verifikasi browser mobile 360px & desktop, skip link WCAG 2.4.1, reduced motion WCAG 2.3.3); [LOG-066](logbook.md#log-066---phase-4-hardening-audit-lighthouse-penutupan-p-21-smtp-dan-verifikasi-q-m11) (Langkah 3 & 4: Lighthouse PWA criteria, penutupan P-21 command uji SMTP `core:send-test-email`, verifikasi retensi backup Q-M11 di config/backup.php, 415 test green); [LOG-067](logbook.md#log-067---phase-4-hardening-drill-backup-dan-restore-sqlite-terisolasi-serta-kesiapan-uat) (Langkah 5: simulasi backup dan restore database SQLite WAL terisolasi lulus 100% konsisten). Skenario UAT bersama owner/partner dan rilis v1.0.0 berikutnya |
| I. Peningkatan pasca-MVP (v1.1+) | Sebagian (Gelombang 1: IMP-01, IMP-02, IMP-03 Selesai) | [LOG-068](logbook.md#log-068---penyusunan-rencana-fitur-peningkatan-pasca-mvp-terprioritas-imp-01-sampai-imp-08), [LOG-076](logbook.md#log-076---sesi-grilling-round-1-penutupan-p-22-adr-012-2fa-otp-adr-013-telegram-bot--inisialisasi-glossarymd), [LOG-077](logbook.md#log-077---sesi-grilling-round-2-penajaman-spesifikasi-teknis-gelombang-1--penutupan-frontier), [LOG-078](logbook.md#log-078---eksekusi-imp-01-otomasi-scheduler-lengkap-daily-digest-roll-task-berulang-dan-purge-soft-deleted), [LOG-079](logbook.md#log-079---eksekusi-imp-02-autentikasi-dua-faktor-2fa-email-otp--8-recovery-codes-sekali-pakai), [LOG-080](logbook.md#log-080---eksekusi-imp-03-split-transaksi-multi-kategori-validasi-invarian-ketat-dan-integrasi-laporan-anggaran): Gelombang 1 v1.1 selesai penuh, P-19 & P-22 ditutup. |

## 3. Langkah berikutnya: selesaikan CORE-01

Scaffold standar/server selesai pada LOG-009; tambahan tooling dan fondasi pada LOG-010; auth/frontend dasar pada LOG-011. Langkah 2-6 telah diimplementasikan dan diverifikasi lokal. Gate Boost langkah 7 dipenuhi lewat terminal per LOG-013; langkah 8 lulus di GitHub pada LOG-014. Branch baseline telah dipublikasikan dengan izin reviewer. **Bagian ini kini historis:** reviewer menerima CORE-01 pada LOG-015, sehingga "langkah berikutnya" bukan lagi acceptance CORE-01 melainkan task Core pada §4. Langkah di bawah dipertahankan sebagai catatan cara fondasi dibangun, bukan instruksi aktif.

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
| 2 | CORE-04 | Household/member, role/policy, slot atomik maksimal dua anggota; uji isolasi dan konkurensi SQLite/MariaDB. Status LOG-039: konkurensi dibuktikan deterministik (unique `(household_id, slot)` menolak anggota ketiga tanpa guard, race yang kalah gagal tertutup dengan pesan domain, slot unik per household) dan berjalan di kedua engine lewat CI; tidak ada test proses paralel OS-level. Sisa CORE-04: pengecualian trait tenant (P-06) |
| 3 | CORE-05 | Invite-only, hash token, expiry/reuse/revoke, alur login/reset; uji registrasi tanpa undangan ditolak. Status LOG-032/LOG-034: backend undangan partner sesuai §5.1 selesai; LOG-036: email undangan partner, halaman penerimaan, reset kata sandi; LOG-040: batas 10 undangan/jam (SEC-3); LOG-041: satu undangan aktif per household dan admin dilarang menjadi member; LOG-043: alur A admin untuk undangan household baru (`InviteNewHousehold`, `AcceptNewHouseholdInvitation`, `RegisterInvitedOwner`, Livewire UI) dan command artisan `core:create-admin` menutup P-17. Sisa CORE-05: antrean mail (CORE-10/P-14) |
| 4 | CORE-06 | Onboarding household, timezone, periode, undang partner opsional. Status LOG-044: wizard 3 langkah selesai (nama household, zona waktu & hari mulai 1–28, undang partner opsional atau lewati, middleware `EnsureHouseholdOnboarded`, 11 test fitur lulus) |
| 5 | CORE-07 | Layout/navigasi/FAB, komponen bersama, light/dark; uji mobile 360px dan keyboard. Status LOG-045: master shell layout mobile/desktop §9.2, bottom nav 5 item, FAB quick add sheet, 12 komponen Blade §9.5, dark mode, dashboard awal, 12 test fitur lulus |
| 6 | CORE-08 | Recurrence wrapper/preset/humanize; uji akhir bulan dan batas recurrence. Status LOG-046: selesai (`App\Core\Recurrence\Recurrence`, pembatas 500 kejadian, preset RFC 5545, lokalisasi teks bahasa Indonesia/Inggris, 12 unit test lulus) |
| 7 | CORE-11 | Calendar/Alert contracts dan aggregator; uji sumber dummy, filter, urutan, timezone, batas rentang. Status LOG-047: selesai (`CalendarItem`, `CalendarSource`, `CalendarAggregator` batas 400 hari, `Alert`, `AlertSource`, `AlertAggregator` prioritas level danger/warning/info, 8 unit test lulus) |
| 8 | CORE-09 | Reminder/delivery per penerima, quiet hours, cancel/idempotensi/retry; **putuskan Q-M9 sebelum mulai**. Status LOG-048: selesai (`Reminder`, `ReminderDelivery`, `QuietHoursCalculator`, `ReminderService`, command `reminders:dispatch` scheduler, penutupan P-08, 15 unit/feature test lulus) |
| 9 | CORE-10 | Notification center, preferensi channel, web push/email, notifikasi uji. Status LOG-049: selesai (migrasi notifications & push_subscriptions dengan morph ULID, notification center `/notifications`, pengaturan `/settings/notifications`, ReminderNotification & TestNotification, ShouldQueue pada HouseholdInvitationMail menutup P-14, 7 test fitur lulus) |
| 10 | CORE-12 | Dashboard kerangka dari layout dan aggregator; widget modul nonaktif tidak ditampilkan. Status LOG-050: selesai (`DashboardWidget` interface, `DashboardManager` mengonsumsi `AlertAggregator` & `CalendarAggregator`, greeting dinamis waktu lokal, filter feature flag modul, 3 test fitur lulus) |
| 11 | CORE-13 | PWA install/offline read-only, update, clear cache pada perubahan auth; uji tidak ada kebocoran cache. Status LOG-051: selesai (`manifest.json`, `sw.js` cache isolation, `offline.html`, banner offline Blade, meta PWA di semua layout, 5 test fitur lulus) |
| 12 | CORE-14 | Profil, household/member, activity log, keamanan; **putuskan Q-M12 sebelum implementasi OTP**. Status LOG-052: selesai (`ProfileSettings`, `HouseholdSettings`, `ActivityLogViewer`, edit profil, ubah password, kelola anggota/undangan/transfer/keluar, P-19 dicatat untuk Q-M12, 7 test fitur lulus) |
| 13 | CORE-15 | Panel admin sesuai §5.2 poin 5 dan uji batas akses (AC: admin tidak dapat membuka route data domain household lain). Status LOG-052: selesai (`AdminDashboard`, middleware `EnsureSystemAdmin`, daftar household, kelola/nonaktifkan user, `disabled_at`, penolakan akses admin ke route domain household terbukti 403, 7 test fitur lulus) |
| 14 | CORE-02 | Siapkan dan uji host Linux bare-metal, Nginx/PHP-FPM, worker/scheduler, zrok, restart service, dan prosedur deploy. Status LOG-052: terpasang dan diverifikasi aktif pada Odroid-C2 Armbian (`nginx` port 80 aktif, `php8.5-fpm` aktif, `family-hub-worker.service` aktif, scheduler cron `reminders:dispatch` & `backup` aktif, SQLite WAL migrasi sukses) |
| 15 | CORE-16 | Backup terenkripsi, ekspor household, alert kegagalan, restore terisolasi; finalisasi bukti operasional. Status LOG-052: selesai (Spatie Laravel Backup terkonfigurasi, jadwal harian `backup:clean` 01:30 & `backup:run` 02:00, kontrak `HouseholdExporter`, `CoreHouseholdExporter`, `HouseholdExportService` pembuat ZIP mandiri teruji, 2 test fitur lulus, P-20 dicatat untuk Q-M11) |

CORE-02 dapat dipersiapkan setelah CORE-01 sesuai Masterplan; ditempatkan akhir urutan implementasi ini agar alur aplikasi dapat diuji end-to-end. AC CORE-02 meminta backup aktif, sedangkan implementasi backup ada di CORE-16 yang bergantung pada CORE-02. Perlakukan sebagai dua gate: infrastruktur CORE-02 tersedia untuk mengerjakan CORE-16, tetapi **jangan tandai CORE-02 sepenuhnya selesai** sebelum backup dan restart end-to-end terbukti. Jika reviewer menghendaki perubahan dependensi formal, perbarui Masterplan terlebih dahulu.

**Gate Phase 0:** seluruh task dan AC selesai; login/undangan berfungsi di HP melalui HTTPS, PWA dapat dipasang, notifikasi uji diterima, dan batas household dibuktikan. Gunakan data sintetis sampai syarat operasional/privasi terpenuhi.

## 5. Phase 1: Finance

Rujukan: [spesifikasi Finance](01-MODULE-FINANCE.md). Seluruh task FIN-01 sampai FIN-16 telah selesai diimplementasikan dan diverifikasi:

1. **FIN-01, FIN-02:** skema/model/enum/factory/provider, kategori dan demo Indonesia. Status LOG-055: selesai (8 tabel migrasi `2026_10_28_000001_finance_create_tables`, 10 enum PHP lengkap label/warna/icon/lokalisasi, 8 model Eloquent dengan tenant boundary, event `HouseholdCreated` + listener `SeedDefaultFinanceCategories`, action `SeedDefaultCategories` lengkap 16 kategori pengeluaran & 8 pemasukan & sistem per §11, `FinanceDemoSeeder`, provider terdaftar, flag `config('modules.finance') => true`, 7 test fitur baru lulus).
2. **FIN-03, FIN-04:** akun/saldo/arsip dan kategori dua level, mapping alokasi, gabung/arsip. Status LOG-057: selesai (`AccountBalanceService`, penyesuaian saldo langsung `AdjustAccountBalance`, rekonsiliasi akhir periode `ReconcileAccounts`, penggabungan kategori `MergeCategories`, proteksi kategori sistem).
3. **FIN-05, FIN-06:** transaksi/transfer/quick add/undo/lampiran, daftar/filter/detail. Status LOG-057: selesai (komponen transaksi Livewire, drawer/modal quick add, undo banner, parsing nominal rupiah aman).
4. **FIN-07:** review struk manual dan payee suggestions. Status LOG-057/LOG-069: selesai (komponen review struk, saran penerima/merchant berbasis riwayat frekuensi transaksi, pemindaian OCR biner tesseract lokal dengan segmentasi PSM 6/4 tangguh, ekstraksi total nominal bertingkat, dan normalisasi multi-format angka).
5. **FIN-08:** anggaran per kategori dan alert threshold. Status LOG-057: selesai (pengecualian BR-FIN-030 untuk transaksi PlannedItem, alert threshold 80% & 100% via `BudgetThresholdNotification`).
6. **FIN-09:** rencana lima alokasi pendapatan. Status LOG-057: selesai (integer basis points 0..10000, peringatan rasio hutang > 30%, net transfer tabungan).
7. **FIN-10, FIN-11:** PlannedItem dan tagihan rutin. Status LOG-057: selesai (generator horizon idempoten `GeneratePlannedItemsFromRecurringBills`, auto-recompute status bertingkat `RecomputePlannedItem`, penandaan lewat tempo `MarkOverduePlannedItemsCommand`, skip/unskip).
8. **FIN-12:** pinjaman dan cicilan. Status LOG-057: selesai (`Loan`, jadwal cicilan otomatis `GenerateLoanInstallments` dengan clamping akhir bulan 31 Jan -> 28/29 Feb, pelunasan dipercepat `AcceleratedLoanSettlement`, riwayat pembayaran).
9. **FIN-13:** rencana bulanan (Monthly Plan). Status LOG-057: selesai (tampilan `/finance/plan`, sisa komitmen dan pemenuhan alokasi bulanan).
10. **FIN-14:** overview/laporan/ekspor. Status LOG-057: selesai (dashboard laporan `/finance/reports`, ringkasan pemasukan vs pengeluaran, perbandingan realisasi vs anggaran, grafik arus kas, ekspor CSV `ExportTransactionsCsvController`).
11. **FIN-15:** integrasi modular & gateway. Status LOG-057: selesai (`FinanceGateway`, `DefaultFinanceGateway`, `NullFinanceGateway`, `FinanceCalendarSource`, `FinanceAlertSource`, `FinanceSummaryWidget`, `FinanceCashflowWidget`, `FinanceHouseholdExporter` ZIP backup).
12. **FIN-16:** pengujian menyeluruh & isolasi tenant. Status LOG-057: selesai (9 test file feature, 346 total test Pest lulus / 2515 assertion, 0 gagal, Pint lulus, PHPStan level 6 [OK] no errors 140 file, build Vite bersih).

**Gate Phase 1:** Seluruh AC dan test modul lulus. Langkah berikutnya adalah Phase 2: Organizer (ORG-01 sampai ORG-13) sesuai masterplan.

## 6. Phase 2: Organizer

Rujukan: [spesifikasi Organizer](02-MODULE-ORGANIZER.md). Seluruh task ORG-01 sampai ORG-13 telah selesai diimplementasikan dan diverifikasi:

1. **ORG-01:** skema/model/provider/feature flag. Status LOG-059: selesai (migrasi 8 tabel `org_tasks`, `org_task_items`, `org_notes` dengan privasi scope, `org_note_items`, `org_shopping_lists`, `org_shopping_items`, `org_events`, `org_event_exceptions`, 7 enum PHP, 8 model Eloquent dengan `BelongsToHousehold` & ULID, `OrganizerServiceProvider`, feature flag `modules.organizer => true`, 7 test lulus).
2. **ORG-02, ORG-03, ORG-04:** tugas, recurrence/rotasi/undo, dan penyelesaian dengan pengeluaran melalui FinanceGateway. Status LOG-060: selesai (`CreateTask`, `CompleteTask`, `UndoCompleteTask`, Livewire `Tasks\Index` & `Tasks\Form`, rotasi assignee, single active recurrence instance BR-ORG-001/002/003, expense link US-ORG-04).
3. **ORG-05:** catatan/checklist, privat, konflik edit, arsip/tempat sampah. Status LOG-060: selesai (Livewire `Notes\Index` & `Notes\Editor`, card color, Markdown aman, deteksi konflik timestamp, filter pin/label/arsip/trash, isolasi privasi BR-ORG-010).
4. **ORG-06, ORG-07:** belanja kolaboratif/parser/poll dan transaksi riil pengeluaran. Status LOG-060: selesai (`ShoppingItemParser`, Livewire `Shopping\Index` & `Shopping\Show`, live polling 10s, dialog selesai belanja dengan pencatatan `FinanceGateway`, belanja lagi).
5. **ORG-08, ORG-09, ORG-10:** acara/exception, sources, lalu kalender Bulan/Agenda. Status LOG-060: selesai (`OrganizerEventCalendarSource`, `OrganizerTaskCalendarSource`, Livewire `Calendar\Index`, grid bulan 7-kolom, agenda 60 hari, modal quick add).
6. **ORG-11, ORG-12:** ekspor ICS, pengingat/alert. Status LOG-060: selesai (`CalendarExportController` feed RFC 5545 Spatie icalendar-generator, `OrganizerAlertSource`, Livewire `Reminders\Index` tunda/batalkan pengingat).
7. **ORG-13:** widget/exporter/polish. Status LOG-060: selesai (`OrganizerSummaryWidget`, `OrganizerHouseholdExporter` 4 dataset JSON, 19 feature test lulus).

**Gate Phase 2:** Seluruh AC dan test modul lulus (367 test green, Pint bersih, Larastan no errors). Langkah berikutnya adalah Phase 3: Asset (AST-01 sampai AST-12).

## 7. Phase 3: Asset

Rujukan: [spesifikasi Asset](03-MODULE-ASSET.md).

1. **AST-01, AST-02, AST-03:** skema/seed, inventaris/foto/detail, lokasi/vendor/WhatsApp. Status LOG-061: selesai (migrasi 9 tabel `ast_*`, enum & model dengan `BelongsToHousehold` & ULID, seeder default data, Livewire aset CRUD/detail, lokasi CRUD, vendor CRUD dengan generator tautan WhatsApp).
2. **AST-04, AST-05:** dokumen/perpanjang serta meter/offset reset kumulatif. Status LOG-061: selesai (dokumen masa berlaku, aksi `RenewDocument` dengan opsi sinkronisasi pengeluaran Finance, service `UpdateMeter` dengan validasi monotonik dan offset kumulatif reset BR-AST-003).
3. **AST-06, AST-07, AST-08:** rencana perawatan/template/snooze, catatan servis, dan kerusakan. Status LOG-061: selesai (rencana perawatan berbasis waktu/meter/keduanya, template default kendaraan/elektronik, tunda perawatan hingga tanggal/odometer, aksi `RecordService` dan `DeleteServiceRecord` dengan rollback status, pelaporan dan penyelesaian isu kerusakan).
4. **AST-09, AST-10:** transaksi dan rencana Finance; kaitkan pembayaran tanpa transaksi ganda, batalkan rencana lama dengan benar. Status LOG-061: selesai (pembuatan transaksi pengeluaran otomatis via `FinanceGateway`, pengaitan ke PlannedItem rencana perawatan, pembatalan/penghapusan pengeluaran saat servis dibatalkan).
5. **AST-11:** kalender, alert, reminder, widget, dan job. Status LOG-061: selesai (`AssetMaintenanceCalendarSource`, `AssetDocumentCalendarSource`, `AssetIssueCalendarSource`, `AssetAlertSource`, widget dashboard `AssetAttentionWidget`, command `assets:refresh-due-dates` dan `assets:odometer-reminders`).
6. **AST-12:** pelepasan aset, exporter, polish, UAT. Status LOG-061: selesai (aksi `DisposeAsset` status sold/disposed/lost dengan deaktivasi rencana dan pembatalan planned items, `AssetHouseholdExporter` ekspor 9 dataset JSON, 23 feature test lulus).

**Gate Phase 3:** Seluruh AC dan test modul Asset lulus (23 feature test green, total 390 test passing, Pint bersih, Larastan level 6 tanpa error, build Vite sukses). Langkah berikutnya adalah Phase 4: Hardening, UAT, dan Rilis.

## 8. Phase 4: Hardening, UAT, dan rilis

1. **Regression test & hardening (Status LOG-064: selesai langkah 1):** penutupan safety net test gap (ekspor CSV transaksi, guard hapus lokasi aset, isolasi privasi catatan backup), perbaikan bug context pada `StreamedResponse`, 404 test Pest/PHPUnit lulus (2816 assertions), Pint bersih, Larastan Level 6 tanpa error (226 file), dan Vite build produksi sukses.
2. **Verifikasi browser & aksesibilitas (Status LOG-065: selesai langkah 2):** verifikasi viewport mobile 360px & desktop 1280px, ketiadaan horizontal overflow (`overflow-x: hidden`), implementasi skip link keyboard WCAG 2.4.1 (`#main-content`), implementasi preferensi `@media (prefers-reduced-motion: reduce)`, banner offline PWA, touch targets minimum 44px, dan 410 test fitur lulus.
3. **Audit Lighthouse & kesiapan PWA (Status LOG-066: selesai langkah 3):** verifikasi manifest PWA (name, icons 192/512 maskable, standalone, theme color), service worker cache lifecycle, halaman offline mandiri (`offline.html`), budget aset produksi (<200KB CSS, <5KB JS), semantic tags dan color contrast ratio.
4. **Resolusi item pending & konfigurasi produksi (Status LOG-066: selesai langkah 4):** penutupan P-21 dengan command `php artisan core:send-test-email` dan `TestEmailMail` untuk pengujian fisik SMTP; verifikasi kebijakan retensi backup terenkripsi Q-M11 di `config/backup.php` (7 hari harian, 4 minggu mingguan, 6 bulan bulanan, 1 tahun tahunan); verifikasi P-19 (OTP ditunda ke v1.1). P-09 menunggu laporan status remote CI reviewer.
5. **Drill deploy & backup/restore (Status LOG-067: selesai langkah 5):** pengujian Spatie backup SQLite database dan simulasi pemulihan darurat (restore drill) ke direktori terisolasi `/tmp/fh-restore-drill/restored.sqlite` dengan integritas schema penuh (`PRAGMA integrity_check` = `ok`, konsistensi record users & households 100%), penyelarasan `docs/runbook/backup-restore.md` dan checklist UAT `docs/uat-go-live.md`.
6. **Audit UI Menyeluruh & Standardisasi Desain Frontend (Status LOG-071 & LOG-073: selesai):** pemasangan skill resmi Anthropic `frontend-design` dan ibelick `improve-ui` (`.agents/skills/` dan `.github/skills/`), audit menyeluruh terhadap 82 Blade views (Core, Finance, Organizer, Asset, Settings, Admin, Components), evaluasi kepatuhan desain non-AI slop, tipografi, microcopy, token warna Tailwind 4, target sentuh 44px WCAG 2.1 AA, dan status interaktif. Temuan dan roadmap remediasi didokumentasikan di `ui_audit_report.md`. Seluruh perbaikan Batch 1 (quick wins kritis & konsolidasi layout), Batch 2 (aksesibilitas & responsivitas mobile), dan Batch 3 (sistem desain, polish, tabular-nums) telah selesai diimplementasikan dan diverifikasi bebas error rekursi pada LOG-073.
7. Luluskan [checklist UAT/go-live](uat-go-live.md) bersama owner/partner; jangan mengisi checklist dari asumsi.
8. Catat commit/tag rilis, hasil penerimaan, known issues dan prosedur operasional di logbook; buka akses nyata hanya setelah semua gate rilis terpenuhi.

Tidak perlu menunggu Phase 4 untuk menguji keamanan, aksesibilitas, backup, atau performa: setiap task harus menjaga standar tersebut sejak implementasi.

### 8.1 Phase 5: Rencana peningkatan pasca-MVP (v1.1+)

Rujukan spesifikasi lengkap: [04-IMPROVEMENT-PLAN.md](04-IMPROVEMENT-PLAN.md) ([LOG-068](logbook.md#log-068---penyusunan-rencana-fitur-peningkatan-pasca-mvp-terprioritas-imp-01-sampai-imp-08)). Seluruh item berstatus **Belum dimulai** dan dieksekusi bertahap setelah UAT Phase 4 / rilis v1.0.0 lulus serta urutan prioritas disetujui reviewer (`P-22`), diurutkan dari prioritas tertinggi (#1) ke terendah (#8):

1. **Prioritas #1 (`IMP-01` — P1 Kritis · Core, Organizer, Asset):** **[SELESAI - LOG-078]** Otomasi scheduler penuh (`routes/console.php`), pengiriman Daily Digest pagi (`digest:send`), penggulungan tugas berulang (`organizer:roll-recurring-tasks`), penjadwalan `assets:refresh-due-dates` & `assets:odometer-reminders`, serta purge permanen household soft-deleted >30 hari (`households:purge-deleted`, `SEC-9`).
2. **Prioritas #2 (`IMP-02` — P1 Tinggi · Core Security):** **[SELESAI - LOG-079]** Autentikasi Dua Faktor (2FA) Email OTP opsional per user & 8 Recovery Codes sekali pakai (menutup `P-19` / `Q-M12` / `SEC-2`).
3. **Prioritas #3 (`IMP-03` — P2 Tinggi · Finance, Organizer):** **[SELESAI - LOG-080]** Split transaksi multi-kategori (`fin_transaction_splits`) dengan integrasi review scan struk OCR lokal & checkout `ShoppingList`.
4. **Prioritas #4 (`IMP-04` — P2 Tinggi · Finance):** Kantong Tabungan Tujuan / *Sinking Funds* (`FIN-V11-1`) dengan earmarking virtual di atas akun tabungan & proyeksi sisihan per `PeriodResolver`.
5. **Prioritas #5 (`IMP-05` — P2 Menengah-Tinggi · Core + Semua Modul):** Pencarian terpadu lintas modul (*Global Household Search* `Ctrl+K` / mobile search) berbasis kontrak `SearchSource` & `SearchAggregator` dengan penegakan `HouseholdScope` dan privasi catatan `BR-ORG-010`.
6. **Prioritas #6 (`IMP-06` — P3 Menengah · Organizer):** URL langganan kalender live `.ics` (`webcal://`) 1-arah bertoken privat yang dapat dicabut/diputar ulang serta sumber kalender Libur Nasional Indonesia lokal.
7. **Prioritas #7 (`IMP-07` — P3 Menengah-Rendah · Asset, Finance):** Prediksi tanggal jatuh tempo servis dari rata-rata laju meter harian, analisis *Total Cost of Ownership* (TCO), dan cetak label QR internal aset.
8. **Prioritas #8 (`IMP-08` — P4 Terendah · Finance):** Wizard impor CSV mutasi bank/e-wallet dengan deduplikasi semi-otomatis (`maatwebsite/excel`) dan laporan keuangan bulanan ramah cetak PDF (`@media print`).

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

**Workflow UI wajib (LOG-012):** khusus repository ini, batch yang memengaruhi tampilan atau perilaku browser menggunakan `premium-frontend-ui` untuk implementasi, `web-design-reviewer` untuk review visual, lalu `webapp-testing` untuk perilaku/regresi; perbaikan diikuti pengecekan ulang yang terdampak. Ketiganya berbagi sesi browser/bukti, bukan mengedit paralel. Baseline 360px/1280px light/dark, viewport tambahan sesuai dampak. Sejak LOG-037 verifikasi browser memakai Playwright lebih dulu: `playwright-core` dipasang di direktori sementara di luar repo dan menjalankan Chrome sistem, sehingga tidak ada dependency, lockfile, atau unduhan browser baru; bila itu gagal, fallback Chrome/CDP dilaporkan. Aturan desain/stack Family Hub mengalahkan efek generik skill premium; tidak ada instalasi dependency aplikasi otomatis. Perubahan dokumentasi/backend tanpa dampak browser tidak memicu workflow. Aturan lengkap dan fallback discovery ada di [instruksi repository](../.github/copilot-instructions.md#mandatory-coordinated-ui-workflow). Ini instruksi agent, bukan watcher setiap save; discovery host berikutnya belum diverifikasi. CORE-01 tetap parsial.

1. Cocokkan hasil dengan task/AC dokumen sumber; tulis apa yang belum terbukti.
2. Tambahkan entri logbook dengan tanggal, task, perubahan, command/hasil, blocker, dan referensi commit/PR jika benar-benar tersedia.
3. Perbarui tabel posisi saat ini hanya berdasarkan bukti tersebut; tautkan entri logbook terbaru. Jika hanya sebagian, gunakan Parsial.
4. Perbarui setup/runbook/ADR jika perilaku, tooling, atau keputusan berubah.
5. Minta reviewer memverifikasi sebelum menutup task dan mengambil task berikutnya.
6. Untuk perubahan struktural, tinjau seluruh dokumen proyek, perbarui semua yang terdampak, dan catat hasil review termasuk dokumen yang tetap sesuai. Ikuti [development-docs-sync](../.github/skills/development-docs-sync/SKILL.md); satu batch cukup satu entri log tanpa rekursi pencatatan.
