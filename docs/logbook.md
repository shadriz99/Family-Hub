# Logbook Development - Family Hub

**Diperbarui:** 2026-10-27
**Status:** CORE-01 diterima untuk kelanjutan development; CORE-03 dinyatakan selesai oleh reviewer; CORE-04 parsial (konkurensi slot selesai); CORE-05 selesai di sisi Alur A & B (backend, email, UI, limiter 10/jam, 1 aktif, create-admin menutup P-17); CORE-06 (onboarding wizard) selesai pada LOG-044; CORE-07 (layout, navigasi, FAB, dark mode, komponen §9.5) selesai pada LOG-045; CORE-08 (recurrence wrapper) selesai pada LOG-046. CI remote untuk perubahan ini belum terverifikasi. Belum ada rilis aplikasi.

Logbook ini mencatat pekerjaan aktual, bukan daftar fitur yang diasumsikan selesai. [Rencana development](development-plan.md) menentukan langkah berikutnya; spesifikasi dan acceptance criteria tetap mengikuti [Masterplan](00-MASTERPLAN.md) dan dokumen modul.

## Ringkasan status

| Area | Status aktual | Bukti / tindak lanjut |
|---|---|---|
| Repository | `main` tanpa branch; batch terakhir LOG-046 | LOG-014: fondasi `ffe4afe`; LOG-026: checkpoint CORE-03/04 di-merge; LOG-031: CI hijau `731fb45`; LOG-035: kerja langsung di `main`; LOG-036: push `efae4f2..d077170`; LOG-039: push `2900d9f..fcaf25b`; LOG-040: limiter undangan; LOG-041: 1 aktif, admin non-member; LOG-042: Odroid-C2 WAL; LOG-043: alur A admin, create-admin (P-17); LOG-044: wizard onboarding CORE-06; LOG-045: layout/navigasi & komponen §9.5 CORE-07; LOG-046: recurrence wrapper CORE-08 |
| Environment lokal | Git, PHP, Composer, Node/npm dapat dijalankan | Rincian dan batas setup di [setup dan rencana development](development-plan.md) |
| Dependency PHP/npm | Terpasang, lockfile tersedia | Composer solve, platform check, audit, dan smoke check lulus pada waktu setup |
| CORE-01 | **Diterima untuk kelanjutan development** | LOG-011/013/014: fondasi, auth, Boost, CI kedua DB; LOG-015: reviewer meminta lanjut; bukan UAT/produksi |
| Boost | Terminal stdio disetujui sebagai transport | LOG-013: initialize, daftar 10 tool, dan dokumentasi lulus; UI Copilot belum terhubung tetapi bukan blocker |
| CORE-02 sampai CORE-16 | CORE-03 selesai; CORE-04 parsial; CORE-05 selesai Alur A & B; CORE-06 selesai onboarding wizard; CORE-07 selesai layout & navigasi; CORE-08 selesai recurrence wrapper | LOG-015–018: utilitas/komponen; LOG-019: urutan disetujui; LOG-020–021: policy/action; LOG-024: tenant scope, konteks household, `PeriodResolver::for`; LOG-025–026: MariaDB CI dipulihkan dan checkpoint di-merge ke main; LOG-030: bootstrap + middleware konteks; LOG-032/034: backend undangan §5.1; LOG-036: email undangan, penerimaan partner, reset kata sandi; LOG-039: konkurensi slot; LOG-040: limiter 10 undangan/jam; LOG-041: satu undangan aktif, admin bukan anggota household; LOG-042: optimasi host Odroid-C2, SQLite WAL; LOG-043: alur A admin, create-admin (P-17); LOG-044: wizard onboarding 3 langkah CORE-06; LOG-045: layout shell mobile/desktop, bottom nav 5 item, FAB, dark mode, 12 komponen §9.5 CORE-07; LOG-046: recurrence wrapper CORE-08. Konteks job, backfill histori, pengecualian trait, dan antrean mail belum selesai |
| Finance / Organizer / Asset | Belum dimulai | Mockup adalah prototype yang sudah ada saat clone, bukan implementasi modul |
| Produksi / UAT | Belum dijalankan | Tidak ada bukti deploy, restore, atau penerimaan pengguna |

## 2026-10-26

### LOG-001 - Repository dan tooling dasar

- Mengatasi Git yang awalnya tidak tersedia di PATH; verifikasi akhir Git `2.56.0.windows.3`.
- Memasang PHP 8.5.12 melalui WinGet dan menguji eksekusi CLI.
- Repository berhasil tersedia di folder `Net`; pull branch `main` terakhir pada tahap ini menghasilkan `Already up to date`.
- Tidak ada bukti agent mengubah pengaturan sandbox/network. Akses command jaringan akhirnya berhasil; perubahan pengaturan tersebut tidak diklaim sebagai pekerjaan agent.
- **Verifikasi:** `git --version`, `php --version`, eksekusi PHP, status repository dan pull.

### LOG-002 - Pembacaan spesifikasi dan pemasangan skill personal

- Membaca instruksi Copilot, Masterplan, spesifikasi Finance/Organizer/Asset, skill repository, serta runbook dan checklist UAT.
- Memasang `ponytail` dan `premium-frontend-ui` dari sumber yang diberikan reviewer ke direktori personal Copilot pada komputer ini, bukan ke repository.
- Memvalidasi metadata skill dan mengaktifkan Ponytail level full pada sesi. Instalasi global ini tidak ikut terbawa saat repository di-clone pada komputer lain.
- **Batas:** discovery UI setelah reload tidak diuji secara terpisah. Tidak mengklaim skill selalu dijalankan pada semua percakapan.

### LOG-003 - Pembaruan stack dan keputusan arsitektur

- Memeriksa rilis stabil dan constraint paket dari sumber resmi, Packagist, dan npm.
- Memperbarui Masterplan ke v0.3: PHP 8.5, Laravel 13, Livewire 4, Tailwind 4, Vite 8, Node 24 LTS, Pest 5, dan Larastan 3.
- Dengan persetujuan reviewer, mengganti rencana Breeze menjadi Fortify + Livewire/Blade proyek; menyetujui Boost 2 sebagai dependency development.
- Mencatat sumber, alasan, constraint, dan batas verifikasi di [ADR-011](adr/ADR-011-current-stack.md); menyelaraskan instruksi Copilot dan acceptance criteria CORE-01.
- Memperjelas scheduler sebagai service Linux terkelola, tanpa Docker.
- **Batas:** keputusan stack bukan bukti aplikasi sudah dapat boot atau fitur autentikasi sudah berjalan.

### LOG-004 - Manifest dan instalasi dependency tanpa coding aplikasi

- Menambahkan [manifest Composer](../composer.json), [lockfile Composer](../composer.lock), [manifest npm](../package.json), [lockfile npm](../package-lock.json), dan aturan ignore untuk dependency/secrets.
- Memasang Composer 2.10.3 untuk user Windows setelah memverifikasi SHA-384 installer resmi.
- Membuat konfigurasi PHP development dan mengaktifkan ekstensi yang diperlukan, termasuk SQLite, PDO MySQL, ZIP, GD, Intl, Mbstring, dan Sodium.
- Memasang dependency PHP yang disetujui, termasuk Laravel 13.38.0, Livewire 4.4.8, Fortify 1.41.0, Boost 2.10.3, dan Pest 5.3.3.
- Memasang Vite 8.3.5, Laravel Vite plugin 3.2.0, Tailwind dan integrasinya 4.3.3. Mockup tidak diubah; ApexCharts ditunda sampai diperlukan dan lisensinya ditinjau.
- Mendokumentasikan restore dependency dan pekerjaan tertunda pada panduan setup (sekarang digabung ke [setup dan rencana development](development-plan.md)).
- **Lulus:** `composer validate` (dengan warning lisensi proyek belum dideklarasikan), `composer check-platform-reqs`, `composer audit --locked`, `npm ls --depth=0`, `npm audit`, versi Vite/Pint/PHPStan, import plugin Vite, autoload PHP, dan query SQLite in-memory.
- **Gagal yang diketahui:** `pest --version` mencoba bootstrap dan gagal karena direktori test belum ada. Tidak dibuat test dummy untuk menyembunyikan kondisi ini.
- **Tidak dijalankan:** scaffold, migration, pembuatan `.env`/app key/database aplikasi, test aplikasi, build aplikasi, CI SQLite/MariaDB, serta `boost:install` dan MCP.

### LOG-005 - Konsolidasi dokumentasi

- Memindahkan Masterplan dan tiga spesifikasi modul dari folder dokumentasi build lama ke [docs](.).
- Memperbarui instruksi Copilot, diagram folder, referensi runbook/UAT, dan tautan relatif; menghapus folder lama setelah dipastikan kosong.
- **Verifikasi:** tidak ditemukan referensi folder lama atau tautan Markdown lokal yang rusak; `git diff --check` lulus.
- **Batas:** hanya organisasi dokumen; aturan domain tidak diubah oleh pemindahan ini.

### LOG-006 - Logbook dan rencana development

- Membuat logbook ini dan [rencana step-by-step](development-plan.md), dengan status awal yang mengacu pada LOG-001 sampai LOG-005.
- Menautkan keduanya dari Masterplan, development setup, dan runbook deploy; mencatat kewajiban sinkronisasi pada instruksi Copilot.
- Menetapkan CORE-01 sebagai langkah coding berikutnya **setelah izin reviewer**, bukan menganggap pemasangan dependency telah menyelesaikannya.
- **Verifikasi:** pemeriksaan tautan lokal Markdown, anchor logbook yang dipakai rencana, pencantuman seluruh task CORE/FIN/ORG/AST, dan `git diff --check` lulus.
- Tidak ada coding aplikasi, instalasi tambahan, commit, atau deploy pada pekerjaan dokumentasi ini.

### LOG-007 - Penggabungan setup dan rencana

- Menggabungkan baseline environment, restore dependency, hasil pemeriksaan, dan batas setup ke [development plan](development-plan.md); dokumen setup terpisah dihapus.
- Memperbarui referensi Masterplan, instruksi Copilot, dan logbook; langkah implementasi dan status CORE-01 tidak berubah.
- Memperjelas bahwa lockfile tersedia di working tree tetapi belum di-commit oleh agent.
- **Verifikasi:** tautan Markdown lokal, referensi dokumen setup lama, dan `git diff --check` diperiksa setelah penggabungan.
- Tidak ada perubahan kode aplikasi atau dependency.

### LOG-008 - Skill sinkronisasi dokumentasi

- Menambahkan [development-docs-sync](../.github/skills/development-docs-sync/SKILL.md) pada repository, sesuai pilihan reviewer, untuk setiap batch perubahan termasuk dokumentasi dan customization.
- Memperkuat aturan wajib pada instruksi Copilot, Masterplan, dan development plan: logbook dan plan selalu diperbarui; perubahan struktural meninjau seluruh dokumentasi dan memperbarui semua yang terdampak.
- Menetapkan pencatatan bukti, status yang tidak dilebihkan, pelestarian histori, dan satu entri per batch tanpa rekursi. Skill bukan watcher atau enforcement CI.
- Perubahan ini menambah workflow dokumentasi, bukan struktur/kontrak aplikasi. Dokumen modul, ADR stack, runbook, dan UAT tidak berubah karena aturan domain, deployment, dan stack tetap sama.
- **Verifikasi:** metadata skill, tautan lokal/anchor terkait, dan whitespace diff diperiksa; discovery skill pada sesi VS Code baru belum diuji.
- CORE-01 tetap parsial dan coding tetap menunggu izin; tidak ada perubahan aplikasi/dependency.

### LOG-009 - Scaffold Laravel dan server lokal

- Reviewer mengizinkan scaffold Laravel dan server lokal saja, tanpa coding fitur Family Hub. Laravel memakai `artisan serve`, bukan `spark serve`.
- Mengambil skeleton resmi `laravel/laravel` v13.0.0 ke direktori sementara dan menyalin file framework tanpa menimpa manifest/dependency/dokumentasi yang sudah ada; runtime tetap Laravel Framework 13.38.0.
- Menambahkan PSR-4, Composer discovery/test/serve scripts, npm build/dev scripts, Vite config, bootstrap, welcome, konfigurasi, dan contoh test upstream. PHP scaffold memakai strict types/PSR-12. Tidak menambah Axios/Tinker/Sail atau paket lain di luar stack yang tersedia.
- Membuat `.env` lokal yang diabaikan Git dan app key; session/cache berbasis file. Tidak membuat database aplikasi atau menjalankan migration/seed. Default User/factory/migrations tetap placeholder framework, bukan ULID/tenancy Family Hub.
- Menonaktifkan auto-discovery Fortify/Passkeys agar default register/TOTP/passkey routes tidak aktif sebelum konfigurasi auth yang disetujui. Test memeriksa welcome/health dan GET/POST register 404.
- **Verifikasi:** Composer autoload/package discovery dan manifest valid (warning lisensi tetap ada); `composer test` lulus 2 test/5 assertion; Pint PSR-12 scaffold lulus; `npm run build` lulus; server loopback memberi HTTP 200 untuk `/`, `/up`, dan aset lokal yang dirujuk welcome.
- Server development dibiarkan berjalan pada `http://127.0.0.1:8000`; jangan menganggap proses ini production service atau akan hidup setelah reboot. Bootstrap diuji melalui HTTP, bukan inspeksi visual browser.
- **Review struktural:** seluruh dokumen `docs/` dan instruksi/skill repository ditinjau untuk dampak status scaffold. Masterplan, plan/setup, ADR-011 (catatan bukti baru), runbook deploy, UAT, logbook, dan instruksi Copilot diselaraskan. Ketiga spesifikasi modul, backup/restore runbook, dan skills tetap sesuai karena tidak ada perubahan aturan domain/backup/workflow.
- **Belum dilakukan:** fitur Core/Finance/Organizer/Asset, konfigurasi Fortify/Boost MCP, Larastan aplikasi, CI SQLite/MariaDB, deploy Linux, dan UAT. CORE-01 tetap parsial. Tidak ada commit/push.

