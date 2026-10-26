# Development Setup dan Rencana - Family Hub

**Baseline:** 2026-10-26

**Keputusan aktif LOG-023:** reviewer mengizinkan merge branch aman `core-01-foundation` ke main dan menunda MariaDB; SQLite menjadi satu-satunya target CI aktif sementara. Fondasi `42bda38` di-fast-forward ke main tanpa membawa `core-03-support`. Branch support tetap menyimpan checkpoint CORE-03/04 dan CI `40286560448` yang SQLite lulus/MariaDB gagal; kegagalan tersebut belum diperbaiki dan tidak boleh disebut lulus. Sebelum merge support berikutnya, integrasikan keputusan SQLite ini dan jalankan CI ulang. Ketentuan lama matriks kedua DB di dokumen ini ditunda oleh keputusan ini, bukan bukti kompatibilitas MariaDB. Main hanya memuat fondasi, bukan fitur household/support lanjutan. Tidak ada migration/seed aplikasi atau provisioning akun.
**Status:** Reviewer mengizinkan kelanjutan sesuai plan dengan perhatian pada credit Copilot; checkpoint fondasi CORE-01 berjalan, task belum selesai.

**Arahan terbaru (LOG-014):** SQLite dipilih untuk development; tidak memasang MariaDB lokal atau menghapus matriks kompatibilitas CI. Reviewer memilih menuntaskan CI CORE-01 sebelum CORE-03 dan mengizinkan branch khusus, commit, serta push fondasi ke repository miliknya (bukan ke main). Hasil remote harus dicatat setelah run selesai, bukan diasumsikan dari test lokal.

Dokumen ini menggabungkan setup environment aktual dan rencana implementasi. Riwayat pekerjaan tetap dicatat di [logbook](logbook.md); keputusan stack mengikuti [ADR-011](adr/ADR-011-current-stack.md) dan [Masterplan](00-MASTERPLAN.md).

## Setup environment

### Installed baseline

- PHP 8.5.12 with local development extensions enabled.
- Composer 2.10.3, installed for the current Windows user from the official installer after SHA-384 verification.
- Node.js 24.22.1 LTS and npm 11.19.1.
- Root [composer.json](../composer.json) and [composer.lock](../composer.lock): Laravel 13, Fortify, Livewire 4, approved domain packages, Heroicons, Pest 5 and Laravel plugin, Pint, Larastan, and development-only Boost/Mockery. Mockery was approved for Laravel's database-testing lifecycle in the ADR-011 addendum.
- Root [package.json](../package.json) and [package-lock.json](../package-lock.json): Vite 8, Laravel Vite plugin 3, Tailwind 4, and its Vite integration.
- The [mockup](../mockup/package.json) remains a separate npm project; its files and dependencies are unchanged.

The official Laravel 13.0.0 skeleton is now integrated with the existing Laravel Framework 13.38.0 dependencies. Artisan, HTTP bootstrap, config, default welcome view, stock User/factory/seeder/migrations, PSR-4 mappings, example tests, Vite configuration, and Composer scripts are present. These are framework defaults, not Family Hub models or features; default integer IDs and migrations must be reconciled with the approved ULID/tenancy design before any domain work.

A local ignored `.env` and app key exist. Session/cache use files so the welcome/login pages render without a database; SQLite remains configured, but no local application database has been created, migrated, or seeded. Tests migrate only isolated in-memory databases. Queue stays configured for the database driver but no worker is running. Fortify is enabled with an empty feature list: login/logout and password confirmation work, while registration/reset/TOTP/passkeys remain disabled. Passkeys auto-discovery stays excluded. Boost MCP configuration for Copilot is installed in `.vscode/mcp.json`; stdio initialization and its documentation tool were tested, but the actual VS Code Copilot connection is not yet confirmed.

Open `/login` for the Indonesian Livewire/Blade form, styled with Tailwind and bundled Alpine. `.env.example` and the existing local `.env` use `APP_LOCALE=id`; English auth labels are also available. Native POST forms delegate authentication to Fortify, including lowercase email, session rotation, and a five-per-minute limiter keyed by normalized email and IP. Success returns to the default `/` welcome page, not a household dashboard. There is no persistent account or authorized account-creation workflow yet; do not invent demo credentials or migrate/seed the placeholder schema just to try login manually. Invitation/account provisioning belongs to later Core tasks.