### LOG-010 - Checkpoint fondasi CORE-01

- Reviewer mengizinkan kelanjutan development plan dengan mempertimbangkan credit Copilot. Batch dikerjakan langsung tanpa subagent; saldo credit tidak tersedia bagi agent. Belum beralih ke CORE-03 atau fitur modul.
- Menambahkan placeholder Core/Modules, feature flags semua modul nonaktif, dan pencegahan lazy-loading non-production. Placeholder bukan implementasi domain.
- Menambahkan konfigurasi Pint PSR-12, Larastan level 6, script lint/analyse, Pest setup, dan test fondasi untuk module flags, koneksi DB terpilih, lazy loading, dan render komponen Livewire anonim. Memperbaiki generic factory annotation model skeleton agar analisis tipe lulus.
- Membuat workflow GitHub Actions PHP 8.5 + SQLite/MariaDB 10.11 dengan pemeriksaan platform, test, lint, analyse, dan frontend build. Workflow belum dijalankan di GitHub; tidak ada klaim hasil MariaDB.
- Menjalankan `boost:install --mcp --no-interaction`. Installer juga menghasilkan konfigurasi OpenCode yang tidak diminta; file itu dihapus dan agent dipatok ke `copilot` pada `boost.json`. `.vscode/mcp.json` dipertahankan. Guidelines/skills tidak digenerate agar instruksi tetap utuh dan konteks tidak bertambah tanpa kebutuhan.
- **Lulus:** `composer test` 6 test/9 assertion; `composer analyse` tanpa error; `composer lint`; `npm run build`; MCP initialize/tool listing dan pemanggilan `search-docs` via client stdio sementara. Client dihentikan setelah pengecekan.
- **Kegagalan yang diselesaikan:** format awal file baru diperbaiki Pint; `composer validate` sempat menemukan lock metadata tertinggal akibat perubahan manifest scaffold, lalu `composer update --lock --no-install --no-scripts` menyelaraskannya tanpa perubahan versi paket. Validasi akhir lulus dengan warning lisensi yang tetap terbuka.
- **Review struktural:** Masterplan, ketiga spesifikasi modul, ADR-011, plan/setup, logbook, kedua runbook, UAT, serta instruksi/skills repository diperiksa terhadap dampak tooling/folder/CI. Status pada Masterplan, ADR, plan/log, dan instruksi diselaraskan; spesifikasi modul, runbook, UAT, dan skills tetap berlaku karena kontrak domain maupun deployment tidak berubah.
- **Belum terbukti:** auth Fortify/entrypoint UI Livewire produksi, GitHub CI/MariaDB, koneksi Boost dari UI Copilot sebenarnya, dan acceptance reviewer CORE-01. Tidak ada fitur Finance/Organizer/Asset atau commit/push.

### LOG-011 - Checkpoint auth CORE-01

- Mengaktifkan discovery Fortify dengan daftar fitur kosong dan provider proyek: login/logout, konfirmasi kata sandi, serta limiter 5 request/menit per email lowercase + IP. Registrasi, reset password, TOTP, dan passkeys tetap nonaktif; discovery Passkeys tetap dikecualikan.
- Menambahkan komponen render Livewire dan layout/form Blade proyek memakai native POST ke Fortify agar validasi credential, session rotation, dan logout tetap milik framework. Alpine bawaan menangani tampil/sembunyi password; tanpa dependency UI/font eksternal. Welcome `/` tetap tujuan login, bukan dashboard.
- Menambahkan label/error Indonesia dan label Inggris, locale `id` pada example/local env, validasi HTML, label/aria error, fokus keyboard, serta warna light/dark responsif. Belum ada provisioning akun, undangan, household, atau fitur modul; model/migration integer skeleton tetap placeholder.
- Reviewer menyetujui Mockery sebagai dependency development setelah `RefreshDatabase` gagal sebelum assertion (`Class "Mockery" not found`). Addendum ADR-011 mencatat alasan; Composer memasang Mockery 1.6.15 dan Hamcrest 3.0.0 transitif, tanpa upgrade paket lain. Percobaan CLI pertama kehilangan caret constraint pada wrapper Windows dan dibatalkan Composer; manifest diperbaiki eksplisit lalu resolve berhasil.
- Test memakai user sintetis dan database SQLite in-memory saja; database aplikasi lokal tidak dibuat/dimigrasikan/di-seed. Cakupan meliputi render, credential kosong/salah/terstruktur, lowercase email, login/logout, rotasi/invalidation sesi, batas percobaan, redirect guest/auth, konfirmasi salah/benar, serta route fitur terlarang.
- **Lulus:** `composer test` 18 test/66 assertion, termasuk pesan error Fortify yang tampil di Livewire dan email yang tetap terisi tanpa mengisi ulang password; `composer lint`; `composer analyse`; `npm run build`; `composer validate` (warning lisensi proyek belum dideklarasikan tetap ada); `composer check-platform-reqs`; `git diff --check`. Composer melaporkan tidak ada advisory saat pemasangan dependency. Route login dan konfirmasi diverifikasi; health/login tetap tersedia melalui server loopback (HTTP 200).
- **Browser:** `/login` tampil berbahasa Indonesia dengan Livewire/Alpine; viewport 360px light/dark dan desktop 1280px tidak overflow horizontal, toggle password dan native input validity diperiksa. Interaksi awal Playwright timeout; tool klik browser berhasil menampilkan password, lalu klik programatik browser memverifikasi sembunyi setelah timeout interaksi berikutnya. Tidak mengklaim seluruh interaksi pointer terotomasi stabil. Bukan pengukuran Lighthouse, audit aksesibilitas menyeluruh, atau UAT perangkat fisik.
- **Review struktural:** meninjau dampak auth/routes/dependency pada seluruh docs (Masterplan, tiga spesifikasi modul, ADR-011, plan/log, kedua runbook, UAT) dan instruksi/skills repository. Masterplan, ADR, plan/log, deploy, UAT, dan instruksi diperbarui. Spesifikasi modul, backup/restore, dan skills tetap sesuai: kontrak domain, backup, dan workflow tidak berubah.
- **Batas/lanjutan:** CORE-01 tetap parsial; perlu hasil GitHub CI SQLite/MariaDB, koneksi Boost dari UI Copilot sebenarnya, dan acceptance reviewer. Tidak menjalankan migration/seed aplikasi lokal, membuat akun persisten, mengimplementasikan reset/undangan/household, atau beralih ke CORE-03. Tidak ada subagent, commit, push, atau deploy.

### LOG-012 - Koordinasi otomatis skill UI repository

- Reviewer memilih cakupan Family Hub saja. Menambahkan instruksi wajib untuk perubahan yang berdampak UI/browser: implementasi dengan `premium-frontend-ui`, review visual dengan `web-design-reviewer`, lalu pengujian perilaku dengan `webapp-testing`; perbaikan diikuti pemeriksaan ulang. Dokumentasi/backend tanpa dampak browser dikecualikan.
- Memperbarui description dan koordinasi dua skill repository; fallback membaca file skill jika discovery tidak tersedia. Skill premium tetap personal, tidak disalin atau diubah global. Tidak menjanjikan discovery otomatis pada setiap host.
- Menetapkan prioritas spesifikasi/desain Family Hub, pembagian tanggung jawab, satu sesi/bukti/laporan, baseline 360px/1280px light/dark, serta larangan instalasi Playwright otomatis dan penutupan halaman reviewer. Fallback klik programatik tidak membuktikan interaksi pointer/keyboard.
- **Verifikasi:** meninjau konflik instruksi ketiga skill dengan aturan proyek, metadata/tautan relatif dua skill dan tracking, serta `git diff --check`. Tidak ada perubahan runtime/dependency; test/build/browser aplikasi tidak diulang untuk batch dokumentasi ini.
- **Batas:** ini workflow agent, bukan hook/watcher save-time atau CI gate. Discovery pada sesi/host baru belum diuji. Tidak ada subagent, commit/push, perubahan skema, maupun perubahan status CORE-01 yang masih parsial.

### LOG-013 - Boost melalui terminal perantara

- Reviewer melaporkan Boost belum dapat diaktifkan melalui MCP List Servers dan memilih terminal perantara. Menetapkan jalur stdio sebagai pengganti gate koneksi native Copilot; konfigurasi UI tetap disimpan untuk pemakaian opsional mendatang.
- **Lulus:** client Node sementara tanpa dependency baru menjalankan `php artisan boost:mcp`, initialize/initialized, daftar 10 tool, dan memanggil `search-docs` dengan query generik tentang regenerasi sesi Laravel; respons teks berhasil, tanpa flag error. Tidak mengirim source, data household, atau secret. Timeout 45 detik dan error ditampilkan; child dihentikan setelah operasi.
- Memperbarui AC CORE-01 pada Masterplan, addendum ADR-011, instruksi agent, dan plan. CORE-01 tetap parsial menunggu CI SQLite/MariaDB remote serta acceptance reviewer; riwayat log sebelumnya tetap menyatakan batas pada saat itu.
- **Review struktural:** perubahan hanya transport tooling development. Meninjau dampaknya pada seluruh docs dan instruksi/skills repository; spesifikasi tiga modul, deploy/backup runbook, UAT, dan skills tidak memerlukan perubahan karena tidak mengatur transport Boost atau mengubah kontrak domain/produksi. Tidak membuat service/wrapper permanen, dependency, migration, atau perubahan UI.
- **Verifikasi dokumentasi:** `git diff --check` dan pencarian gate UI usang pada status aktif. Test/build/browser aplikasi tidak diulang untuk perubahan dokumentasi/tooling ini. Koneksi native UI dan CI remote tidak diuji; tidak ada commit/push.

### LOG-014 - Publikasi fondasi untuk CI CORE-01

- Reviewer memilih SQLite sebagai DB development dan secara eksplisit mempertahankan gate CI sebelum CORE-03. Memverifikasi `.env` memakai SQLite; tidak membuat DB aplikasi atau menjalankan migration/seed lokal. Matriks SQLite/MariaDB tetap dipertahankan sesuai AC, tanpa instalasi MariaDB lokal.
- Reviewer mengizinkan branch khusus, commit dan push fondasi/dokumentasi yang disiapkan sejak clone ke repository miliknya, bukan langsung ke main. Identitas penulis Git dikonfirmasi reviewer; hanya disetel lokal repository.
- Sebelum publikasi: workflow GitHub belum tersedia (0 workflow), remote main masih `750a946`. Meninjau inventaris file, ignore, manifest, config test, workflow, dan marker secret pada perubahan yang akan dipublikasikan. `.env`, vendor/node_modules, build, database/cache/log runtime tidak disertakan; tidak ditemukan pola private key, token GitHub/AWS, atau APP_KEY terisi.
- **Lulus lokal:** 18 test/66 assertion pada SQLite in-memory, Pint, Larastan, build Vite, dan Composer validate (warning lisensi proyek masih ada). Skrip inspeksi secret pertama gagal parsing PowerShell sebelum berjalan, lalu diperbaiki dan dijalankan berhasil.
- **Publikasi:** branch `core-01-foundation`, commit `ffe4afea86556414ed2f8e38f9429f523779acc6` berhasil di-push; SHA remote cocok. Pemeriksaan staged menemukan whitespace bawaan scaffold/Markdown yang sebelumnya tidak tercakup diff tracked; whitespace dibersihkan tanpa perubahan perilaku. `.env` dan runtime artifacts tidak masuk commit.
- **CI lulus:** [run pertama 40268711517](https://github.com/shadri-zulkurniadi/Net/actions/runs/40268711517) pada `ffe4afe`; kedua job `checks (sqlite)` dan `checks (mariadb)` berstatus success. Step install Composer/npm, manifest/platform, Vite build, Pint, Larastan, dan test database masing-masing semuanya success (step database lain skipped sesuai matriks). Main tidak diubah; belum membuat PR, merge, atau memulai CORE-03.
- **Review struktural/status:** instruksi, plan/log, Masterplan, ADR-011, dan catatan UAT diselaraskan dengan publikasi/CI. Ketiga spesifikasi modul, kedua runbook, serta skills tetap berlaku; tidak ada kontrak domain atau deploy berubah. CORE-01 memenuhi gate teknis dan menunggu acceptance reviewer, bukan dianggap sudah diterima hanya karena CI hijau.
- Pembaruan bukti setelah CI dipublikasikan sebagai commit dokumentasi terpisah dengan `[skip ci]` agar tidak mengulang job identik hanya untuk mencatat hasil. Bukti runtime/CI tetap merujuk `ffe4afe`; tidak ada kode aplikasi/workflow berubah pada follow-up tersebut. Tautan lokal dan whitespace dokumentasi diperiksa.

### LOG-015 - CORE-03 checkpoint Money

- Setelah CI CORE-01 lulus, reviewer meminta melanjutkan sesuai plan; diperlakukan sebagai acceptance fondasi untuk kelanjutan development, bukan UAT/produksi. Membuat branch lokal `core-03-support` dari fondasi terverifikasi tanpa merge main.
- Menambahkan `App\Core\Support\Money::parse/format`. Format Rupiah bertitik dan negatif sesuai kontrak; parser menerima angka biasa, grup ribuan, prefix Rp, suffix rb/jt dan pecahan koma yang menghasilkan Rupiah bulat. Aritmetika string tanpa float mempertahankan presisi hingga batas signed integer; input buruk, pecahan Rupiah dan overflow melempar exception eksplisit.
- Menambahkan 41 kasus test unit (16 parse valid, 20 invalid/overflow, 5 format+roundtrip) termasuk PHP_INT_MIN/MAX dan 1 Rupiah dari suffix jt. Tidak ada caller aplikasi/UI untuk utilitas ini; form berikutnya harus mengubah exception menjadi validasi terlokalisasi.
- **Verifikasi:** targeted Money test 41 kasus/46 assertion lulus; regression akhir 59 test/112 assertion, `composer lint`, `composer analyse`, dan `git diff --check` lulus. Larastan awal menolak null-coalescing pada capture regex yang selalu ada; akses capture diperbaiki tanpa suppression.
- **Batas:** CORE-03 tetap parsial: PeriodResolver dengan histori/transisi, tenant support, x-money/x-input-money/x-date belum diimplementasikan. Belum ada perubahan UI/browser sehingga workflow tiga skill UI tidak dipicu; tidak ada dependency, DB lokal, migration/seed, admin, commit/push, atau CI task baru.
- **Review dokumentasi:** kontrak Money pada Masterplan, plan/log, dan status instruksi diselaraskan. Dampak pada tiga spesifikasi modul, ADR, runbook, UAT, dan skills ditinjau: tidak berubah karena checkpoint ini tidak mengubah arsitektur, skema, routes, stack, atau perilaku aplikasi yang dirilis.

### LOG-016 - CORE-03 checkpoint periode keuangan

- Melanjutkan CORE-03 pada branch lokal yang sama tanpa menimpa checkpoint Money yang belum di-commit. Pemilihan teks CORE-02 di editor bukan arahan mengganti task; belum memasang server produksi.
- Menambahkan `Period` immutable dan mesin `PeriodResolver` berbasis history efektif/timezone. Menghitung batas periode lokal dan end-of-day inklusif, current/fromKey/forDate/previous/next; tanggal sebelum history memakai setting pertama. Perubahan mempertahankan batas historis dan menambahkan periode transisi hingga kemunculan hari baru berikutnya.
- Validasi eksplisit: history wajib, start day 1-28, tanggal kalender ketat YYYY-MM-DD, urutan unik menaik, perubahan pada batas lama, dan fromKey hanya untuk awal periode valid. Tidak menyortir/menebak history rusak secara diam-diam.
- Menambahkan 14 kasus test periode: S=1/25/28, tahun kabisat/akhir tahun, timezone, frozen clock, transisi pendek dan panjang relatif periode sebelumnya, multiple changes, invalid keys/history, serta kontinuitas harian 400 hari (coverage/adjacency/roundtrip).
- **Lulus:** regression 73 test/1362 assertion, `composer lint`, dan `composer analyse`. Percobaan awal test memunculkan deprecation PHP 8.5 karena lookup array dengan key null saat history masih kosong; guard empty history memperbaiki akar masalah, regression akhir tanpa deprecation. `git diff --check` juga diperiksa.
- **Batas penting:** factory `PeriodResolver::for($household)`/relasi DB dan tenant support memerlukan model household CORE-04 yang belum ada. Tidak membuat model sementara, schema palsu, atau migration hanya untuk memenuhi signature. CORE-03 tetap parsial; komponen x-money/x-input-money/x-date belum dibuat. Kebutuhan dependensi integrasi ini dicatat di plan, bukan dianggap selesai.
- **Dokumentasi:** Masterplan menjelaskan API perhitungan yang tersedia versus adapter household yang masih planned; plan/log dan instruksi diselaraskan. Spesifikasi modul, ADR, kedua runbook, UAT, dan skills tetap sesuai karena kontrak aplikasi, schema, routes, stack, dan produksi tidak berubah.
- Tidak ada UI/browser impact, dependency baru, DB lokal/migration/seed, akun admin, commit/push, merge, atau CI baru. Workflow UI tidak dipicu untuk utilitas backend tanpa caller UI.

### LOG-017 - CORE-03 komponen tampilan uang dan tanggal

- Menambahkan Blade `x-money` yang memakai formatter integer bersama, digit tabular, pewarnaan negatif opsional light/dark, dan atribut pemanggil. Tidak mengubah UI login atau menambah demo route.
- Menambahkan `x-date` dengan validasi tanggal kalender ketat dan markup semantik `<time>`. String tanggal tidak digeser timezone; objek DateTimeInterface diperlakukan sebagai instant dan dikonversi tanpa mutasi. Locale Indonesia/Inggris mengikuti aplikasi. Default timezone Asia/Jakarta bukan resolusi household otomatis; pemanggil harus memasok timezone household setelah integrasi tersedia.
- Menambahkan 9 test komponen untuk Rupiah/color/atribut, tanggal kalender, instant lintas hari/timezone, English locale, dan penolakan lima input tanggal buruk. **Lulus:** 82 test/1384 assertion regression, Pint, Larastan, build Vite; `git diff --check` diperiksa setelah sinkronisasi docs.
- **Workflow UI:** premium-frontend-ui dimuat untuk implementasi sesuai batas desain Family Hub; web-design-reviewer dan webapp-testing dibaca langsung karena belum tersedia di discovery. Menggunakan output Blade aktual sebagai fixture browser sementara dengan stylesheet build lokal. Snapshot/screenshot visual diperiksa; matriks DOM 360px/1280px light/dark menunjukkan teks/tanggal tepat, warna negatif sesuai, dan tanpa overflow. Komponen statis tidak memiliki kontrol pointer/keyboard atau navigasi yang perlu diuji. Ini bukan uji integrasi halaman domain, Lighthouse, atau audit aksesibilitas menyeluruh.
- Fixture tab internal pertama ditutup; tab fixture tool berikutnya sudah tidak ditemukan saat cleanup, tanpa menutup halaman login milik reviewer. Tidak ada file fixture, server baru, atau dependency yang ditinggalkan.
- **Review dokumentasi:** Masterplan, plan/log, dan checkpoint instruksi diselaraskan; modul, ADR, runbook, UAT, dan skills tetap sesuai karena tidak ada schema/routes/stack/kontrak lintas modul berubah. Dokumentasi pemakaian membedakan API tersedia dan planned.
- **Batas/lanjutan:** `x-input-money` belum dibuat/diuji; perlu sinkronisasi integer Livewire tanpa kehilangan presisi. Household-backed PeriodResolver dan tenant support belum ada. CORE-03 masih parsial. Tidak ada migration/seed database aplikasi lokal, akun admin, commit/push, merge, atau CI baru.

### LOG-018 - CORE-03 input uang integer aman

- Reviewer memilih batas integer browser/Livewire ±9.007.199.254.740.991, bukan pengangkutan seluruh 64-bit sebagai string. Money backend tetap mendukung 64-bit; batas input bukan pembatasan penyimpanan seluruh domain.
- Menambahkan x-input-money dengan label wajib/id, binding Alpine modelable, tampilan ribuan, dukungan Rp/rb/jt, empty/null, caret, aria/error terlokalisasi dan native validity. Validasi salah/range mengirim null sehingga tidak mempertahankan nominal lama. Input teks menjaga akses minus/koma/suffix. Tidak membuat halaman domain/demo permanen.
- Parser JS memakai BigInt untuk pemeriksaan presisi sebelum konversi Number. Rule SafeRupiah menolak tipe noninteger dan range di luar batas pada server; form pemanggil wajib menggunakan required/nullable serta rule ini dan merender error server.
- Menambahkan 12 test PHP (23 assertion) untuk markup/model/range/validasi Livewire dan 4 test JS. npm test memakai Node built-in; workflow CI menambah npm test tanpa dependency/lockfile upgrade. Bug ketik melewati pemisah ribuan diperbaiki dengan membedakan edit digit/deletion dari paste; test regresinya tersedia.
- **Lulus lokal:** 94 test PHP/1407 assertion, 4 test JS, Pint, Larastan, Vite build. CORE-03 belum di-commit/push, jadi belum ada klaim hasil CI untuk perubahan ini.
- **Workflow UI:** premium skill dimuat, dua skill review/testing repository dibaca langsung. Router fixture di session workspace menjalankan aplikasi nyata + komponen Livewire pada loopback 8011, tanpa DB/migration. Memverifikasi model awal, suffix, submit/reset, maksimum integer tepat, nilai negatif, penolakan unsafe dan null, serta native invalid/aria. Matriks 360px/1280px light/dark tanpa overflow dan input 44px; screenshot/snapshot digunakan untuk inspeksi, bukan pengukuran Lighthouse.
- **Batas bukti browser:** klik Playwright timeout, keyboard sempat bekerja tetapi percobaan berikutnya tidak konsisten. Submit/reset/presisi juga diuji via panggilan Livewire langsung; ketik/backspace via event InputEvent programatik + Alpine tick. Ini membuktikan integrasi data, bukan pointer/keyboard native penuh; gap ini tetap dilaporkan. Tidak ada success-shaped fallback atau klaim seluruh interaksi normal lulus.
- **Cleanup:** fixture tab tidak ditemukan lagi ketika ditutup; server sementara dihentikan dan router scratch spesifik dihapus. Server reviewer port 8000 tetap sehat (HTTP 200 /up), halaman login reviewer tidak ditutup.
- **Review struktural:** kontrak input dan command test/CI pada Masterplan, plan/log, serta instruksi diselaraskan. Ketiga spesifikasi modul, ADR stack, runbook deploy/backup, UAT, dan skills tetap berlaku; tidak ada perubahan schema, routes produksi, arsitektur, paket, atau rilis. CORE-03 tetap parsial menunggu integrasi tenant/household dan penutupan gap UI; belum beralih CORE-04.

### LOG-019 - Urutan CORE-03A/04/03B dan fondasi household

- Reviewer secara eksplisit menyetujui pemecahan CORE-03A → CORE-04 → CORE-03B untuk menghilangkan dependensi melingkar model/tenant. Gap interaksi browser tetap tercatat; tidak menandai semua CORE-03 selesai.
- Menambahkan model Household/HouseholdMember/HouseholdFinancePeriodSetting dengan ULID, relasi eksplisit, casts, soft delete household, enum MemberRole/color, dan migrasi household/history/member. Constraint DB membatasi slot 1/2, unique household/slot, unique user, role owner/partner, FK, serta tanggal efektif history unik per household.
- User dan sessions diubah dari integer placeholder menjadi ULID, dengan kolom preferensi/default sesuai Masterplan. Field admin tidak mass-assignable; default false. Tidak membuat user admin atau mengaktifkan provisioning. Migrasi awal User disesuaikan karena belum ada migration DB aplikasi lokal/produksi; bukan strategi upgrade untuk database integer berisi data.
- Tabel membership memakai CREATE TABLE dengan inline CHECK karena SQLite tidak mendukung penambahan CHECK via ALTER; SQL menargetkan SQLite/MariaDB. Tidak mengklaim kompatibilitas perubahan baru sudah terbukti di MariaDB: remote CI terakhir hanya mencakup CORE-01.
- **Lulus lokal:** 101 test PHP/1421 assertion, 4 test JS, Pint, Larastan. Test meliputi ULID/default admin false, relasi/member enum/history, unique user/slot, maksimal dua slot, duplikasi effective date, soft delete, serta regresi login yang tetap lulus. Test berjalan pada SQLite in-memory saja; tidak menjalankan migrate/seed DB aplikasi.
- **Batas:** CORE-04 parsial; policy, action membership/transaksi/slot allocation, bootstrap history, rollback runtime, serta konkurensi SQLite/MariaDB masih perlu dibuktikan. Tenant context/scope dan PeriodResolver household adapter 03B belum ada. Model/relasi bukan otorisasi; tidak membuka route UI/CRUD baru.
- **Dokumentasi struktural:** meninjau dampak schema/urutan pada Masterplan, ketiga spesifikasi modul, ADR, plan/log, deploy/backup runbook, UAT, serta instruksi/skills. Masterplan/plan/log/instruksi dan catatan deploy/UAT diselaraskan; modul, ADR stack, backup dan skills tidak berubah karena kontrak lintas modul, stack, dan operasi backup tetap sama. Tidak ada commit/push, merge, CI baru, atau perubahan browser; workflow UI tidak dipicu pada skema/model tanpa caller halaman.

### LOG-020 - CORE-04 policy household

- Menambahkan HouseholdPolicy dan registrasi eksplisit Gate untuk model Core. View/update tersedia bagi kedua peran; invite/cancel/removePartner/transfer/delete khusus owner; leave khusus partner. Tidak ada before-hook admin yang melewati isolasi household.
- Policy memeriksa membership melalui query terbaru, bukan relasi eager-loaded yang bisa stale. Query relasi household menolak household soft-deleted sekalipun pemanggil memegang objek lama. Model belum persisted ditolak. Ini otorisasi tingkat household, bukan validasi target partner/token/kapasitas atau implementasi mutasi.
- Menambahkan 19 test policy/46 assertion melalui Gate: matriks delapan ability, guest/admin/outsider/lintas-household, pencabutan membership/perubahan role, objek household stale setelah soft-delete, dan admin yang menjadi partner tanpa eskalasi owner.
- **Lulus:** targeted policy dan regression 120 test/1467 assertion, Pint, Larastan. Analisis awal mengira role model sebagai string; dokumentasi properti enum pada model diselaraskan dengan cast sebenarnya, tanpa cast paksa/suppression. Whitespace diff diperiksa.
- **Review docs:** matriks Masterplan tetap dipertahankan; catatan implementasi, plan/log, dan instruksi diperbarui. Spesifikasi modul, ADR, runbook/UAT, dan skills tetap sesuai: tidak ada schema, route/UI, stack, atau kontrak domain baru. Policy belum terhubung ke route/action produksi karena surface itu belum diimplementasikan.
- **Lanjutan/batas:** CORE-04 masih parsial: action/transaksi alokasi slot, bootstrap household/history, konkurensi SQLite/MariaDB, dan uji rollback belum selesai. 03B tenant/resolver dan gap pointer/keyboard tetap terbuka. Test hanya DB terisolasi, tanpa migrate/seed aplikasi lokal, admin persisten, commit/push, merge atau CI baru. UI workflow tidak dipicu untuk policy backend tanpa caller halaman.

### LOG-021 - CORE-04 action membership dan audit atomik

- Menambahkan RemoveHouseholdMember (partner keluar/owner mengeluarkan partner) dan TransferHouseholdOwnership. Action mengunci household aktif, memanggil Gate di dalam transaksi dan membaca target membership terbaru; outsider/admin tidak mendapat bypass. Target lintas-household, owner keluar, partner mengeluarkan owner, transfer ke diri sendiri/nonmember, serta household terhapus ditolak eksplisit.
- Transfer hanya menukar role: slot, warna kalender dan joined_at dipertahankan. Removal menghapus membership, bukan akun atau data domain. Tidak menambahkan route, undangan, join/provisioning bebas, atau UI; ini fondasi CORE-04 untuk CORE-14 nanti, bukan implementasi pengaturan lengkap.
- Mengintegrasikan dependency Spatie Activitylog yang sudah disetujui: migration schema v5 dengan PK/morph ULID, model Activity HasUlids dan konfigurasi model/buffer. Default paket lain tetap diwarisi; buffering dipaksa nonaktif agar audit dan mutasi satu transaksi. Properties hanya ID target/pemilik, subject household dan causer actor. Belum ada pembaca audit publik/tenant scope; caller CORE-14 nanti wajib mengotorisasi/membatasi subject household.
- **Lulus:** 19 test action/79 assertion baru; total 139 test PHP/1546 assertion, Pint/Larastan. Test mencakup role matrix di boundary action, identitas audit, penggantian role tanpa perubahan warna/slot, role stale, target lintas-household dan rollback kedua action saat audit gagal. `git diff --check` diperiksa. Uji terpisah boot Laravel dengan koneksi SQLite `:memory:` menjalankan migrate -> rollback -> migrate dan memverifikasi kelima tabel users/household/member/history/activity hilang/lahir sesuai tahap; DB aplikasi `database.sqlite` tetap tidak ada.
- **Review struktural:** inventaris seluruh docs ditinjau untuk kontrak membership/audit, termasuk Masterplan, tiga modul, ADR-011, deploy/backup, UAT, plan/log dan instruksi/skill docs-sync/UI. Masterplan/plan/log/instruksi/deploy/UAT diperbarui. Spesifikasi modul, ADR, backup dan skills tidak diubah karena tidak ada perubahan API lintas modul, dependency, operasi backup atau workflow UI. Retensi audit mengikuti default paket yang belum diaktifkan sebagai scheduler; bukan keputusan retensi backup.
- **Batas/berikutnya:** belum ada uji proses paralel SQLite/MariaDB atau CI baru; lockForUpdate bukan klaim jaminan konkurensi SQLite. Admission/slot lewat CORE-05 dan bootstrap household/history masih tertunda; CORE-04/03B tetap parsial. Tidak migrate/seed DB aplikasi, membuat akun persisten, commit/push/merge, atau mengubah UI. JS/build/browser tidak diulang karena tidak ada perubahan browser/caller halaman.

### LOG-022 - Persiapan commit/push checkpoint support dan household

- Reviewer secara eksplisit meminta commit/push. Target branch kerja `core-03-support` dari `42bda38`, remote repository reviewer; tidak merge atau push ke main. Kode CORE-03A dan CORE-04 dipisahkan menjadi commit tersendiri; dokumentasi lintas-checkpoint dipublikasikan dalam batch yang sama.
- Preflight ulang: 139 test PHP/1546 assertion, 4 test JS, build Vite, Pint/Larastan dan `git diff --check` lulus. Inventaris tracked/untracked ditinjau; scan marker private key/token/APP_KEY/database password tidak menemukan kecocokan (bukan jaminan deteksi semua secret). `.env`, SQLite aplikasi, vendor/node_modules, dan public/build tetap ignore; tidak ada DB aplikasi.
- Ini catatan persiapan, bukan bukti push atau CI baru. SHA hasil commit, konfirmasi remote dan CI dicatat setelah tersedia. CORE-03/04 tetap parsial; concurrency SQLite/MariaDB, bootstrap/invitation, tenant/resolver dan gap browser tetap mengikuti LOG-021/sebelumnya. Tidak menjalankan migration/seed aplikasi atau membuat akun.
- **Hasil setelah publikasi:** commit `bcf6e4d` (CORE-03A), `bc3ea37` (CORE-04), dan `a08a5ae` (docs checkpoint) berhasil di-push ke `origin/core-03-support`; `git ls-remote` mengonfirmasi tip `a08a5ae7a2172576e381ba9dc889d4640adc0d26`. Remote main tetap `750a946ec4b0967a590f50ffbe81deb8938f1e16`, tidak ada PR/merge. Working tree bersih setelah push. [CI run 40286560448](https://github.com/shadri-zulkurniadi/Net/actions/runs/40286560448) untuk tip itu terpantau `in_progress`, bukan bukti kelulusan. Hasil push dicatat dalam follow-up docs-only `[skip ci]` agar tidak menggandakan CI kode identik.
- Dokumen plan/log diperbarui untuk otorisasi publikasi; tidak ada perubahan struktur/fitur pada batch Git ini. Dokumen lain tetap mengikuti checkpoint implementasi LOG-021.
### LOG-023 - Merge fondasi aman dan fokus SQLite

- Reviewer meminta menunda MariaDB, menggunakan SQLite dahulu dan merge branch yang aman. Berdasarkan rekomendasi sebelumnya, `core-01-foundation` (`42bda38`) di-fast-forward ke main; `core-03-support` tidak di-merge. LOG-015 sampai LOG-022 tetap berada pada branch support yang belum digabung; ID LOG-023 dipakai agar tidak berbenturan saat integrasi berikutnya.
- CI main diubah menjadi satu job SQLite in-memory, tanpa service/matrix MariaDB dan tanpa pdo_mysql pada setup CI. Build, Pint, Larastan dan test tetap wajib. Ini penundaan target kompatibilitas, bukan perbaikan kegagalan MariaDB pada support run `40286560448` (SQLite success, MariaDB failure).
- Dokumen Masterplan, ADR, plan/log, instruksi dan runbook/UAT diselaraskan dengan keputusan sementara. Spesifikasi ketiga modul dan backup tidak memerlukan perubahan kontrak; schema/arsitektur runtime tetap sama. Branch support harus mengintegrasikan main dan lulus CI baru sebelum evaluasi merge.
- Merge lokal berhasil fast-forward; validasi/push dan CI terbaru dicatat berdasarkan hasil aktual berikutnya. Tidak menjalankan migration/seed DB aplikasi, membuat akun atau menyatakan produksi siap.
- **Validasi lokal setelah merge:** 18 test fondasi/66 assertion, Pint, Larastan dan diff whitespace lulus. Tidak mengklaim 139 test branch support tersedia pada main. Build/browser tidak diulang karena tidak ada perubahan UI/aset. Publikasi main dan run CI diperiksa setelah commit keputusan SQLite.

### LOG-024 - Integrasi main dan tenant support CORE-03B

- `main` (`a729dfe`, keputusan SQLite-only) di-merge ke `core-03-support` secara lokal; konflik `docs/logbook.md` dan `docs/development-plan.md` diselesaikan dengan mempertahankan seluruh histori LOG-015 sampai LOG-023 dan keputusan terbaru. CI branch menjadi satu job SQLite plus `npm test`. Merge commit lokal `1cd85ae`; tidak ada push atau merge ke main.
- CORE-03B: `HouseholdContext` singleton (`use`/`forget`/`current`, gagal tertutup), helper global `currentHousehold()`, trait `BelongsToHousehold` dengan `HouseholdScope` (global scope `household_id`) dan auto-fill/validasi `household_id` saat creating. Trait diterapkan pada `HouseholdFinancePeriodSetting`; `Household` mendapat default atribut timezone `Asia/Jakarta`, start day 1, dan integrasi aktif agar model baru sesuai default skema. `PeriodResolver::for(Household)` membaca histori dan timezone household; global scope dilewati secara eksplisit karena household parameter adalah batas tenant, sehingga aman dipakai job tanpa konteks ambient.
- Test baru `HouseholdTenantTest` (5 kasus/16 assertion): auto-fill dan isolasi baca dua household, gagal tertutup tanpa konteks (query, helper, create), penolakan tulis untuk household lain, resolver dari histori DB tanpa konteks ambient, timezone household, dan histori kosong. `HouseholdSchemaTest` menetapkan konteks household pada fixture.
- **Verifikasi:** setelah merge 139 test PHP/1546 assertion; setelah CORE-03B 144 test PHP/1562 assertion, 4 test JS (`npm test`), `composer lint`, `composer analyse`, `npm run build`, dan `git diff --check` lulus pada SQLite in-memory. Tidak ada UI/browser, migration/seed DB aplikasi, akun, dependency baru, push, atau CI baru.
- **Batas:** trait belum diterapkan pada `HouseholdMember` (akses via policy dan eksplisit per household) atau `Activity` (tabel tanpa kolom `household_id`), dan belum ada model modul. Middleware/command/job yang menginisialisasi konteks belum dibangun. Bootstrap household + histori periode, admission lewat undangan CORE-05, dan bukti konkurensi tetap pekerjaan CORE-04. Publikasi/push dan evaluasi merge ke main menunggu reviewer.

### LOG-025 - MariaDB dikembalikan ke CI

- Reviewer menyatakan CORE-03 selesai dan mengizinkan MariaDB masuk kembali ke CI ("since 03 is completed you can include mariadb ci again"). Keputusan ini menggantikan penundaan LOG-023 untuk gate CI; status "selesai" adalah pernyataan reviewer, bukan acceptance tertulis atas setiap AC CORE-03.
- `.github/workflows/ci.yml` dikembalikan ke matriks `database: [sqlite, mariadb]` dengan service `mariadb:10.11`, `pdo_mysql`, dan step `Test MariaDB` yang menetapkan `DB_CONNECTION=mysql`, host/port, database `family_hub_test`, serta kredensial CI-only. Step SQLite tetap memakai `phpunit.xml` in-memory. Workflow kini identik dengan versi yang dipakai pada run gagal `40286560448`.
- **Belum terbukti:** tidak ada Docker/MariaDB lokal, sehingga suite tidak dijalankan pada MariaDB di mesin ini dan tidak ada klaim kompatibilitas untuk perubahan CORE-03/04. Penyebab kegagalan MariaDB pada `40286560448` juga belum diketahui; log run lama tidak dapat diakses (GitHub CLI tidak terpasang, API run mengembalikan 404).
- **Verifikasi lokal (perubahan workflow + dokumen):** 144 test PHP/1562 assertion, 4 test JS, `composer lint`, `composer analyse`, `npm run build`, `composer validate`, dan `git diff --check` lulus pada SQLite in-memory. Tidak ada migration/seed DB aplikasi, akun, dependency baru, atau perubahan kode domain. Push dan hasil run CI baru menunggu tindakan reviewer.
- **Dokumen yang diselaraskan:** logbook, `docs/development-plan.md`, `docs/00-MASTERPLAN.md` (catatan checkpoint CI), `docs/adr/ADR-011-current-stack.md`, `.github/copilot-instructions.md`, `docs/runbook/deploy.md`, `docs/uat-go-live.md`, dan `AGENTS.md` root. Spesifikasi modul Finance/Organizer/Asset, runbook backup-restore, dan checklist lain tidak berubah karena kontrak domain, topologi deploy, dan skema tidak berubah.

### LOG-026 - Publikasi support dan merge ke main

- Reviewer memberi izin eksplisit untuk push lalu merge ke main. `core-03-support` di-push ke `origin` (`2137b2b..bcbbe86`), lalu `main` di-fast-forward `a729dfe..bcbbe86` tanpa merge commit tambahan. `git ls-remote` mengonfirmasi `main` dan `core-03-support` sama-sama berada di `bcbbe86f82d90b6be2964d8f92a9079d50a89c1b`. Tidak ada tag atau rilis.
- Push memicu workflow pada kedua branch. Status run **belum diketahui**: repository privat, GitHub CLI tidak terpasang, dan REST API mengembalikan 404 tanpa autentikasi. Karena itu hasil MariaDB (dan SQLite remote untuk tip ini) tidak diklaim lulus; pemeriksaan hanya tersedia dari output GitHub Actions milik reviewer.
- **Verifikasi lokal sebelum push/merge:** 144 test PHP/1562 assertion, 4 test JS, Pint, Larastan, Vite build, `composer validate`, dan `git diff --check` lulus pada SQLite in-memory. Tidak menjalankan migration/seed DB aplikasi, membuat akun, atau mengubah dependency.
- **Batas:** CORE-04 tetap parsial (bootstrap household/history, middleware/job context, admission CORE-05, konkurensi). Main kini memuat checkpoint CORE-03A/03B/04 beserta skemanya; ini bukan acceptance UAT/produksi, bukan bukti kompatibilitas MariaDB, dan bukan izin deploy. Dokumen yang diselaraskan pada batch ini: logbook, `docs/development-plan.md`, `AGENTS.md` root; dokumen lain tidak berubah karena tidak ada perubahan perilaku/kontrak baru.

### LOG-027 - Perbaikan nama index untuk MariaDB

- Investigasi CI: `Schema::unique(['household_id', 'effective_on'])` pada `household_finance_period_settings` menghasilkan nama index `household_finance_period_settings_household_id_effective_on_unique` (66 karakter). Laravel tidak memotong nama tersebut (terverifikasi di `Blueprint::createIndexName`), sedangkan MariaDB/MySQL membatasi identifier 64 karakter (error 1059). SQLite tidak membatasi, sehingga run SQLite lulus dan MariaDB gagal. Ini kandidat kuat penyebab kegagalan `40286560448`, tetapi belum dikonfirmasi karena log run tersebut tidak dapat diakses.
- Perbaikan: index diberi nama eksplisit `household_period_unique`. Migrasi asli diedit karena belum ada database aplikasi yang dimigrasikan, belum ada rilis, dan jalur upgrade data belum pernah dibuat; aturan "migrasi baru setelah rilis" belum berlaku. `php artisan migrate --pretend` menunjukkan `create unique index "household_period_unique"`.
- Verifikasi lokal: 144 test PHP/1562 assertions, 4 test JS, Pint, Larastan, dan `git diff --check` lulus pada SQLite in-memory. Bukti MariaDB tetap menunggu run CI baru; jangan menyatakan gap MariaDB tertutup sebelum itu.
- Instruksi proyek diperbarui agar index komposit diberi nama eksplisit <= 64 karakter. Dokumen lain (masterplan, ADR, runbook, UAT, spesifikasi modul) tidak berubah karena tidak ada perubahan kontrak, skema logis, atau topologi.

### LOG-028 - Perbaikan collation foreign key MariaDB

- Bukti CI dari reviewer untuk `bcbbe86`: job MariaDB gagal saat migrasi `2026_10_26_000001_core_create_households.php` baris 25 dengan `SQLSTATE[HY000]: General error: 1005` / `errno: 150 "Foreign key constraint is incorrectly formed"`; hasil akhir 62 failed, 82 passed. SQLite tetap lulus, sehingga kegagalan spesifik MariaDB.
- Penyebab: `household_members` dibuat dengan DDL mentah tanpa opsi tabel sehingga mewarisi collation default server (MariaDB 10.11: `utf8mb4_general_ci`), sedangkan `households`/`users` dibuat Laravel dengan `default character set utf8mb4 collate 'utf8mb4_unicode_ci'` sesuai `config/database.php`. Foreign key pada kolom `CHAR(26)` mensyaratkan charset/collation identik, sehingga MariaDB menolak constraint. SQLite tidak menegakkan collation, jadi lolos. Ini penyebab aktual kegagalan run lama; masalah nama index LOG-027 tidak tercapai karena migrasi berhenti lebih dulu.
- Perbaikan: DDL mentah kini menambahkan `ENGINE=InnoDB DEFAULT CHARSET=... COLLATE=...` dari konfigurasi koneksi hanya pada driver `mysql`; SQLite tidak berubah. Tidak mengubah tipe kolom, constraint, atau perilaku aplikasi.
- Verifikasi lokal: `php artisan migrate --pretend --database=sqlite` menunjukkan DDL tanpa suffix pada SQLite dan index `household_period_unique`; 144 test PHP/1562 assertions, Pint, Larastan, dan `git diff --check` lulus. Bukti MariaDB menunggu run CI berikutnya; jangan menutup gap kompatibilitas sebelum hasilnya ada.
- Dokumen yang diselaraskan: logbook dan `docs/development-plan.md`. Masterplan, ADR, runbook, UAT, dan instruksi tidak berubah karena kontrak/skema logis sama.

### LOG-029 - CI SQLite dan MariaDB lulus

- Reviewer melaporkan kedua job CI lulus untuk `6d6f7bc` setelah perbaikan collation. Ini menutup gate kompatibilitas MariaDB untuk skema CORE-03/04 dan membuktikan perbaikan `LOG-027`/`LOG-028` pada run nyata. Tautan run tidak dicatat karena tidak diberikan; bukti berasal dari laporan reviewer.
- Cakupan bukti: migrasi seluruh tabel, 144 test PHP/1562 assertions, 4 test JS, Pint, Larastan, dan build pada matriks SQLite/MariaDB. Tidak ada deployment, migration DB aplikasi, atau UAT.
- CORE-04 masih parsial pada sisi produk: bootstrap household + histori awal, inisialisasi konteks per request/job, admission lewat undangan CORE-05, dan uji konkurensi penerimaan slot belum selesai. Dokumen lain tidak berubah.

### LOG-030 - Bootstrap household dan konteks tenant per request

- `CreateHousehold` (`app/Core/Actions/CreateHousehold.php`) membuat household, membership owner slot 1 dengan warna peran, dan satu `HouseholdFinancePeriodSetting` awal bertanggal hari ini pada timezone household, semuanya dalam satu transaksi, lalu menulis audit `core.household.created`. Validasi: nama wajib setelah trim dan maksimal 100 karakter, start day 1-28, timezone harus ada di `timezone_identifiers_list()`, dan user yang sudah punya membership ditolak (`household_members.user_id` unik). Action tidak bergantung pada konteks ambient; ia memasang `HouseholdContext` sementara untuk penulisan milik tenant lalu memulihkan keadaan sebelumnya agar trait `BelongsToHousehold` tetap gagal tertutup.
- `UseHouseholdContext` (`app/Http/Middleware/UseHouseholdContext.php`) didaftarkan pada grup `web` di `bootstrap/app.php`: lupa konteks di awal, pasang household dari membership user terautentikasi (household terhapus lunak otomatis tidak terpilih karena scope soft delete), dan lupa konteks lagi di blok `finally`. Guest dan user tanpa household berjalan tanpa konteks. `HouseholdContext::currentOrNull()` ditambahkan untuk keperluan pemulihan itu.
- Test baru: `HouseholdBootstrapTest` (8 kasus, termasuk 5 dataset input tidak valid dan bukti tidak ada penulisan saat validasi gagal) dan `HouseholdContextMiddlewareTest` (4 kasus: member aktif, user tanpa household, guest, household terhapus lunak); 12 test/43 assertion. Regression penuh **156 test PHP/1605 assertion**, 4 test JS, `composer lint` (termasuk perbaikan `braces_position` pada middleware), `composer analyse`, dan `npm run build` lulus pada SQLite in-memory.
- **Batas:** tidak ada base class/middleware job atau command; job nanti wajib membawa household ID eksplisit dan memasang konteks sendiri. Histori periode hanya berisi baris awal (belum ada backfill periode lampau). Admission partner lewat undangan CORE-05 dan bukti konkurensi penerimaan slot belum ada, sehingga CORE-04 tetap parsial. Bukti MariaDB bergantung pada CI berikutnya; tidak ada Docker/MariaDB lokal, deployment, migration/seed DB aplikasi, akun, atau dependency baru.
- Dokumen yang diselaraskan: logbook, `docs/development-plan.md`, `docs/00-MASTERPLAN.md` (status isolasi §2.4), `.github/copilot-instructions.md`, dan `AGENTS.md` root. ADR, runbook, UAT, checklist, dan spesifikasi modul tidak berubah karena keputusan stack, topologi deploy, skema logis, dan kontrak modul tidak berubah.
- **Publikasi:** atas permintaan reviewer, `731fb45` di-push ke `origin/main` (`49f218c..731fb45`) dan `core-03-support` disamakan ke SHA yang sama; `git ls-remote` mengonfirmasi keduanya identik. `core-01-foundation` (`42bda38`) sudah menjadi ancestor `main`, jadi tidak ada branch yang perlu di-merge. Hasil CI untuk `731fb45` belum dapat dipantau dari mesin ini (repo privat, tanpa GitHub CLI); jangan mengklaim lulus sebelum reviewer melaporkan. Ini bukan izin deploy atau UAT.

### LOG-031 - CI lulus untuk bootstrap household dan konteks request

- Reviewer melaporkan seluruh job CI hijau untuk `731fb45` (CORE-04 bootstrap + middleware konteks). Bukti mencakup matriks SQLite/MariaDB, migrasi seluruh tabel, 156 test PHP/1605 assertion, test JS, Pint, Larastan, dan build. Tautan run tidak dicatat karena tidak diberikan.
- Ini menutup pertanyaan portabilitas `whereHas` membership, insert `HouseholdFinancePeriodSetting` di dalam transaksi, dan DDL mentah `household_members` pada MariaDB. `d4a6986` hanya menambah entri logbook dengan `[skip ci]`, sehingga tip dokumen tidak memiliki run sendiri dan tidak perlu bukti terpisah.
- Cakupan tetap: tidak ada deployment, migration/seed DB aplikasi, akun nyata, atau UAT. CORE-04 masih parsial (konteks job/command, backfill histori periode, admission CORE-05, konkurensi slot); pekerjaan berikutnya adalah CORE-05 registrasi invite-only sesuai urutan rencana.
- Dokumen yang diselaraskan: logbook dan `docs/development-plan.md`. Masterplan, ADR, runbook, UAT, instruksi, dan spesifikasi modul tidak berubah karena tidak ada keputusan, kontrak, atau topologi baru.

### LOG-032 - Backend undangan partner (slice pertama CORE-05)

- Reviewer memilih slice backend lebih dulu: penyimpanan token, kedaluwarsa, sekali pakai, revoke, dan admission, tanpa email/route/UI. CORE-05 penuh (email undangan, registrasi tanpa token ditolak, invite household baru oleh admin) belum dikerjakan.
- Migrasi `2026_10_26_000003_core_create_household_invitations.php`: `id` ULID, `household_id` FK, `token_hash` CHAR(64) unik, `invited_by` FK, `expires_at`, `accepted_at`/`accepted_by` nullable, timestamps, dan index `household_invitation_pending_idx` (31 karakter, di bawah batas 64). Token 32 byte acak diubah ke 64 karakter heksadesimal; hanya SHA-256-nya yang disimpan.
- `InviteHouseholdMember` (otorisasi `invitePartner`, menolak household yang sudah dua anggota, masa berlaku 7 hari, audit `core.household.partner_invited`, mengembalikan `IssuedInvitation` berisi token plaintext), `AcceptInvitation` (cari berdasarkan hash dengan lock, menolak token tidak dikenal/kedaluwarsa/sudah dipakai/household terhapus/user sudah punya household/household penuh, mengisi slot bebas secara atomik, menandai `accepted_at`, audit `core.household.partner_joined`), dan `RevokeHouseholdInvitation` (otorisasi `cancelInvitation`, hanya undangan pending milik household tersebut, hapus baris, audit `core.household.invitation_revoked`). `HouseholdInvitation` sengaja tidak memakai trait tenant karena penerimaan terjadi sebelum user punya household.
- **Konflik spesifikasi yang dilaporkan, bukan dipilih diam-diam:** §5.1 masterplan mendefinisikan `household_invitations` dengan `household_id` nullable, `email`, `type` (`new_household`/`partner`), dan `revoked_at`. Implementasi slice ini memakai `household_id` wajib, tanpa `email`/`type`, dan revoke menghapus baris. Penyimpangan ini sesuai lingkup "backend partner saja" dan belum aman untuk alur email/admin; keputusan reviewer diperlukan sebelum slice berikutnya (menambah kolom vs mengubah §5.1).
- **Verifikasi lokal:** `HouseholdInvitationTest` 15 kasus/54 assertion (hash tidak menyimpan token, kedaluwarsa tepat 7 hari termasuk batas 1 detik, pemakaian kedua ditolak, token asing/dirusak, household penuh, user sudah punya household, household terhapus, otorisasi partner/outsider, revoke asing/terpakai, dan unique `token_hash` di level DB). Total 171 test PHP/1659 assertion, `composer lint`, `composer analyse`, dan `php artisan migrate --pretend --database=sqlite` lulus. Belum ada run MariaDB lokal; bukti MariaDB menunggu CI setelah push.
- **Batas/berikutnya:** belum ada route, Livewire/Blade, email, rate limit, atau invite household baru oleh admin (CORE-15). Penguncian `accepted_at` + unique `(household_id, slot)` adalah batas logis pemakaian ulang; belum ada test proses paralel nyata di SQLite maupun MariaDB. Karena tidak ada kolom `email`, pemegang tautan adalah pihak yang diterima selama belum jadi anggota household lain.
- Dokumen yang diselaraskan: logbook dan `docs/development-plan.md`. Masterplan, ADR, runbook, UAT, instruksi, dan spesifikasi modul tidak diubah karena menunggu keputusan atas konflik skema di atas.
- **Disusul LOG-034:** konflik skema di atas sudah diputuskan reviewer (dokumen jadi acuan), kode diselaraskan ke §5.1, dan masterplan diberi status implementasi. Batas lain pada entri ini tidak berubah.

### LOG-034 - Skema undangan diselaraskan ke masterplan §5.1

- Keputusan reviewer atas P-01: dokumentasi adalah acuan; bila kode menyimpang, kode yang diperbaiki, kecuali dokumennya sendiri tidak masuk akal. Opsi pertama (tambah kolom) dipilih.
- Migrasi `2026_10_26_000003_core_create_household_invitations.php` ditulis ulang (belum ada DB aplikasi yang dimigrasikan, jadi aman): `household_id` kini **nullable** untuk Alur A, `email` wajib, `type` enum `new_household`/`partner`, `token_hash` unik, `invited_by`, `expires_at`, `accepted_at`, dan `revoked_at`. Kolom `accepted_by` dari LOG-032 dihapus karena tidak ada di §5.1 dan sudah tercakup audit (`partner_joined` mencatat causer dan `member_user_id`).
- `InvitationType` baru; `InviteHouseholdMember::handle($actor, $household, $email)` mewajibkan email partner yang valid (trim, huruf kecil, maksimal 255, `FILTER_VALIDATE_EMAIL`), menyimpan `type = partner`, dan tetap mengembalikan token plaintext sekali. `AcceptInvitation` menolak undangan yang sudah dicabut (`revoked_at`) dan undangan `new_household` (Alur A belum dibangun) selain aturan lama. `RevokeHouseholdInvitation` tidak lagi menghapus baris; ia menandai `revoked_at` sehingga riwayat undangan tetap ada, dan menolak undangan yang sudah diterima/dicabut.
- **Verifikasi:** `HouseholdInvitationTest` 20 kasus/71 assertion (email tidak valid ditolak tanpa menulis, normalisasi email, hash tersimpan, batas kedaluwarsa, pemakaian kedua, token asing/dirusak, household penuh/terhapus, user sudah punya household, otorisasi, revoke bertanda dan idempoten-negatif, undangan `new_household` ditolak jalur partner, unique `token_hash` di DB). Total 176 test PHP/1676 assertion, `composer lint`, `composer analyse`, dan `php artisan migrate --pretend --database=sqlite` (menampilkan `household_id` nullable, `type` check, `revoked_at`) lulus. MariaDB belum diuji lokal; bukti menunggu CI.
- **Batas:** belum ada pengiriman email, ikatan email saat penerimaan, route registrasi, UI, rate limit, Alur A admin, atau undangan pending ganda (P-02/P-04/P-11/P-13). `expires_at` disimpan UTC; belum ada cleanup undangan kedaluwarsa.
- Dokumen yang diselaraskan: logbook, `docs/development-plan.md`, `docs/00-MASTERPLAN.md` (§5.1 status implementasi), `docs/pending.md` (P-01 ditutup), `.github/copilot-instructions.md`, dan `AGENTS.md` root. ADR, runbook, UAT, dan spesifikasi modul tidak berubah karena keputusan stack, topologi, dan kontrak modul tetap.

### LOG-035 - Alur kerja pindah ke main tanpa branch

- Keputusan reviewer: karena dikembangkan sendiri, pekerjaan tidak lagi dipecah ke branch task; semua commit langsung ke `main`. Ini menggantikan aturan sebelumnya "satu task ID per branch/PR".
- Pertimbangan yang dicatat: tanpa branch tidak ada review gate sebelum perubahan masuk `main`, sehingga verifikasi lokal (`composer lint`, `composer analyse`, `composer test`) wajib dijalankan sebelum setiap push dan kegagalan CI diperbaiki maju di `main`. Satu task ID per commit tetap dipertahankan agar riwayat dan logbook tetap dapat dilacak. Tidak ada branch protection yang dikonfigurasi dan tidak ada bukti kewenangan untuk mengubahnya dari mesin ini.
- `core-03-support` berhenti disinkronkan dan tertinggal di `28ebeb2`; `core-01-foundation` tetap di `42bda38`. Keduanya sudah menjadi ancestor `main`. Penghapusan branch remote dicatat sebagai P-10 dan menunggu keputusan reviewer; tidak ada branch yang dihapus pada batch ini.
- **Verifikasi:** hanya perubahan dokumentasi/instruksi. `git diff --check` lulus; test, Pint, Larastan, dan build tidak dijalankan ulang karena tidak ada perubahan kode. Kondisi kode terakhir tetap 176 test/1676 assertion pada `3851754`.
- Dokumen yang diselaraskan: logbook, `docs/development-plan.md`, `docs/pending.md` (P-10 diperluas), `.github/copilot-instructions.md`, dan `AGENTS.md` root. Masterplan, ADR, runbook, UAT, dan spesifikasi modul tidak berubah karena keputusan ini soal alur kerja, bukan arsitektur atau kontrak.

### LOG-033 - Daftar tertunda dan aturan pencatatannya

- Motivasi reviewer: hasil kerja CORE-04/CORE-05 menyisakan konflik dan keputusan yang sebelumnya hanya muncul di jawaban chat. Mulai batch ini semua hal tersebut dicatat permanen di repositori.
- Dokumen baru [daftar tertunda](../pending.md) berisi 13 item `P-NN` dengan status, keputusan yang dibutuhkan, dampak bila ditunda, langkah berikutnya, dan tabel riwayat penutupan. Bagian A (butuh keputusan reviewer): skema undangan vs §5.1 (P-01), email undangan + titik masuk registrasi (P-02), alur reset kata sandi (P-03), invite household baru oleh admin (P-04), backfill histori periode (P-05), pengecualian `Activity`/`HouseholdMember` dari trait tenant (P-06), dan bukti konkurensi slot (P-07). Bagian B (pekerjaan tertunda): konteks job (P-08), bukti CI `528adbd` (P-09), branch `core-01-foundation` (P-10), UI/route/rate limit undangan (P-11), modul nonaktif (P-12), dan undangan pending ganda (P-13).
- `.github/skills/development-docs-sync/SKILL.md` diperbarui: daftar tertunda masuk daftar bacaan wajib, langkah kerja baru (item `P-NN`, ID stabil, penutupan dengan `LOG-NNN`, pengulangan keputusan terbuka di jawaban akhir), penomoran langkah setelahnya digeser, dan aturan penyelesaian baru bahwa batch tidak selesai bila ada konflik/keputusan/bukti yang belum tercatat di daftar. Skill personal tidak berubah karena `development-docs-sync` hanya ada di repositori.
- `.github/copilot-instructions.md` dan `AGENTS.md` root kini mewajibkan pembacaan `docs/pending.md` dan pembaruannya pada batch yang sama; aturan logbook bertambah butir ke-7 dengan isi yang sama.
- **Verifikasi:** `git diff --check` dan pemeriksaan tautan relatif baru (`docs/pending.md`, `../pending.md`, `../../.github/...`) pada berkas yang diubah. Tidak ada perubahan kode, dependency, skema, atau perilaku aplikasi, sehingga test PHP/Pint/Larastan dan build tidak dijalankan ulang; test terakhir tetap 171 test/1659 assertion pada `528adbd`.
- **Batas:** daftar ini ringkasan keputusan, bukan pengganti logbook; item ditutup hanya setelah ada keputusan reviewer atau bukti nyata. P-09 masih terbuka sampai hasil CI `528adbd` dilaporkan.

### LOG-036 - Email undangan, penerimaan partner, dan reset kata sandi

- Keputusan reviewer atas P-02/P-03: email undangan membawa tautan ke layar buat kata sandi, dan pengiriman tautan itulah yang membuat akun household; lupa kata sandi memakai layar reset yang sama; undangan partner mengikuti alur yang sama. Akun yang sudah ada tidak boleh mengganti kata sandi lewat undangan; ia harus masuk dulu untuk menerima.
- Fortify: `config/fortify.php` kini hanya mengaktifkan `Features::resetPasswords()`; registrasi, TOTP, dan passkey tetap mati. `FortifyServiceProvider` memasang view forgot/reset, `resetUserPasswordsUsing(ResetUserPassword::class)`, lokalisasi notifikasi `ResetPassword::toMailUsing`, binding `FailedPasswordResetLinkRequestResponse` ke `GenericPasswordResetLinkResponse`, dan mempertahankan limiter login 5/menit per email+IP.
- `GenericPasswordResetLinkResponse` memberi pesan `auth.reset_sent` yang sama untuk `INVALID_USER` maupun `RESET_THROTTLED` (status lain dilaporkan ke log), mempertahankan input email, sehingga formulir publik tidak bisa dipakai menebak email terdaftar. `ResetUserPassword` memvalidasi `required|string|min:8|confirmed` lalu menyimpan hash lewat cast model; token dihapus broker setelah berhasil.
- Undangan: `InviteHouseholdMember` mengirim `HouseholdInvitationMail` setelah transaksi (sinkron agar kegagalan mail terlihat; queue menyusul di CORE-10). Mail memuat tautan `invitation.show` dan waktu kedaluwarsa dalam timezone household. Route `GET /invitation/{token}` publik; `AcceptInvitationForm` menangani token tidak dikenal/kedaluwarsa/terpakai/dicabut, akun yang sudah ada (harus masuk; akun lain ditawari keluar), validasi nama maksimal 100 karakter dan kata sandi minimal 8 dengan konfirmasi, lalu `RegisterInvitedPartner` membuat akun dan admission dalam satu transaksi sekaligus memasukkan pengguna.
- Pengikatan email ditegakkan di domain, bukan hanya di UI: `AcceptInvitation` menolak token bila email akun tidak sama (case-insensitive) dengan email undangan (`household.invitation_email_mismatch`), sehingga tautan yang diteruskan tidak bisa diklaim akun lain. Test household-penuh disesuaikan agar memakai email yang cocok; satu test mismatch ditambahkan.
- UI: halaman login menautkan "Lupa kata sandi?", sedangkan forgot, reset, dan invitation memakai `x-auth-layout` dengan target sentuh ≥44px, ring fokus 2px, error `role="alert"`, hint kata sandi, dan copy dari `lang/id`/`lang/en` (`auth`, `passwords`, `household`, `validation`; `max.string` ditambahkan agar error panjang tetap berbahasa Indonesia).
- Test: `InvitationAcceptanceTest` 8 kasus (email terkirim beserta tautan, render halaman, token asing, akun dibuat+admitted+login, validasi gagal tanpa akun, status kedaluwarsa/terpakai/dicabut, akun lama harus masuk, akun lain harus keluar), `PasswordResetTest` 6 kasus (halaman, mail lokal, email asing dijawab sama + input dipertahankan, halaman reset, token sekali pakai, token salah, konfirmasi), `HouseholdInvitationTest` +1 mismatch, dan `AuthenticationTest` disesuaikan. Total **193 test PHP/1767 assertion**, `composer lint`, `composer analyse`, dan `npm run build` lulus pada SQLite in-memory.
- Verifikasi browser (Chrome headless lewat CDP; Playwright tidak terpasang dan tidak dipasang otomatis): 360px/1280px light/dark pada login, forgot, reset, dan invitation — overflow horizontal 0, semua kontrol ≥44px kecuali checkbox "Ingat saya" 20px di dalam label 44px, semua input berlabel, outline fokus 2px terlihat. Alur yang diperiksa: tautan lupa kata sandi, email asing menghasilkan pesan generik, reset lalu login dengan kata sandi baru, validasi undangan, bergabung, dan pesan undangan terpakai. Smoke memakai DB sementara di `%TEMP%`; `database/database.sqlite` tidak dimigrasikan (masih hanya tabel `migrations`). Catatan: `artisan serve` tidak meneruskan `DB_DATABASE`, jadi smoke dijalankan dengan `php -S` + router framework; server, profil Chrome, dan direktori sementara sudah dihapus.
- Batas: mail masih sinkron dan belum di-queue (CORE-10); belum ada rate limit pembuatan undangan 10/jam sesuai SEC-3 dan kegagalan mail setelah commit meninggalkan undangan pending tanpa email (P-14); permintaan reset belum punya throttle seragam/timing seragam (P-15). Alur A admin (P-04), konteks job (P-08), backfill periode (P-05), dan bukti konkurensi (P-07) tetap terbuka. CI untuk commit ini belum terverifikasi; jangan mengklaim lulus sebelum reviewer melaporkan.
- Publikasi: `d077170` di-push ke `origin/main` (`efae4f2..d077170`) pada 2026-10-26 setelah `composer lint`, `composer analyse`, `composer test` (193 test/1767 assertion), dan `npm run build` lulus. Workflow CI berjalan otomatis, tetapi hasilnya belum bisa dibaca dari mesin ini (repo privat, tanpa `gh`/API terautentikasi); bukti remote tetap menunggu laporan reviewer (P-09).
- Dokumen yang diselaraskan: logbook, `docs/development-plan.md`, `docs/00-MASTERPLAN.md` (checkpoint auth dan status A5.1), `docs/pending.md` (P-02/P-03/P-11 ditutup; P-14/P-15 baru), `.github/copilot-instructions.md`, dan `AGENTS.md` root. ADR, runbook, UAT, dan spesifikasi modul tidak berubah karena stack, topologi, dan kontrak modul tetap.

### LOG-037 - Playwright jadi alat utama verifikasi browser

- Keputusan reviewer (2026-10-26, di luar nomor task): verifikasi browser memakai Playwright lebih dulu; bila tidak memungkinkan, baru pakai metode lain. Instruksi proyek sebelumnya melarang pemasangan Playwright otomatis, sehingga aturan itu kini bertentangan dengan keputusan reviewer.
- Perubahan instruksi: `.github/copilot-instructions.md` langkah `webapp-testing` dan `AGENTS.md` (bagian skill UI/browser) sekarang meminta `playwright-core` dipasang di direktori sementara di luar repo lalu menjalankan Chrome yang sudah ada di mesin. Tidak ada dependency, lockfile, browser bawaan Playwright, atau job CI baru; fallback Chrome/CDP tetap didokumentasikan dan wajib dilaporkan bila dipakai.
- Bukti kelayakan: `playwright-core@1.64.1` dipasang di `%TEMP%\opencode\fh-pw` (1 detik, 1 paket) dan menjalankan Chrome headless lewat `executablePath`, sehingga tidak perlu unduhan browser ±150 MB. Verifikasi LOG-036 diulang dengan Playwright pada build final: 16 audit halaman (login, forgot, reset, invitation × 360/1280 px × light/dark) semuanya overflow 0, tidak ada input tanpa label, `lang=id`, dan satu-satunya kontrol <44px adalah checkbox "Ingat saya" 20px di dalam label 44px; lima alur lulus (tautan lupa kata sandi, email asing dijawab generik + input dipertahankan, undangan bergabung, reset berhasil, login dengan kata sandi baru); 0 console error. Hasil identik dengan jalur CDP, jadi tidak ada perubahan kode aplikasi pada batch ini.
- Batas: Playwright belum menjadi dependency repo dan belum masuk CI; ini keputusan terbuka P-16.
- Verifikasi batch: hanya dokumentasi dan instruksi. `composer lint`, `composer analyse`, dan `composer test` dijalankan ulang sebelum commit (193 test/1767 assertion lulus) karena aturan push mewajibkannya, bukan karena kode berubah. DB sementara, server, dan direktori `%TEMP%` sudah dihapus.

### LOG-038 - Koreksi catatan lokasi aturan Playwright

- Koreksi atas LOG-037: larangan pemasangan Playwright ternyata tidak berada di skill personal (`%USERPROFILE%\.copilot\skills` hanya berisi `ponytail` dan `premium-frontend-ui`), melainkan di skill repo `.github/skills/webapp-testing/SKILL.md` baris 12 ("do not install Playwright automatically") dan baris 14 (pemakaian Playwright hanya lewat MCP atau instalasi lokal). Aturan lama di `.github/copilot-instructions.md` sudah diganti pada LOG-037.
- Perbaikan: skill `webapp-testing` kini meminta Playwright lebih dulu lewat `playwright-core` di direktori sementara dan Chrome sistem, memakai MCP bila tersedia, fallback Chrome/CDP bila gagal (beserta batas buktinya), dan promosi ke devDependency/CI hanya dengan persetujuan reviewer + ADR. Skill `web-design-reviewer` sudah merujuk Playwright MCP sehingga tidak berubah. `docs/pending.md` P-16 tidak lagi menyebut skill personal dan tinggal menyisakan satu keputusan: permanen temp-only atau promosi ke dependency/CI.
- Verifikasi: `git diff --check` bersih; `composer lint`, `composer analyse`, dan `composer test` (193 test/1767 assertion) lulus sebelum push. Tidak ada perubahan kode aplikasi, dependency, skema, atau perilaku.

### LOG-039 - Bukti konkurensi slot partner (CORE-04, menutup P-07)

- P-07 ditutup atas keputusan reviewer (2026-10-26) untuk mengerjakan item yang tidak butuh keputusan produk lebih dulu. Masterplan CORE-04 memang sudah mencantumkan "unique household/slot menolak penerimaan undangan bersamaan" dan "test konkurensi SQLite/MariaDB" sebagai AC, jadi ini pekerjaan yang sudah disetujui, bukan keputusan baru.
- Perilaku: `AcceptInvitation` kini menangkap `Illuminate\Database\UniqueConstraintViolationException` pada penyisipan anggota dan melemparkan `household.invite_full`. Sebelumnya, permintaan yang kalah balapan menghasilkan error 500; sekarang gagal tertutup dengan pesan domain yang sama seperti jalur pra-cek, dan transaksi rollback sehingga undangan tetap pending.
- Test baru `tests/Feature/InvitationConcurrencyTest.php` (3 kasus/11 assertion):
  1. Interleaving deterministik lewat event `HouseholdMember::creating`: baris pesaing mengambil slot 2 setelah daftar slot dibaca tetapi sebelum insert, lalu penerimaan harus gagal dengan `household.invite_full`, household tetap 1 anggota (rollback), undangan belum diterima, dan tidak ada audit `partner_joined`.
  2. Tanpa guard aplikasi, insert mentah anggota ketiga pada slot yang sama ditolak `UniqueConstraintViolationException` dan household tetap 2 anggota — ini backstop yang sebenarnya, dan test berjalan di SQLite maupun MariaDB lewat CI.
  3. `(household_id, slot)` unik per household, bukan global: slot 2 di household lain tetap boleh.
- Batas yang dicatat jujur: tidak ada test proses paralel OS-level (dua worker sungguhan). Test membuktikan constraint database dan penanganan jalur kalah-balapan secara deterministik; bukti paralelisme nyata hanya bisa didapat dari MariaDB CI dan tidak diklaim di sini.
- Verifikasi lokal: `composer lint` dan `composer analyse` bersih, `composer test` **196 test/1778 assertion** lulus (sebelumnya 193/1767).
- Dokumen diselaraskan: logbook (entri ini + tabel status), `docs/development-plan.md` (§4 CORE-04/CORE-05/CORE-15, tabel posisi, jumlah test, §3 ditandai historis), dan `docs/pending.md` (P-07 pindah ke riwayat penutupan; P-09/P-10/P-13/P-15/P-16 dipindah ke bagian A sesuai aturan file; P-17 baru untuk provisioning system admin pertama). CI untuk commit ini belum terverifikasi (P-09).

### LOG-040 - Batas 10 undangan per jam (CORE-05, menutup separuh P-14)

- Angka batasnya bukan keputusan baru: SEC-3 masterplan menetapkan "undangan 10/jam", jadi bagian limiter pada P-14 dikerjakan tanpa menunggu reviewer.
- Perilaku: `InviteHouseholdMember` memeriksa `RateLimiter::tooManyAttempts` untuk kunci `core:invite-partner:{actor}` sebelum transaksi dengan `MAX_PER_HOUR = 10` dan `THROTTLE_DECAY_SECONDS = 3600`; bila terlampaui muncul `household.invite_throttled` sebagai `ValidationException` pada field `email`. Hit baru dicatat setelah email benar-benar terkirim, sehingga salah ketik alamat atau kegagalan kirim tidak menghabiskan kuota.
- Test baru 3 kasus/29 assertion di `tests/Feature/HouseholdInvitationTest.php`: undangan ke-11 ditolak dan hanya 10 baris tersimpan, 12 percobaan email tidak valid tidak memakai kuota, dan undangan diizinkan lagi setelah jendela satu jam lewat.
- Kunci per aktor (bukan per IP) karena action belum punya route/endpoint; batas per IP+email ala SEC-3 untuk endpoint HTTP menyusul bersama Alur A/panel undang (P-04). Ini batas yang dicatat, bukan klaim bahwa SEC-3 sudah lengkap.
- Batas: pengiriman email tetap sinkron setelah commit; kegagalan mail masih meninggalkan undangan pending tanpa email sampai CORE-10. Sisa itu tetap dilacak sebagai P-14 (bagian B).
- Verifikasi lokal: `composer lint` dan `composer analyse` bersih, `composer test` **199 test/1807 assertion** lulus (sebelumnya 196/1778).
- Dokumen diselaraskan: logbook (entri ini + tabel status), `docs/development-plan.md` (status, checkpoint undangan, jumlah test, tabel posisi, baris CORE-05), `docs/pending.md` (status + P-14 menyisakan antrean mail), `docs/00-MASTERPLAN.md` (status implementasi §5.1 dan checkpoint auth), `AGENTS.md`, dan `.github/copilot-instructions.md`. CI untuk commit ini belum terverifikasi (P-09).

### LOG-041 - Satu undangan aktif, admin bukan anggota household, branch lama dihapus

- Keputusan reviewer (2026-10-26): (a) akun system admin hanya dipakai sebagai user manajemen dan tidak boleh ikut household; (b) branch lama dihapus; (c) hanya satu undangan yang boleh aktif. Batch dokumentasi tidak perlu commit sendiri, jadi kode + dokumen masuk bersama.
- P-13 — satu undangan aktif: `InviteHouseholdMember` mencabut undangan pending lain di household yang sama (stempel `revoked_at`, baris tetap ada) di dalam transaksi yang sudah mengunci baris household, jadi dua undangan bersamaan tetap berurutan dan hanya satu yang aktif. Test baru: undangan kedua menyisakan tepat satu undangan aktif, token lama ditolak, token baru tetap bisa diterima.
- P-04 — admin murni manajemen: `CreateHousehold` dan `AcceptInvitation` menolak akun `is_system_admin` dengan pesan `household.admin_not_member` (id/en). Ini menegakkan keputusan reviewer, bukan sekadar catatan; admin tetap boleh membuat invite household baru untuk orang lain. Konflik dokumen tercatat: masterplan §2.1 sebelumnya berbunyi "admin juga bisa menjadi member household-nya sendiri", sehingga kalimat itu diganti dan keputusan reviewer dicatat sebagai pemenang sesuai aturan P-01/LOG-034. Dua test baru menutup jalur ini.
- P-10 — branch: `core-01-foundation` (`42bda38`) dan `core-03-support` (`28ebeb2`) dihapus dari remote dan lokal; remote hanya menyisakan `main`.
- Penyesuaian test lama: karena satu household kini hanya punya satu undangan aktif, test "household penuh" mengisi slot kedua secara langsung, dan test status undangan (kedaluwarsa/terpakai/dicabut) memakai household terpisah per status. Tidak ada perilaku yang dilonggarkan.
- Verifikasi lokal: `composer lint` dan `composer analyse` bersih, `composer test` **202 test/1825 assertion** lulus (sebelumnya 199/1807).
- Dokumen diselaraskan: logbook (entri ini + tabel status), `docs/00-MASTERPLAN.md` (§2.1 peran admin, §5.1 status implementasi, §5.2 poin 5), `docs/development-plan.md` (status, checkpoint undangan, jumlah test, tabel posisi, baris CORE-05), `docs/pending.md` (P-04/P-10/P-13 pindah ke riwayat penutupan), `AGENTS.md`, dan `.github/copilot-instructions.md`. CI untuk commit ini belum terverifikasi (P-09).

### LOG-042 - Profil optimasi Odroid-C2, konfigurasi SQLite WAL, dan penegasan stack tanpa Redis

- **Konteks & Keputusan:** Reviewer meminta penyesuaian stack untuk server produksi rumah Odroid-C2 (RAM 2GB, CPU ARM Cortex-A53 4-core, eMMC, Armbian Ubuntu Minimal).
- **Evaluasi Stack:**
  - Menegaskan arsitektur *zero-daemon* untuk session & cache: tidak menggunakan daemon Redis (menghemat 30–50MB RAM dan mengeliminasi proses ekstra). Session dan cache dialihkan ke SQLite/file sesuai kebutuhan.
  - SQLite WAL: Ditegakkan wajib di `config/database.php` dan `AppServiceProvider` melalui event `ConnectionEstablished` (`PRAGMA busy_timeout = 5000; PRAGMA journal_mode = WAL; PRAGMA synchronous = NORMAL; PRAGMA cache_size = -8000; PRAGMA temp_store = MEMORY;`).
  - Zero-Node on Production: Dilarang memasang Node.js/Vite di Odroid-C2. Aset frontend di-build di environment developer/CI (`npm run build`) dan disinkronkan ke `public/build/`.
  - Profil OS & Memori Odroid-C2: ZRAM bawaan Armbian untuk swap terkompresi di RAM, `vm.swappiness = 10`, PHP-FPM pool `ondemand` (`max_children = 4`), OPcache 64MB (`validate_timestamps = 0`), dan queue worker didaur ulang (`--max-jobs=200 --max-time=3600 --memory=64`).
- **Instalasi Skill:** Skill `ponytail` (beserta `ponytail-review` dan `ponytail-audit`) serta `premium-frontend-ui` dipasang di `.github/skills/` dan disinkronkan ke `.agents/skills/`.
- **Verifikasi Lokal:**
  - `composer lint` (Pint) lulus.
  - `composer analyse` (Larastan Level 6) `[OK] No errors`.
  - `composer test` **202 test/1825 assertion** lulus.
  - `npm test` **4 test JS** lulus.
- **Dokumen yang diselaraskan:** `docs/runbook/deploy.md` (§3.1 profil Odroid-C2 dan tabel data lingkungan), `docs/00-MASTERPLAN.md` (DB-1 dan §13.3), `docs/development-plan.md`, `docs/pending.md`, dan logbook ini.

### LOG-043 - Sisa CORE-05 Alur A: artisan command system admin (P-17) dan alur undangan household baru

- **P-17 — Provisioning System Admin Pertama:** Command Artisan `php artisan core:create-admin` tersedia untuk membuat atau mempromosikan user ke `is_system_admin = true`. Command memvalidasi nama, email, dan password minimal 8 karakter; jika user sudah memiliki membership household, proses promosi ditolak (menegakkan LOG-041 bahwa admin dilarang menjadi anggota household). Menutup item P-17 di daftar tertunda.
- **Alur A — Undangan Household Baru oleh Admin:**
  - `InviteNewHousehold`: Hanya dapat dipanggil oleh akun dengan `is_system_admin = true`. Mengirim undangan tipe `new_household` (dengan `household_id = null`), token SHA-256 acak 64 karakter, masa berlaku 7 hari, dibatasi 10 undangan per jam per aktor (SEC-3). Undangan pending baru mencabut undangan pending sebelumnya untuk email yang sama di dalam transaksi. Mengirim email `HouseholdInvitationMail` secara sinkron dan mencatat audit `core.household.new_household_invited`.
  - `AcceptNewHouseholdInvitation` & `RegisterInvitedOwner`: Menerima undangan `new_household`, memvalidasi email akun cocok dengan undangan, menolak akun admin sistem atau akun yang sudah menjadi anggota household lain, lalu memanggil `CreateHousehold` untuk membuat household dan menetapkan pengguna sebagai Owner (slot 1). Menandai undangan `accepted_at` dan mencatat audit `core.household.owner_joined`.
  - Integrasi Livewire: `AcceptInvitationForm` kini mendeteksi tipe undangan `new_household` vs `partner`. Bila `new_household`, form menampilkan field nama keluarga (`householdName`) dan copy lokalisasi yang sesuai, lalu membuat akun owner dan bootstrap household.
- **Verifikasi Lokal:**
  - `composer lint` (Pint) lulus.
  - `composer analyse` (Larastan Level 6) `[OK] No errors`.
  - `composer test` **214 test/1870 assertion** lulus (+12 test baru pada `CreateSystemAdminCommandTest` dan `AdminHouseholdInvitationTest`).
  - `npm test` **4 test JS** lulus.
  - `npm run build` berhasil memperbarui aset dan manifest Vite.
- **Dokumen yang diselaraskan:** `docs/00-MASTERPLAN.md` (§5.1 status implementasi), `docs/development-plan.md` (status, tabel posisi, baris CORE-05), `docs/pending.md` (P-17 ditutup ke riwayat penutupan), dan logbook ini.

### LOG-044 - CORE-06 Onboarding Wizard: Nama Household, Waktu & Periode, Undang Partner

- **Task ID:** CORE-06 (Onboarding Household)
- **Implementasi Skema & Model:**
  - Migrasi `database/migrations/2026_10_27_000001_add_onboarded_at_to_households_table.php` menambahkan kolom `onboarded_at` (timestamp, nullable) pada tabel `households`.
  - Model `App\Core\Models\Household` menambahkan `onboarded_at` ke `$fillable`, cast `immutable_datetime`, dan method pembantu `isOnboarded(): bool`.
- **Middleware Proteksi Onboarding:**
  - `App\Http\Middleware\EnsureHouseholdOnboarded` (alias `'onboarded'` di `bootstrap/app.php`) memastikan user berstatus Owner yang belum menyelesaikan onboarding (`onboarded_at === null`) dialihkan ke rute `/onboarding` saat mengakses rute terproteksi aplikasi (seperti `/`).
  - Partner, guest, dan akun admin sistem dikecualikan dari pengalihan ini.
- **Komponen Livewire Wizard 3 Langkah:**
  - `App\Livewire\Onboarding\HouseholdWizard` dan view `resources/views/livewire/onboarding/household-wizard.blade.php`:
    - **Langkah 1 (Nama Household):** Menampilkan nama household awal (dari proses pendaftaran undangan), validasi 1–100 karakter, tombol "Lanjutkan".
    - **Langkah 2 (Zona Waktu & Periode):** Pilihan zona waktu dengan grup utama Indonesia (`Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura`) serta daftar IANA lengkap; pilihan hari mulai siklus keuangan bulanan 1–28 (default 1). Menyimpan perubahan ke household dan memperbarui baris awal `HouseholdFinancePeriodSetting` dengan batas konteks tenant eksplisit. Tombol "Kembali" dan "Lanjutkan".
    - **Langkah 3 (Undang Pasangan Opsional):** Input email pasangan dengan penjelasan hak akses Partner. Jika diisi, memanggil aksi `InviteHouseholdMember` (mengirim email undangan dan mencatat log). Jika dikosongkan atau memilih "Lewati untuk sekarang", proses selesai tanpa mengirim undangan.
    - **Penyelesaian:** Memberi stempel `onboarded_at = now()`, mencatat audit log `core.household.onboarded`, menampilkan flash alert sukses, dan mengarahkan pengguna ke beranda `/`.
  - Stepper visual progress bar terintegrasi dengan aksesibilitas ARIA, navigasi tombol kembali/lanjut, dan dukungan mode terang/gelap serta responsif 360px mobile hingga 1280px desktop.
  - Halaman wrapper `resources/views/onboarding.blade.php` menggunakan layout bersama `<x-auth-layout>`.
  - Komponen `AcceptInvitationForm` diperbarui untuk mengarahkan pengguna pembuat household baru langsung ke `route('onboarding')`.
  - Teks lokalisasi lengkap ditambahkan pada `lang/id/household.php` dan `lang/en/household.php`.
- **Verifikasi:**
  - Test suite baru `tests/Feature/HouseholdOnboardingTest.php` (11 test, 57 assertion) mencakup seluruh alur: pengalihan owner belum onboarding, izin akses owner yang sudah onboarding, guard untuk guest/admin/partner, validasi langkah 1 & 2, navigasi bolak-balik, skip langkah 3 tanpa email, pengiriman undangan partner di langkah 3, dan penanganan validasi email.
  - `composer test`: **225 test/1927 assertion** lulus (sebelumnya 214/1870).
  - `npm test`: **4 test JS** lulus.
  - `composer lint` (Pint): Lulus.
  - `composer analyse` (Larastan level 6): `[OK] No errors`.
  - `npm run build`: Aset frontend Vite terkompilasi bersih.
- **Dokumen yang diselaraskan:** `docs/logbook.md` (entri ini), `docs/development-plan.md`, `docs/pending.md`, dan `docs/00-MASTERPLAN.md`.

### LOG-045 - CORE-07 Layout, Navigasi Mobile/Desktop, FAB, Bottom Sheet, Dark Mode, dan Komponen §9.5

- **Task ID:** CORE-07 (Layout & Navigasi)
- **Komponen Blade Bersama (§9.5):**
  - `x-card`: Kontainer kartu bersudut `rounded-3xl` dengan title, subtitle, badge, dan actions.
  - `x-datetime`: Komponen waktu/tanggal instant berbasis timezone household (dengan class `App\View\Components\Datetime.php`).
  - `x-badge-status`: Status jatuh tempo sesuai §9.1 poin 4 (`Terlambat` rose, `Hari ini` amber, `Segera` sky/info, `Lunas/Selesai` emerald/green).
  - `x-member-avatar`: Avatar inisial dengan warna slot/peran anggota (`#059669` owner, `#7C3AED` partner).
  - `x-empty-state`: Ilustrasi ikon, judul, deskripsi, dan tombol aksi terintegrasi.
  - `x-progress-bar`: Bar kemajuan dengan pewarnaan otomatis (<80% emerald, 80–99% amber, ≥100% rose).
  - `x-offline-banner`: Banner deteksi status koneksi offline secara reaktif via Alpine `navigator.onLine`.
  - `x-period-switcher`: Navigator periode antar bulan/siklus (`[◀] Nov 2026 [▶]`).
  - `x-filter-chips`: Baris chip filter horizontal dengan dukungan touch target dan overflow scroll ramah mobile.
  - `x-fab`: Tombol aksi mengambang utama di tengah bottom nav mobile dengan target sentuh ≥48px.
  - `x-confirm`: Dialog konfirmasi aksi penting/berisiko dengan opsi verifikasi ketik kata kunci ("HAPUS").
  - `x-bottom-sheet`: Dialog/bottom sheet responsif (slide-up di mobile, modal di desktop) dengan backdrop dan aksesibilitas keyboard Escape.
- **Master Shell Layout & Navigasi (§9.2):**
  - `resources/views/components/app-layout.blade.php` (dan `components/layouts/app.blade.php`):
    - **Desktop (≥1024px):** Sidebar kiri persisten lebar 64 (256px), logo brand, kartu household aktif, daftar anggota keluarga, menu modul ber-badge "Segera" jika belum aktif, toggle dark mode, dan logout.
    - **Mobile (<1024px):** Top bar ringkas (nama household, toggle dark mode, lonceng notifikasi, avatar) dan Bottom Navigation 5 item: `Beranda`, `Keuangan`, `[+]` (FAB tengah), `Kalender`, `Lainnya`.
    - **Quick Add Bottom Sheet:** Membuka pilihan aksi cepat (`Pengeluaran`, `Pemasukan`, `Transfer`, `Tugas`, `Catatan`, `Item Belanja`, `Laporan Kerusakan`) yang disaring secara kondisional berdasarkan feature flag `config/modules.php`.
    - **Lainnya Bottom Sheet:** Menu akses cepat ke modul non-utama, pengaturan, dan opsi logout.
- **Dark Mode (§9.4):**
  - Implementasi class/data-theme dark mode disimpan di `localStorage` dan terintegrasi otomatis dengan preferensi browser (`prefers-color-scheme: dark`).
  - Skrip inisialisasi inline di `<head>` mencegah kedipan putih (*flash of unstyled theme*).
- **Halaman Beranda & Welcome:**
  - `resources/views/dashboard.blade.php`: Tampilan awal dashboard bagi anggota keluarga terautentikasi dan ter-onboard.
  - `resources/views/welcome.blade.php`: Halaman depan publik privat bernuansa hangat, organik, hijau hutan, dengan tautan masuk.
- **Verifikasi:**
  - Test suite baru `tests/Feature/LayoutAndComponentsTest.php` (12 test, 54 assertion): uji render welcome guest, render dashboard shell untuk pengguna terautentikasi, uji render seluruh 12 komponen §9.5, uji ambang batas warna progress bar, format timezone datetime, dan status badge.
  - `composer test`: **237 test/1981 assertion** lulus (+12 test baru).
  - `npm test`: **4 test JS** lulus.
  - `composer lint` (Pint): Lulus.
  - `composer analyse` (Larastan level 6): `[OK] No errors` (42 file).
  - `npm run build`: Aset CSS dan JS Vite terkompilasi bersih (86.87 kB CSS, 2.89 kB JS).
- **Dokumen yang diselaraskan:** `docs/logbook.md` (entri ini), `docs/development-plan.md`, `docs/pending.md`, dan `docs/00-MASTERPLAN.md`.

### LOG-046 - CORE-08 Recurrence Engine Wrapper di Sekitar php-rrule

- **Task ID:** CORE-08 (Recurrence Wrapper)
- **Komponen & Fungsionalitas:**
  - `App\Core\Recurrence\Recurrence`:
    - Wrapper statis di sekitar `rlanvin/php-rrule` (`RRule`).
    - `occurrencesBetween(string $rrule, DateTimeInterface|string $dtstart, DateTimeInterface|string $from, DateTimeInterface|string $to, int $limit = 500): array<CarbonImmutable>`: Menghasilkan daftar tanggal berulang dalam rentang terikat, aman membatasi kuota maksimal 500 kemunculan per kueri untuk melindungi memori server kecil.
    - `next(string $rrule, DateTimeInterface|string $dtstart, DateTimeInterface|string $after, bool $inclusive = false): ?CarbonImmutable`: Mengembalikan satu kejadian berikutnya secara strictly after atau inclusive.
    - `humanize(string $rrule, string $locale = 'id'): string`: Mengubah string RRULE standar RFC 5545 menjadi teks bahasa Indonesia (atau Inggris) yang alami dan mudah dipahami, menangani:
      - Harian: "Setiap hari", "Setiap N hari"
      - Mingguan: "Setiap minggu pada hari X", "Setiap N minggu pada hari X, Y"
      - Bulanan: "Setiap bulan tanggal N", "Setiap bulan (akhir bulan)" (menggunakan `BYMONTHDAY=-1`), "Setiap N bulan..."
      - Tahunan: "Setiap tahun", "Setiap N tahun"
      - Pembatas `COUNT`: "sebanyak N kali"
      - Pembatas `UNTIL`: "sampai d M Y"
- **Pengujian & Kasus Uji Khusus:**
  - Unit test `tests/Unit/RecurrenceTest.php` (12 test, 56 assertion) mencakup seluruh preset UI §7.4:
    - Preset harian (interval 1 dan 2).
    - Preset mingguan hari tertentu (`BYDAY=MO,WE,FR`).
    - Preset bulanan tanggal tertentu (`BYMONTHDAY=15`).
    - Preset akhir bulan (`BYMONTHDAY=-1`) termasuk penanganan tahun kabisat (29 Februari 2028 vs 28 Februari 2026).
    - Preset hitungan (`COUNT=3`) dan batas tanggal (`UNTIL=...`).
    - Validasi rentang tanggal (`InvalidArgumentException` jika `$from > $to`).
    - Kejadian berikutnya strictly after dan inclusive, serta penanganan `null` saat batas tercapai.
    - Lokalisasi `humanize()` bahasa Indonesia dan bahasa Inggris.
- **Verifikasi:**
  - `composer test`: **249 test/2037 assertion** lulus (+12 test baru).
  - `npm test`: **4 test JS** lulus.
  - `composer lint` (Pint): Lulus.
  - `composer analyse` (Larastan level 6): `[OK] No errors` (43 file).
- **Dokumen yang diselaraskan:** `docs/logbook.md` (entri ini), `docs/development-plan.md`, `docs/pending.md`, dan `docs/00-MASTERPLAN.md`.

## Aturan pembaruan berikutnya

1. Tambahkan entri baru dengan tanggal dan ID `LOG-NNN`; sertakan task ID resmi (`CORE-*`, `FIN-*`, `ORG-*`, `AST-*`) jika relevan.
2. Catat perubahan, keputusan reviewer/ADR, command dan hasil verifikasi, kegagalan, serta pemeriksaan yang tidak dijalankan. Jangan mencatat secret atau data pengguna.
3. Perbarui status tahap terkait di [rencana development](development-plan.md) dan tautkan ke entri bukti. Jangan menandai selesai hanya karena paket terpasang atau kode sudah ditulis.
4. Task selesai hanya setelah acceptance criteria dan Definition of Done terpenuhi serta diverifikasi reviewer. Bila terblokir, tulis penyebab dan langkah pembuka blokir.
5. Untuk deploy, tambahkan commit/tag rilis aktual dan bukti di [runbook deploy](runbook/deploy.md) serta [UAT](uat-go-live.md). Jangan menyebut commit/tag tersedia sebelum benar-benar dibuat.
6. Terapkan aturan ini pada setiap batch perubahan, bukan hanya task coding. Ikuti [development-docs-sync](../.github/skills/development-docs-sync/SKILL.md); perubahan struktural wajib meninjau seluruh dokumentasi dan menyelaraskan semua yang terdampak. Investigasi read-only tidak memerlukan entri, dan pembaruan tracking dalam batch yang sama tidak memerlukan entri berulang.
7. Catat setiap konflik, ambiguitas, keputusan tertunda, pemeriksaan yang dilewati, atau bukti yang belum ada sebagai item `P-NN` di [daftar tertunda](pending.md). Item tidak dianggap tercatat bila hanya muncul di jawaban chat atau hanya di dalam logbook. Tutup item dengan mencatat `LOG-NNN` yang memutuskannya, dan ulangi keputusan reviewer yang masih terbuka di akhir jawaban.