Core/Modules placeholder directories and `config/modules.php` exist, with all unreleased modules disabled. Non-production Eloquent lazy-loading prevention is enabled. Permanent Pint PSR-12 and Larastan level 6 configurations and Composer `lint`/`analyse` scripts are available. CI is defined in `.github/workflows/ci.yml` for PHP 8.5 with SQLite and MariaDB 10.11.

**Bukti CI terbaru (LOG-014):** [Run 40268711517](https://github.com/shadri-zulkurniadi/Net/actions/runs/40268711517) pada `ffe4afe` lulus untuk kedua job SQLite/MariaDB, termasuk dependency install, Composer validate/platform, build, Pint, Larastan, dan test. Tidak ada server MariaDB lokal yang dipasang.

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
```

For a fresh clone only, copy `.env.example` to `.env` if absent, then run `php artisan key:generate`. Never overwrite an existing `.env` or regenerate a key for data that depends on it.

### Run the local scaffold

```powershell
php artisan serve --host=127.0.0.1 --port=8000 --tries=1
```

Alternatively run `composer serve`. Laravel uses `artisan serve`, not CodeIgniter's `spark serve`. Open `http://127.0.0.1:8000`; health check is `/up`. The server was left running on loopback for reviewer use, not exposed to the LAN or internet. It is a development server, not a production deployment. If the port is already occupied, reuse the existing instance or select another port; do not terminate unrelated processes.

Use the repository lockfiles. Do not run `composer update` or `npm update` just to restore an environment. Composer permits only the Pest plugin; installation skips application scripts. npm lifecycle scripts are disabled; Vite and its plugins were verified to load on this Windows x64 environment.

The local PHP installation is managed by WinGet. Its `php.ini` is in the same directory as `php.exe`; find it with `php --ini`. Enabled DLL extensions: curl, openssl, mbstring, intl, fileinfo, exif, gd, zip, pdo_sqlite, sqlite3, pdo_mysql, and sodium. PHP also supplies built-in XML, DOM, bcmath, and other required modules. This is a development configuration with error display enabled, not a production PHP configuration. Recheck the INI after a WinGet upgrade.

Composer is installed in `%LOCALAPPDATA%\Programs\Composer` with a `composer.cmd` launcher on the user PATH. `vendor` and `node_modules` are ignored by Git; lockfiles and the reviewed foundation were committed/pushed on the approved `core-01-foundation` branch at `ffe4afe` (LOG-014), not merged into main. No credentials belong in the manifests or source control.

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
- `composer test`: 18 tests pass, 66 assertions, including foundation checks and auth rendering, credential validation/error display/email retention, login/logout/session rotation, rate limiting, disabled feature routes, authenticated/guest redirects, and valid/invalid password confirmation. Auth tests use synthetic users and SQLite in-memory. The stock trivial unit example is not evidence of domain coverage.
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
| C. CORE-01 | Teknis/CI lulus; menunggu acceptance reviewer | [LOG-011](logbook.md#log-011---checkpoint-auth-core-01): 18 test/66 assertion lokal dan browser auth; [LOG-013](logbook.md#log-013---boost-melalui-terminal-perantara): Boost terminal; [LOG-014](logbook.md#log-014---publikasi-fondasi-untuk-ci-core-01): GitHub CI SQLite/MariaDB lulus pada `ffe4afe` |
| D. Core lainnya | Belum dimulai | CORE-02 sampai CORE-16 belum selesai; runbook masih draft |
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
| 1 | CORE-03 | Money, PeriodResolver, tenant support, komponen uang/tanggal; uji parser, perubahan periode, tahun kabisat, dan histori |
| 2 | CORE-04 | Household/member, role/policy, slot atomik maksimal dua anggota; uji isolasi dan konkurensi SQLite/MariaDB |
| 3 | CORE-05 | Invite-only, hash token, expiry/reuse/revoke, alur login/reset; uji registrasi tanpa undangan ditolak |
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
| Kelanjutan CORE-01 | Sebelum menutup task | Teknis/CI kedua DB lulus; menunggu acceptance reviewer sebelum CORE-03 |
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
