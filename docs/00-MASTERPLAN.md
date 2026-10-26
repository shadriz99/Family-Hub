# 🏠 Family Hub — MASTERPLAN
**Dokumen:** 00-MASTERPLAN.md
**Versi:** 0.3
**Status:** Approved for Phase 0 (Core) — modul lain mengikuti dokumen masing-masing
**Pemilik produk / Reviewer:** Shadri (manusia)
**Pelaksana:** AI Agent (developer) — wajib membaca dokumen ini sebelum mengerjakan task apa pun

---

## 0. Peta Dokumen & Cara Membaca

| File | Isi | Kapan dibaca |
|---|---|---|
| `00-MASTERPLAN.md` | Kerangka sistem, standar teknis, Core module, kontrak lintas modul, aturan kerja AI agent | **Selalu**, sebelum task apa pun |
| `01-MODULE-FINANCE.md` | Keuangan, anggaran, rencana bulanan, tagihan rutin, pinjaman & cicilan | Task berprefix `FIN-` |
| `02-MODULE-ORGANIZER.md` | Tugas rumah tangga, catatan, daftar belanja, kalender, pengingat, ekspor .ics | Task berprefix `ORG-` |
| `03-MODULE-ASSET.md` | Inventaris aset, garansi/dokumen, perawatan, servis, vendor, laporan kerusakan | Task berprefix `AST-` |
| `docs/runbook/deploy.md` | Prosedur deploy/pembaruan produksi dan pengecekan operasional | Deploy server |
| `docs/runbook/backup-restore.md` | Prosedur backup, uji restore, dan pemulihan insiden | Sebelum go-live dan operasi |
| `docs/uat-go-live.md` | Checklist penerimaan lintas modul dan kesiapan rilis | Sebelum rilis |
| `docs/logbook.md` | Riwayat pekerjaan aktual, verifikasi, dan batas hasil | Sebelum melanjutkan pekerjaan dan setelah setiap task |
| `docs/development-plan.md` | Setup/restore environment, urutan development, gate, status, dan keputusan tertunda | Menyiapkan environment dan memilih langkah berikutnya; sinkronkan dengan logbook |

**Hirarki kebenaran:** Jika ada konflik, urutan prioritas: (1) keputusan terbaru reviewer di PR/chat → (2) dokumen modul → (3) MASTERPLAN. Konflik **wajib** dilaporkan dan dokumen diperbarui.

**Kata kunci normatif:** **WAJIB** (MUST), **JANGAN** (MUST NOT), **SEBAIKNYA** (SHOULD), **BOLEH** (MAY).

---

## 1. Ringkasan Produk

Family Hub adalah aplikasi web (PWA) **self-hosted di server rumah** untuk pasangan suami–istri mengelola:
1. **Keuangan** — transaksi, anggaran, rencana bulanan, tagihan rutin, pinjaman & cicilan.
2. **Organisasi rumah tangga** — tugas, catatan bersama, daftar belanja, kalender terpadu, pengingat.
3. **Aset rumah tangga** — inventaris, garansi, jadwal perawatan, riwayat servis, vendor.

**Nilai utama (differentiator):** ketiga modul **terintegrasi**. Contoh:
- Cicilan pinjaman → otomatis menjadi rencana pengeluaran bulanan → muncul di kalender → pengingat H-3.
- Servis AC dicatat → otomatis menjadi transaksi pengeluaran → jadwal servis berikutnya dihitung ulang.
- Daftar belanja selesai → menjadi transaksi pengeluaran.

### 1.1 Keputusan Discovery (hasil grill round 1)

| # | Topik | Keputusan | Dampak desain |
|---|---|---|---|
| D1 | Pengguna | Utama untuk keluarga sendiri. Kerabat/teman boleh diberi akses, tetapi **setiap household maksimal 2 akun (suami & istri)** | Multi-household, data terisolasi, `max 2 member` per household |
| D2 | Rilis | Bertahap, dokumen terpisah per modul | Phase 0 Core → 1 Finance → 2 Organizer → 3 Asset |
| D3 | Penginput data | Hanya suami & istri | Tidak ada peran anak/ART/guest. RBAC sederhana |
| D4 | Transparansi | Keuangan **transparan penuh** | Tidak ada dompet privat. Semua member melihat semua data finansial |
| D5 | Pinjaman | Input pokok, angsuran, jatuh tempo, total bayar → **otomatis masuk rencana pengeluaran tiap bulan** | Konsep `planned_items` di Finance |
| D6 | Offline | **Read-only** offline cukup | SW network-first + cache halaman; form dinonaktifkan saat offline |
| D7 | Kalender | Kalender internal + **download .ics** (1 arah) | `CalendarAggregator` + ekspor .ics |
| D8 | Tim & hosting | Reviewer: Shadri. Developer: AI agent. Host: **server rumah Linux bare-metal**, Nginx; akses internet saat ini melalui **zrok** | PHP-FPM dan proses aplikasi berjalan langsung di Linux, tanpa Docker; HTTPS publik melalui zrok; migrasi ke Cloudflare Tunnel dipertimbangkan saat skala membesar |

### 1.2 Non-Goals (JANGAN dibangun tanpa ADR baru)
- Integrasi bank otomatis / open banking / scraping mutasi.
- Sinkron 2 arah Google Calendar.
- Input data saat offline (offline write).
- Peran anak, ART, teknisi, atau guest.
- Aplikasi native Android/iOS.
- Fitur investasi, saham, kripto.
- Multi mata uang (semua dalam IDR).

Pengelompokan dana **Tabungan & Investasi** serta pencatatan setoran/transfer masuk Finance MVP; non-goal investasi di atas berarti analisis portofolio, harga pasar, dan perdagangan, bukan larangan alokasi dana masa depan. Finance memakai lima alokasi custom (Kebutuhan Hidup, Tersier, Cicilan / Utang, Tabungan & Investasi, Donasi / Sosial / Zakat); rinciannya ada pada US-FIN-04A.

---

## 2. Model Pengguna & Tenancy

### 2.1 Entitas
- **System Admin** — pemilik server (Shadri). Mengelola household & undangan via halaman `/admin`. **JANGAN** bisa melihat data finansial/catatan household lain (privasi). **Keputusan reviewer LOG-041:** akun ini murni akun manajemen dan **tidak boleh menjadi anggota household mana pun** (termasuk household sendiri); keputusan ini menggantikan kalimat lama "admin juga bisa menjadi member household-nya sendiri".
- **Household** — satu rumah tangga. Batas: **maksimal 2 member**.
- **Member** — user dalam household, peran:
  - `owner` — pembuat household.
  - `partner` — pasangan yang diundang.

### 2.2 Aturan Akses (RBAC)

| Aksi | owner | partner |
|---|:-:|:-:|
| Semua CRUD data domain (finance, organizer, asset) | ✅ | ✅ |
| Lihat semua data household | ✅ | ✅ |
| Undang partner / batalkan undangan | ✅ | ❌ |
| Ubah pengaturan household (nama, periode keuangan, zona waktu) | ✅ | ✅ |
| Keluarkan partner | ✅ | ❌ |
| Transfer ownership | ✅ | ❌ |
| Hapus household | ✅ | ❌ |
| Keluar dari household | ❌ (harus transfer dulu) | ✅ |

> Tidak menggunakan `spatie/laravel-permission` (overkill). Gunakan **Laravel Policy** + enum `MemberRole`.

**Implementasi parsial LOG-020:** `App\Core\Policies\HouseholdPolicy` terdaftar eksplisit pada Gate untuk ability `view`, `update`, `invitePartner`, `cancelInvitation`, `removePartner`, `transferOwnership`, `delete`, dan `leave`. Memakai membership terbaru dan household aktif, tanpa bypass system admin. Policy tidak mengeksekusi mutasi; action berikutnya wajib memanggil Gate serta memvalidasi target partner, token/kapasitas, dan transaksi sesuai use-case. Belum ada route/aksi admin atau domain yang dibuka.

**Fondasi action LOG-021:** `RemoveHouseholdMember::handle($actor, $household, $target)` mengotorisasi `leave` jika actor adalah target, atau `removePartner` jika berbeda, dan hanya menghapus membership partner pada household tersebut. `TransferHouseholdOwnership::handle(...)` mengotorisasi owner dan hanya menerima partner aktif pada household yang sama; menukar role tanpa mengubah identitas slot/warna/joined_at. Household aktif dikunci sebelum Gate dan query membership dalam transaksi; audit wajib ikut transaksi. Tidak menghapus akun atau data domain. Belum ada caller UI/route; CORE-14 tetap tertunda. Lock transaksi ini bukan bukti konkurensi kedua DB.

### 2.3 Registrasi
- Registrasi publik **dimatikan** (`REGISTRATION_MODE=invite`).
- Alur A (household baru): System Admin membuat **invite code household baru** → penerima daftar → menjadi `owner` household baru.
- Alur B (partner): Owner mengundang email partner → link bertoken (berlaku 7 hari, sekali pakai) → penerima daftar/login → menjadi `partner`.
- Satu user **hanya boleh** tergabung di **satu** household (unik `user_id` di `household_members`).

### 2.4 Isolasi Data (WAJIB)
- Setiap tabel domain memiliki kolom `household_id` (FK, index).
- Model domain memakai trait `BelongsToHousehold`:
  - Global scope `where household_id = currentHousehold()->id`.
  - Event `creating` mengisi `household_id` otomatis.
- Setiap Policy memeriksa `$model->household_id === $user->household_id`.
- **Setiap modul WAJIB punya test isolasi**: user household A mendapat 404 saat mengakses resource household B (by ID langsung, via Livewire action, dan via ekspor).
- Job queue, scheduler, dan command yang memproses data household WAJIB membawa household ID secara eksplisit dan menginisialisasi konteks tenant untuk setiap pekerjaan. Jangan mengandalkan konteks request atau global scope sebagai satu-satunya batas isolasi; konteks yang hilang harus gagal tertutup.

**Status implementasi (LOG-024, diperbarui LOG-030):** `App\Core\Support\HouseholdContext` (singleton, `use`/`forget`/`current`/`currentOrNull`, gagal tertutup), helper `currentHousehold()`, `BelongsToHousehold` + `HouseholdScope`, dan auto-fill/validasi `household_id` tersedia. Trait baru diterapkan pada `HouseholdFinancePeriodSetting`; `HouseholdMember` masih di-scope eksplisit oleh policy/action, dan `Activity` tidak memiliki kolom `household_id`. Middleware `UseHouseholdContext` memasang konteks untuk request `web` dari membership user terautentikasi, tetapi job/command belum punya bootstrap konteks dan test isolasi lintas modul belum ada.

---

## 3. Tech Stack (Approved List)

| Layer | Pilihan | Catatan |
|---|---|---|
| Bahasa | PHP 8.5.x | Baseline local, CI, dan produksi; stabil terverifikasi 8.5.12 |
| Framework | Laravel 13.x (`^13.38`) | Stabil terverifikasi 13.38.0; kunci resolusi di `composer.lock` |
| UI interaktif | Livewire 4.x + Alpine.js bawaan Livewire | Stabil Livewire 4.4.8; jangan memuat Alpine kedua kali. Lihat ADR-002/011 |
| CSS | Tailwind CSS 4.x (via `@tailwindcss/vite`) | Stabil 4.3.3; konfigurasi tema CSS-first (`@theme`) |
| Build | Vite 8.x + `laravel-vite-plugin` 3.x | Stabil 8.3.5 / 3.2.0; Node.js 24 LTS (terverifikasi 24.22.1), npm + lockfile |
| Auth | Laravel Fortify 1.x + form Livewire 4 / komponen Blade proyek | Stabil 1.41.0; invite-only. Menggantikan Breeze; tanpa Volt/Flux. Lihat ADR-011 |
| DB | **SQLite** (default) / **MariaDB 10.11+** (opsional) | Lihat §6 |
| Queue | `database` driver | |
| Scheduler | `php artisan schedule:work` (service Linux terkelola) | Tanpa Docker; lihat §13 |
| Recurrence | `rlanvin/php-rrule` 3.x | Stabil 3.0.0; subset RRULE, lihat §7.4 |
| Audit log | `spatie/laravel-activitylog` 5.x | Stabil 5.1.3 |
| File/foto | `spatie/laravel-medialibrary` 11.x | Stabil 11.23.10; disk `local` privat |
| Web Push | `laravel-notification-channels/webpush` 13.x | Stabil 13.0.1; VAPID; membutuhkan Laravel >=13.13 pada jalur 13.x |
| iCalendar | `spatie/icalendar-generator` 3.x | Stabil 3.3.1 |
| Excel/CSV | `maatwebsite/excel` 4.x | Stabil 4.0.6; ekspor laporan |
| Backup | `spatie/laravel-backup` 10.x | Stabil 10.3.3; verifikasi operasi backup/restore pada Linux |
| Chart | ApexCharts 8.x (npm) | Stabil 8.5.0; tinjau lisensi upstream sebelum distribusi |
| Test | Pest 5.x + `pestphp/pest-plugin-laravel` 5.x | Stabil 5.3.3 / 5.0.1; plugin membutuhkan Laravel >=13.23; Pest ini membutuhkan Boost >=2.6 jika terpasang |
| Test lifecycle | `mockery/mockery` 1.6.x (development saja) | Disetujui reviewer untuk `RefreshDatabase` Laravel; terpasang 1.6.15, lihat addendum ADR-011 |
| Kualitas kode | Laravel Pint 1.x, Larastan 3.x level 6 | Stabil 1.32.1 / 3.13.2; Larastan memakai PHPStan 2.x |
| AI tooling (development saja) | Laravel Boost 2.x (`^2.10.3`) | Disetujui untuk CORE-01; MCP/guidelines untuk Copilot VS Code, bukan fitur AI aplikasi |

**Aturan paket:** AI agent **JANGAN** menambah paket Composer/npm di luar daftar ini tanpa membuat ADR dan disetujui reviewer.

### 3.1 Verifikasi versi dan kompatibilitas (2026-10-26)

Versi stabil di atas adalah snapshot dari metadata Packagist/npm dan rilis PHP/Node, bukan bukti dependency sudah terpasang. Gunakan major yang disetujui, resolusi stabil, serta `composer.lock` / `package-lock.json`; jangan mengambil prerelease atau major berikutnya secara otomatis. Manifest mockup tetap terpisah dan Tailwind 4.3.3-nya sudah sesuai.

Laravel 13 mendukung PHP 8.3–8.5. Baseline proyek dinaikkan ke PHP 8.5 agar local/CI/produksi konsisten; Pest 5, Activitylog 5, dan iCalendar Generator 3 terbaru juga membutuhkan minimal PHP 8.4. SQLite tetap default dan MariaDB 10.11+ tetap batas kompatibilitas yang diuji, bukan klaim versi DB terbaru; perubahan mesin DB produksi tidak termasuk pembaruan dependency aplikasi ini.

Breeze 2.4.2 mengizinkan Laravel 13 pada constraint Composer, tetapi installer Livewire-nya masih memasang Livewire `^3.6.4`, Volt `^1.7.0`, dan Tailwind `^3.1.0`. Karena itu jangan menjalankan `breeze:install livewire` untuk stack ini. Reviewer menyetujui Fortify + Livewire 4 + Blade proyek sendiri, tanpa mengambil seluruh starter kit beserta dependency tambahannya. Registrasi publik wajib dinonaktifkan; penerimaan undangan ditangani Core. Jangan mengaktifkan TOTP/passkey bawaan Fortify; email OTP opsional tetap mengikuti SEC-2 dan Q-M12.

### 3.2 Laravel Boost

Boost 2.10.3 menyatakan dukungan PHP `^8.2` dan Laravel `^11.45.3|^12.41.1|^13.0`, sehingga constraint-nya cocok dengan PHP 8.5 / Laravel 13 dan memenuhi batas Pest 5 (Boost >=2.6). Dokumentasi resmi menyediakan integrasi GitHub Copilot di VS Code. Ini verifikasi metadata, **belum** pembuktian resolusi dependency transitif atau koneksi MCP runtime.

Saat CORE-01, setelah scaffold Laravel tersedia:

```shell
composer require laravel/boost:"^2.10.3" --dev
php artisan boost:install
```

**Status persiapan 2026-10-26:** atas instruksi reviewer, manifest/lockfile root dan dependency PHP/npm sudah disiapkan tanpa scaffold atau coding aplikasi. Boost sudah terpasang sebagai paket development; `boost:install` dan MCP belum dijalankan. Resolusi Composer dan pemeriksaan platform lokal lulus; ini tidak menyelesaikan CORE-01. Lihat [setup dan rencana development](development-plan.md) untuk langkah restore dan pemeriksaan yang masih tertunda.

**Pembaruan scaffold (LOG-009):** reviewer kemudian mengizinkan scaffold Laravel standar dan server lokal saja. Welcome/health, test boot, serta build aset sudah berjalan; belum ada fitur Family Hub, migration/seed yang dijalankan, CI, atau konfigurasi Boost MCP. Model/migration default skeleton bukan implementasi skema ULID/household yang disetujui. Detail dan perintah server tersedia di [development plan](development-plan.md).

**Checkpoint fondasi (LOG-010):** reviewer mengizinkan kelanjutan sesuai plan. Placeholder Core/Modules, flag modul nonaktif, konfigurasi Pint/Larastan, test fondasi, dan workflow CI kedua DB tersedia. Test/lint/analisis/build lokal serta Boost stdio/tool dokumentasi lulus; workflow belum berjalan di GitHub dan koneksi UI Copilot belum terverifikasi. CORE-01 masih parsial; lihat [logbook](logbook.md) untuk bukti dan batasnya.

**Checkpoint auth (LOG-011):** Fortify login/logout dan konfirmasi kata sandi terhubung ke Blade/Livewire dengan locale Indonesia dan Alpine bawaan. Form native memakai validasi/session Fortify, bukan implementasi auth ganda di Livewire. Registrasi/reset password/TOTP/passkeys tetap nonaktif; undangan/household belum dibuat. Test database terisolasi, lint, analisis, build, dan pemeriksaan browser 360px light/dark lulus; database aplikasi lokal tidak dimigrasikan atau di-seed. CORE-01 tetap parsial sampai CI kedua DB dan koneksi UI Copilot terbukti.

**Checkpoint auth undangan dan reset (LOG-036):** registrasi publik, TOTP, dan passkey tetap nonaktif, tetapi `Features::resetPasswords()` kini aktif karena reviewer memutuskan lupa kata sandi memakai layar reset yang sama dengan pembuatan kata sandi undangan. Halaman login menautkan forgot-password; email undangan membawa tautan `invitation.show`; tautan itu membuat akun household (atau meminta akun lama masuk lebih dulu) dan `AcceptInvitation` menolak token bila email akun tidak cocok. Permintaan reset memberi jawaban yang sama untuk email terdaftar maupun tidak. Verifikasi lokal 193 test/1767 assertion dan browser 360px/1280px light/dark lulus; CI remote belum terverifikasi. Rate limit undangan ditambahkan pada LOG-040; antrean mail dan Alur A admin belum ada.

Pilih GitHub Copilot pada installer, tinjau diff konfigurasi MCP/guidelines/skills, lalu mulai server melalui VS Code `MCP: List Servers` dan uji koneksi serta tool dokumentasinya. Pertahankan instruksi Family Hub dan skills yang sudah ada; jangan membiarkan hasil generate mengganti aturan domain, invite-only, isolasi household, atau batas dependency. Tool yang menjalankan kode/query hanya dipakai pada development dengan data sintetis, bukan database produksi atau secret. Jangan mengekspos server MCP ke jaringan publik. Boost tidak diperlukan saat runtime produksi (`composer install --no-dev`) dan tidak mengizinkan pengiriman struk/data keluarga ke layanan AI.

**Keputusan transport terbaru (LOG-013):** reviewer mengizinkan client stdio terminal sebagai pengganti koneksi UI Copilot yang belum dapat diaktifkan. Inisialisasi, daftar tool, dan `search-docs` kembali lulus; gate koneksi Boost dipenuhi lewat jalur ini, tanpa mengklaim koneksi native UI. Jalankan saat diperlukan dan hentikan child setelah selesai. Keputusan ini menggantikan kewajiban koneksi UI pada CORE-01, bukan kewajiban CI kedua DB atau acceptance reviewer; lihat addendum ADR-011.

**Checkpoint CI (LOG-014):** SQLite dipilih untuk development lokal; MariaDB tetap target kompatibilitas CI. Fondasi dipublikasikan dengan izin reviewer pada branch `core-01-foundation` (`ffe4afe`), bukan main. [GitHub CI run 40268711517](https://github.com/shadri-zulkurniadi/Net/actions/runs/40268711517) lulus kedua job beserta install/platform/build/test/Pint/Larastan. Gate teknis CORE-01 terpenuhi; acceptance reviewer masih diperlukan sebelum CORE-03. Ini bukan bukti skema household, produksi, atau UAT.

Sebelum menandai CORE-01 selesai, wajib jalankan resolusi Composer penuh pada PHP 8.5 beserta ekstensi yang dibutuhkan, `composer check-platform-reqs`, build Vite, test SQLite/MariaDB, Pint, Larastan, dan smoke test integrasi Boost. Bila ada constraint transitif yang gagal, laporkan dan selesaikan secara eksplisit; jangan memakai `--ignore-platform-reqs`. Sumber dan keputusan lengkap: [ADR-011](adr/ADR-011-current-stack.md).

---

**Keputusan sementara LOG-023 (2026-10-26):** reviewer memilih SQLite dahulu dan menunda MariaDB. CI aktif hanya menjalankan SQLite; kewajiban matriks SQLite/MariaDB dalam task/DoD dokumen ini ditunda sampai MariaDB diaktifkan kembali dan diverifikasi. Jangan menyatakan kompatibilitas perubahan baru dengan MariaDB atau menyembunyikan run gagal sebagai kelulusan. Branch fondasi aman di-merge ke main; checkpoint CORE-03/04 pada `core-03-support` belum di-merge. Tidak ada perubahan kontrak domain atau izin deployment.

**Pembaruan LOG-025 (2026-10-26):** reviewer menyatakan CORE-03 selesai dan mengizinkan MariaDB kembali ke CI. Workflow `.github/workflows/ci.yml` dipulihkan ke matriks SQLite/MariaDB 10.11 dengan service database dan `pdo_mysql`; step SQLite tetap in-memory. Penundaan LOG-023 berakhir hanya untuk gate CI, bukan bukti kompatibilitas: run baru pada `core-03-support` belum ada, kegagalan MariaDB `40286560448` belum didiagnosis, dan CORE-04/bootstrap/konkurensi tetap parsial. Tidak ada merge ke main, migration/seed aplikasi, atau izin deploy.

**Pembaruan LOG-026 (2026-10-26):** atas izin reviewer, `core-03-support` di-push dan `main` di-fast-forward ke `bcbbe86`; kedua ref identik. Main kini memuat checkpoint CORE-03A/03B/04 beserta skema household, tetapi hasil CI remote untuk tip itu belum terverifikasi. CORE-04 tetap parsial dan tidak ada izin deploy/UAT atau migration DB aplikasi.

**Pembaruan LOG-029 (2026-10-26):** reviewer melaporkan job SQLite dan MariaDB lulus untuk `6d6f7bc` setelah perbaikan collation/index (LOG-027/LOG-028). Gate kompatibilitas MariaDB untuk skema ini terpenuhi; ini bukan bukti UAT, deploy, atau penyelesaian CORE-04 (bootstrap/context/admission/konkurensi masih terbuka).

## 4. Arsitektur — Modular Monolith

### 4.1 Struktur Folder
```
app/
├── Core/
│   ├── Auth/                 # invite, registrasi
│   ├── Household/            # Household, HouseholdMember, Invitation, Policies, Actions
│   ├── Calendar/             # CalendarSource (contract), CalendarItem (DTO), CalendarAggregator
│   ├── Alerts/               # AlertSource (contract), Alert (DTO), AlertAggregator
│   ├── Reminders/            # Reminder model, ReminderService, Remindable (contract), DispatchDueReminders job
│   ├── Notifications/        # Notification classes, channels
│   ├── Recurrence/           # RecurrenceRule wrapper + presets
│   ├── Support/              # Money, PeriodResolver, BelongsToHousehold trait, helpers
│   └── Livewire/             # Dashboard, Settings, Admin
├── Modules/
│   ├── Finance/
│   │   ├── Actions/  Models/  Enums/  Policies/  Events/  Listeners/
│   │   ├── Jobs/  Livewire/  Data/  Calendar/  Alerts/
│   │   ├── Contracts/        # API publik modul untuk modul lain (FinanceGateway)
│   │   └── FinanceServiceProvider.php
│   ├── Organizer/  (struktur sama)
│   └── Asset/      (struktur sama)
resources/views/
├── components/               # komponen Blade global (x-card, x-money, dll)
├── layouts/
├── core/
└── modules/{finance,organizer,asset}/
routes/
├── web.php                   # core
└── modules/{finance,organizer,asset}.php   # di-load oleh ServiceProvider modul
database/
├── migrations/               # nama file: YYYY_MM_DD_HHMMSS_{core|fin|org|ast}_create_xxx_table.php
└── seeders/
tests/
├── Feature/{Core,Finance,Organizer,Asset}/
└── Unit/
docs/
├── 00-MASTERPLAN.md
├── 01-MODULE-FINANCE.md
├── 02-MODULE-ORGANIZER.md
├── 03-MODULE-ASSET.md
├── adr/ADR-NNN-judul.md
├── runbook/{deploy,backup-restore}.md
├── development-plan.md
├── logbook.md
└── uat-go-live.md
```

### 4.2 Aturan Layer
| Layer | Tanggung jawab | Larangan |
|---|---|---|
| **Livewire component** | Menerima input, validasi form, memanggil Action, render view | JANGAN berisi logika bisnis / query kompleks |
| **Action** (`app/Modules/X/Actions/DoSomething.php`) | Satu use-case, method `handle(...)`, dibungkus `DB::transaction` jika menulis >1 tabel | JANGAN memanggil Action modul lain secara langsung |
| **Model** | Relasi, casts, scopes, accessor sederhana | JANGAN memanggil service eksternal |
| **Policy** | Otorisasi | |
| **Event/Listener** | Integrasi lintas modul (asinkron atau sinkron) | |
| **Contracts/** | Satu-satunya pintu yang boleh dipanggil modul lain | |

### 4.3 Komunikasi Lintas Modul
- Modul A **hanya boleh** memanggil modul B melalui interface di `app/Modules/B/Contracts/` atau dengan mendengarkan Event modul B.
- **JANGAN** query tabel modul lain secara langsung (misal Asset melakukan `Transaction::create`). Gunakan `FinanceGateway`.
- Relasi FK antar modul diperbolehkan **hanya** sebagai kolom nullable `xxx_id` + polymorphic `source_type/source_id`, tanpa eager-load lintas modul di view.

### 4.4 Urutan Ketergantungan Modul
```
Core  ←  Finance  ←  Organizer (pakai FinanceGateway untuk belanja)
                 ←  Asset     (pakai FinanceGateway untuk biaya servis & rencana perawatan)
```
Organizer dan Asset **WAJIB** tetap berfungsi jika fitur integrasi Finance dinonaktifkan per household (setting `finance_integration_enabled`, default `true`).
Saat integrasi dinonaktifkan, batalkan lebih dulu seluruh PlannedItem Finance yang masih `planned` dan berasal dari Organizer/Asset ketika gateway masih aktif; baru setelah pembatalan berhasil ubah setting ke `false`. Jika pembatalan gagal, jangan matikan integrasi dan tampilkan error. `NullFinanceGateway` tidak membuat/mengubah data, sehingga tidak dapat dipakai untuk membersihkan rencana yang sudah ada.

---

## 5. Core Module — Spesifikasi

**Urutan implementasi disetujui reviewer (LOG-019):** pecah CORE-03 menjadi 03A (utilitas/perhitungan/komponen), CORE-04 (skema/model household), lalu 03B (integrasi tenant dan factory resolver household). Ini mengatasi dependensi melingkar antara support tenant dan model household; 03A bukan klaim seluruh CORE-03 atau gap browser selesai. CORE-04 parsial: User/sessions kini memakai ULID, model Household/HouseholdMember/history dan enum MemberRole tersedia. Migrasi awal User boleh disesuaikan karena belum ada database aplikasi lokal/produksi yang dimigrasikan; jangan menerapkan perubahan ini sebagai upgrade in-place database integer yang sudah berisi data. Skema baru hanya diuji pada SQLite in-memory; policy, action membership/slot, konkurensi kedua DB dan bootstrap histori masih tertunda. Tidak ada akun baru atau undangan.

### 5.1 Tabel Core

**`users`**
| Kolom | Tipe | Aturan |
|---|---|---|
| id | ULID PK | |
| name | string(100) | |
| email | string unique | |
| password | string | |
| is_system_admin | bool default false | |
| locale | string(5) default `id` | `id` / `en` |
| notify_push | bool default true | |
| notify_email | bool default true | |
| quiet_hours_start | time nullable default `21:00` | |
| quiet_hours_end | time nullable default `06:00` | |
| daily_digest_time | time nullable default `06:00` | null = tidak ada digest |
| remember_token, email_verified_at, timestamps | | |

**`households`**
| Kolom | Tipe | Aturan |
|---|---|---|
| id | ULID | |
| name | string(100) | contoh "Keluarga Shadri" |
| timezone | string default `Asia/Jakarta` | |
| finance_period_start_day | tinyint default 1 | 1–28. Hari mulai periode keuangan aktif; perubahan dijadwalkan mulai batas periode berikutnya dan kolom ini diperbarui saat perubahan berlaku. Lihat `PeriodResolver` |
| finance_integration_enabled | bool default true | |
| created_by | ULID FK users | |
| timestamps, soft deletes | | |

**`household_members`**
| Kolom | Tipe | Aturan |
|---|---|---|
| id | ULID | |
| household_id | FK | |
| user_id | FK **unique** | 1 user = 1 household |
| role | string enum `MemberRole` (`owner`,`partner`) | |
| color | string(7) | warna di kalender, default owner `#059669`, partner `#7C3AED` |
| joined_at | datetime | |
| slot | tinyint | `1` atau `2`; membatasi kapasitas household di database |
| unique | (`household_id`,`slot`) | |
| check | `slot IN (1,2)` | |

Aturan: slot dipilih saat `AcceptInvitation` dalam transaksi. Unique constraint `(household_id, slot)` dan check `slot IN (1,2)` menjadi batas atomik maksimal dua member pada SQLite dan MariaDB. Konflik slot saat dua penerimaan bersamaan harus ditangani sebagai household penuh atau dicoba pada slot lain, bukan dilewati.

**`household_invitations`**
| Kolom | Tipe |
|---|---|
| id, household_id (nullable untuk invite household baru), email, type (`new_household`,`partner`), token_hash, invited_by, expires_at, accepted_at, revoked_at, timestamps |

**Status implementasi (LOG-034, diperbarui LOG-040):** `App\Core\Models\HouseholdInvitation` dan migrasinya mengikuti kolom di atas: `household_id` nullable, `email`, `type` enum `InvitationType`, `token_hash` SHA-256 unik, `invited_by`, `expires_at`, `accepted_at`, dan `revoked_at` (revoke menandai baris, tidak menghapus). Alur B (partner) kini mencakup pengiriman email bertautan `invitation.show`, halaman penerimaan yang membuat akun atau meminta akun lama masuk, dan penolakan token bila email akun tidak cocok dengan email undangan. Pembuatan undangan dibatasi 10 per jam per aktor sesuai SEC-3 (LOG-040), dan hanya satu undangan boleh aktif per household: undangan baru mencabut undangan pending sebelumnya (LOG-041). Alur A (admin membuat household baru) belum punya aksi/route dan `AcceptInvitation` menolak undangan `new_household`, tetapi akun admin sistem kini ditolak saat menerima undangan maupun membuat household (LOG-041); antrean mail juga belum ada.

**`reminders`** (dipakai semua modul)
| Kolom | Tipe | Aturan |
|---|---|---|
| id | ULID | |
| household_id | FK | |
| remindable_type / remindable_id | morph | sumber (planned item, task, event, maintenance plan, dll) |
| key | string(50) | pembeda bila 1 sumber punya >1 reminder (`h-3`, `h0`, `h-30`) |
| title | string | |
| body | string nullable | |
| url | string | link ke halaman sumber |
| remind_at | datetime (UTC) | |
| recipient_user_id | FK nullable | null = semua member; menentukan audience logical reminder |
| status | enum `scheduled`,`cancelled` | status logical reminder |
| unique | (`remindable_type`,`remindable_id`,`key`) | idempoten |

**`reminder_deliveries`** (satu delivery per penerima)
| Kolom | Tipe | Aturan |
|---|---|---|
| id | ULID | |
| household_id | FK | |
| reminder_id | FK reminders | |
| recipient_user_id | FK users | |
| remind_at | datetime (UTC) | waktu kirim efektif untuk penerima ini; dapat ditunda oleh quiet hours |
| status | enum `pending`,`sent`,`cancelled`,`failed` | hasil delivery per penerima |
| attempt_count | unsigned smallint default 0 | untuk retry |
| next_attempt_at | datetime nullable | backoff retry |
| sent_at | datetime nullable | |
| last_error_code | string nullable | jangan simpan isi dokumen atau data sensitif di error |
| created_by, timestamps | FK nullable / timestamps | |
| unique | (`reminder_id`,`recipient_user_id`) | idempoten |

Jika `recipient_user_id` pada reminder null, buat delivery untuk tiap member household. Member baru juga mendapat delivery untuk reminder household yang masih `scheduled`. Mengubah/membatalkan reminder menyelaraskan delivery yang belum terkirim; quiet hours dan kegagalan satu penerima tidak mengubah delivery penerima lain.

Tabel lain: `notifications` (Laravel), `push_subscriptions` (paket webpush), `activity_log`, `media`, `jobs`, `failed_jobs`, `cache`, `sessions`.

**Audit backend LOG-021:** `activity_log` mengikuti schema Spatie Activitylog 5 dengan PK ULID serta `subject`/`causer` nullable ULID morphs, log_name, description, event, attribute_changes/properties JSON dan timestamps. Model `App\Core\Models\Activity` memakai HasUlids; buffering dimatikan agar audit action tersimpan bersama transaksi, bukan setelah response. Event membership menyimpan household sebagai subject, actor sebagai causer, dan ID target/pemilik lama-baru dalam properties, bukan password/email. Belum ada layar/query audit publik; caller audit CORE-14 nanti wajib mengotorisasi dan membatasi subject household.

**`household_finance_period_settings`** menyimpan riwayat perubahan hari mulai periode: `id`, `household_id`, `start_day` (1–28), `effective_on` (tanggal lokal awal periode saat perubahan berlaku), `created_by`, timestamps; unique (`household_id`,`effective_on`). Buat baris awal saat household dibuat; untuk tanggal yang lebih lama dari baris awal, gunakan setting awal tersebut. Perubahan efektif pada batas periode berikutnya menurut pengaturan aktif; bila masih ada perubahan yang menunggu berlaku, preview ulang lalu ganti jadwal pending tersebut, bukan menambah jadwal bertumpuk. Periode transisi dari batas tersebut sampai kemunculan berikutnya hari baru dapat lebih pendek/panjang; `PeriodResolver` harus memakai riwayat ini agar periode historis tidak berubah.

### 5.2 Fitur Core (Phase 0)
1. Login, logout, reset password, registrasi via invite.
2. Onboarding household: nama household, zona waktu, hari mulai periode keuangan, undang partner.
3. Halaman **Beranda (Dashboard)**: lihat §9.3.
4. **Pengaturan**: profil, notifikasi (push/email/quiet hours/digest), household, member.
5. **Admin** (`is_system_admin`): daftar household (nama, jumlah member, tanggal dibuat, ukuran storage), buat invite household baru, nonaktifkan user. Tanpa akses data domain, dan bukan anggota household mana pun (LOG-041).
6. **Notification center** (ikon lonceng): daftar notifikasi in-app, tandai dibaca.
7. **Reminder engine** & **Calendar/Alert aggregator** (kontrak, belum ada UI kalender — UI di Organizer).
8. PWA shell (manifest, service worker, install prompt, offline page).
9. Audit log viewer sederhana di Pengaturan → "Riwayat Aktivitas" (100 terakhir, filter modul).

---

## 6. Database Rules (SQLite & MariaDB)

| # | Aturan |
|---|---|
| DB-1 | Default `DB_CONNECTION=sqlite`, file `database/database.sqlite`, **WAJIB** mode WAL (`PRAGMA journal_mode=WAL`, `busy_timeout=5000`) diset di `AppServiceProvider`. |
| DB-2 | Kode **WAJIB** kompatibel dengan kedua DB. Gunakan Eloquent/Query Builder. JANGAN pakai fungsi SQL spesifik (`DATE_FORMAT`, `strftime`, `JSON_EXTRACT`, `IFNULL` vs `COALESCE` → pakai `COALESCE`). |
| DB-3 | Agregasi per bulan/periode dilakukan dengan filter rentang tanggal (`whereBetween`) yang dihitung oleh `PeriodResolver`, bukan dengan fungsi tanggal SQL. |
| DB-4 | Primary key: **ULID** (`$table->ulid('id')->primary()`, trait `HasUlids`). |
| DB-5 | Uang: **`bigInteger`** dalam rupiah penuh (tanpa sen). JANGAN pakai float/decimal. |
| DB-6 | Enum disimpan sebagai `string` + PHP backed enum di `Enums/`. JANGAN pakai tipe `enum` SQL. |
| DB-7 | Tanggal saja → `date`. Waktu → `dateTime` disimpan **UTC**, ditampilkan di zona household. |
| DB-8 | JSON → kolom `text` + cast `array`. JANGAN query isi JSON di SQL. |
| DB-9 | Semua tabel domain: `household_id`, `created_by` (nullable FK users), `timestamps`, `softDeletes` (kecuali tabel pivot/log). |
| DB-10 | Index wajib: `household_id`, semua FK, dan kolom tanggal yang dipakai filter. |
| DB-11 | CI menjalankan test suite di SQLite **dan** MariaDB. |
| DB-12 | Prefix tabel per modul: core tanpa prefix, `fin_`, `org_`, `ast_`. |

---

## 7. Kontrak & Utilitas Bersama

### 7.1 Money
```php
// app/Core/Support/Money.php
Money::format(1250000);          // "Rp 1.250.000"
Money::format(-50000);           // "-Rp 50.000"
Money::parse('1.250.000');       // 1250000  (menerima "1250000", "1.250.000", "Rp 1.250.000", "1,25jt" -> 1250000, "500rb" -> 500000)
```
Parser memakai aritmetika string/integer, bukan float. Titik hanya pemisah ribuan dengan grup tiga digit; koma merupakan pecahan desimal dan hasil akhir harus Rupiah bulat (misalnya `0,000001jt` = 1). Prefix `Rp` dan suffix `rb`/`jt` tidak peka huruf besar; tanda minus mendahului prefix (`-Rp 50.000`). Input tidak valid, pecahan Rupiah, dan overflow integer melempar `InvalidArgumentException`, bukan dibulatkan atau dikembalikan sebagai nol. Form pemanggil wajib mengubah exception ini menjadi pesan validasi terlokalisasi.

Blade: `<x-money :amount="$x" />` (merah untuk negatif bila `colored`). Input: `<x-input-money id="amount" :label="__('finance.amount')" wire:model="amount" required />` (format ribuan saat mengetik, nilai integer/null ke Livewire; pemanggil menyediakan label terlokalisasi).

**Kontrak input uang disetujui reviewer (LOG-018):** input interaktif dibatasi integer ±9.007.199.254.740.991 (Number.MAX_SAFE_INTEGER) untuk mencegah pembulatan JSON/JavaScript. Parser/formatter PHP Money tetap mendukung signed 64-bit. `x-input-money` memakai Alpine bawaan + `x-modelable` dengan binding Livewire pada wrapper; menerima format parser biasa/Rp/rb/jt, memformat nominal valid, menampilkan error terlokalisasi dan memblokir native form saat tidak valid. Input kosong/tidak valid mengirim null, bukan nominal valid lama. Form server wajib memakai `required` (atau `nullable` untuk opsional) bersama `App\Rules\SafeRupiah`; tipe string/float dan integer di luar batas ditolak. Rule bukan otorisasi atau pengganti validasi bisnis. Error validasi server ditampilkan oleh form pemanggil; komponen menyediakan pesan validasi browser. Gunakan properti Livewire nullable dan muat Vite app.js serta Livewire/Alpine. Input memakai keyboard teks agar minus, koma, dan suffix tetap dapat diketik. `npm test` menjalankan test JS Node bawaan tanpa dependency baru dan tercantum pada workflow CI.

**Checkpoint komponen (LOG-017):** `x-money` sudah tersedia, menerima integer `amount`, boolean `colored`, dan atribut HTML pemanggil. `x-date` menerima `value` berupa string tanggal kalender ketat `YYYY-MM-DD` (tidak digeser timezone) atau `DateTimeInterface` untuk instant yang dikonversi ke `timezone` eksplisit; default `Asia/Jakarta` harus diganti timezone household oleh pemanggil saat household tersedia. Label mengikuti locale aplikasi (`27 Okt 2026` untuk Indonesia); markup `<time datetime="...">` mempertahankan tanggal/offset mesin. String relatif/timestamp bebas ditolak: berikan objek tanggal untuk instant. `x-input-money` dan `x-datetime` belum diimplementasikan.

### 7.2 PeriodResolver (periode keuangan)
- Household punya `finance_period_start_day` (S).
- Batas periode dihitung dari `household_finance_period_settings`; periode historis tidak dihitung ulang saat setting berubah.
  - S=1, periode `2026-11-01` → 1 Nov – 30 Nov.
  - S=25, periode `2026-11-25` → 25 Nov – 24 Des. (Label UI: "Nov 2026 (25 Nov – 24 Des)")
  - Kunci periode adalah tanggal mulai aktual `YYYY-MM-DD`, bukan bulan, agar periode transisi dan perubahan hari mulai tetap unik. Label UI menampilkan rentang tanggal.
  - Pengaturan baru berlaku pada batas periode berikutnya menurut setting aktif; sampai tanggal mulai baru pertama, periode transisi ditampilkan sesuai rentang aktual. Preview wajib menunjukkan dampaknya sebelum user mengonfirmasi. Anggaran historis tetap pada rentang lamanya. Anggaran mendatang yang terdampak tidak dipindahkan otomatis: tampilkan daftar rentang lama dan sediakan aksi eksplisit untuk menyalin jumlahnya ke periode baru; anggaran lama tidak dihitung sebagai anggaran periode baru.
  - `fromKey()` hanya menerima tanggal yang merupakan batas awal periode valid; `forDate()` menggunakan riwayat setting untuk memilih rentang yang mencakup tanggal.
- API:
```php
$p = PeriodResolver::for($household);
$p->current();                    // Period { key:'2026-11-01', start: Carbon, end: Carbon }
$p->fromKey('2026-11-01');
$p->forDate(Carbon $date);        // periode yang memuat tanggal tsb
$p->previous($period); $p->next($period);
```
- **Semua** fitur Finance yang bicara "bulan ini" WAJIB memakai PeriodResolver.

**Checkpoint CORE-03 (LOG-016):** mesin perhitungan tersedia melalui `new PeriodResolver($history, $timezone)` dengan history berurutan berbentuk `list<array{effective_on: string, start_day: int}>`. History kosong, tanggal tidak valid, hari di luar 1-28, duplikasi/urutan terbalik, dan perubahan di luar batas periode lama ditolak eksplisit. Baris pertama berlaku juga untuk tanggal sebelum pembuatannya; baris perubahan berikutnya menciptakan batas transisi, lalu mengikuti hari baru berikutnya. `Period` memakai CarbonImmutable dalam timezone household, awal hari dan akhir hari inklusif; key tanggal lokal. API lain (`current/fromKey/forDate/previous/next`) sudah diuji mandiri.

**Checkpoint tenant (LOG-024):** `PeriodResolver::for($household)` membaca histori dari `household_finance_period_settings` dan timezone household; household parameter adalah batas tenant sehingga factory tidak bergantung pada konteks ambient dan aman dipakai job. Histori kosong tetap ditolak. Tenant context/scope tersedia untuk model domain; penerapan ke seluruh model modul, bootstrap histori saat household dibuat, preview perubahan setting, dan pemindahan budget belum diimplementasikan.

### 7.3 Calendar Contract
```php
namespace App\Core\Calendar;

interface CalendarSource {
    public function key(): string;                 // 'finance.planned', 'organizer.events', ...
    public function label(): string;               // "Tagihan & Cicilan"
    /** @return iterable<CalendarItem> */
    public function items(Household $h, CarbonImmutable $from, CarbonImmutable $to): iterable;
}

final readonly class CalendarItem {
    public function __construct(
        public string $uid,            // unik & stabil, contoh "fin-planned-01J...@familyhub"
        public string $sourceKey,
        public string $title,
        public CarbonImmutable $start,
        public ?CarbonImmutable $end,
        public bool $allDay,
        public string $color,          // hex
        public string $url,            // link ke detail
        public ?string $status = null, // 'planned','done','overdue',...
        public ?string $description = null,
        public ?string $memberId = null, // null = keduanya
    ) {}
}
```
- Modul mendaftarkan source: `$this->app->tag([PlannedItemCalendarSource::class], 'calendar.sources');`
- `CalendarAggregator::between($household, $from, $to, ?array $sourceKeys)` menggabungkan & mengurutkan. Dipakai oleh: Dashboard "Hari Ini", UI Kalender (Organizer), ekspor .ics (Organizer).
- Untuk `allDay=true`, `start` dan `end` merepresentasikan tanggal lokal household pada tengah malam; `end` bersifat eksklusif. Ekspor iCalendar menggunakan DATE, bukan menggeser tanggal berdasarkan konversi UTC.
- Rentang maksimal per panggilan: 400 hari.

### 7.4 Recurrence
- Disimpan sebagai string RRULE (RFC 5545) di kolom `rrule` + `dtstart` (date).
- **Subset yang didukung UI** (preset):
  | Preset UI | RRULE |
  |---|---|
  | Setiap hari | `FREQ=DAILY` |
  | Setiap minggu pada hari X | `FREQ=WEEKLY;BYDAY=MO` |
  | Setiap 2 minggu | `FREQ=WEEKLY;INTERVAL=2;BYDAY=..` |
  | Setiap bulan tanggal N | `FREQ=MONTHLY;BYMONTHDAY=N` (N>28 → otomatis `BYMONTHDAY=-1` jika user pilih "akhir bulan") |
  | Setiap N bulan | `FREQ=MONTHLY;INTERVAL=N;BYMONTHDAY=..` |
  | Setiap tahun | `FREQ=YEARLY` |
  | Berakhir | `UNTIL=` atau `COUNT=` (opsional) |
- Wrapper `App\Core\Recurrence\Recurrence`: `occurrencesBetween(rrule, dtstart, from, to): array<CarbonImmutable>`, `next(rrule, dtstart, after): ?CarbonImmutable`, `humanize(rrule, locale): string` ("Setiap bulan tanggal 10").

### 7.5 Reminder Contract
```php
interface Remindable {                       // diimplementasikan model sumber
    public function reminderTitle(string $key): string;
    public function reminderUrl(): string;
}

ReminderService::schedule(Model&Remindable $m, string $key, CarbonInterface $at, ?User $recipient = null): Reminder; // upsert by (morph,key)
ReminderService::cancel(Model $m, ?string $key = null): void;
```
- Job `DispatchDueReminders` berjalan **tiap menit**: ambil `reminder_deliveries.status=pending` yang sudah jatuh tempo (batch 100) untuk dikirim ke penerima masing-masing → tandai hasil pada delivery tersebut.
- Quiet hours: jika jatuh di quiet hours penerima, **tunda** ke `quiet_hours_end` (kecuali reminder dengan key berakhiran `!urgent`).
- Preferensi channel dan quiet hours berlaku per user. Satu delivery gagal tidak menunda atau menandai berhasil delivery penerima lain.
- Waktu default reminder harian modul: **08:00** waktu household.
- Reminder yang `remind_at` sudah lewat >24 jam saat dibuat → JANGAN dibuat.
- Kebijakan retry/backoff dan penanganan status `failed` harus ditetapkan sebelum reminder engine dirilis (Q-M9).

### 7.6 Alert Contract
```php
interface AlertSource {
    /** @return iterable<Alert> */
    public function alerts(Household $h): iterable;
}
final readonly class Alert {
    public function __construct(
        public string $level,   // 'danger' | 'warning' | 'info'
        public string $title,   // "Anggaran Makan sudah 92%"
        public string $url,
        public string $sourceKey,
    ) {}
}
```
Dashboard menampilkan maksimal 5 alert, diurutkan danger → warning → info.

### 7.7 Lampiran (Media)
- Disk `private` (`storage/app/private`), diakses via route bertanda tangan (`URL::temporarySignedRoute`, 10 menit) yang memeriksa policy.
- Gambar dikompres di sisi client (maks 1600px, JPEG 0.8) sebelum upload; server membuat konversi `thumb` 300px.
- Batas: 10 MB per file, tipe: jpg, png, webp, heic (dikonversi), pdf.

---

## 8. Coding Standards

| # | Standar |
|---|---|
| CS-1 | PSR-12 via Pint. `declare(strict_types=1);` di semua file PHP baru. |
| CS-2 | Nama kode, tabel, kolom, variabel: **bahasa Inggris**. Teks UI: **bahasa Indonesia** via file `lang/id/*.php` (siap `lang/en`). JANGAN hardcode teks UI di Blade. |
| CS-3 | Validasi di Livewire Form Object (`Livewire\Form`) dengan pesan bahasa Indonesia. |
| CS-4 | Setiap Action menulis activity log: `activity()->performedOn($m)->causedBy($user)->log('fin.transaction.created')`. |
| CS-5 | Konkurensi 2 user: **last-write-wins** + kolom `updated_at` dicek di form edit; jika berubah sejak dibuka, tampilkan peringatan "Data ini baru diubah oleh {nama}. Muat ulang?" |
| CS-6 | Hapus = soft delete. Data dengan keterkaitan finansial → **arsip**, bukan hapus (dirinci per modul). |
| CS-7 | Test minimal per fitur: happy path, validasi gagal, otorisasi lintas household, dan aturan bisnis utama. |
| CS-8 | Query N+1 dilarang — aktifkan `Model::preventLazyLoading()` di non-production. |
| CS-9 | Commit: Conventional Commits, scope = modul (`feat(fin): ...`, `fix(core): ...`). |
| CS-10 | Semua tanggal tampil format Indonesia: `27 Okt 2026`, `Sen, 27 Okt`, jam `14.30`. Helper `<x-date>` & `<x-datetime>`. |

---

## 9. UI/UX Standard

### 9.1 Prinsip
1. **Mobile-first**: desain dari lebar 360px. Desktop adalah adaptasi.
2. **Input cepat**: aksi paling sering (catat pengeluaran) ≤ 3 tap dari mana pun.
3. **Satu tangan**: aksi utama di bagian bawah layar (FAB, bottom sheet, tombol simpan sticky).
4. **Jelas status**: setiap item yang punya jatuh tempo menampilkan badge: `Terlambat` (rose), `Hari ini` (amber), `Segera` ≤3 hari (sky), `Lunas/Selesai` (emerald).

### 9.2 Layout & Navigasi
- **Mobile (<1024px)**: Top bar (judul, lonceng notifikasi) + **Bottom nav 5 item**: `Beranda` · `Keuangan` · `[+]` (FAB tengah) · `Kalender` · `Lainnya`.
  - `Lainnya` → Tugas, Catatan, Belanja, Aset, Laporan, Pengaturan.
- **Desktop (≥1024px)**: Sidebar kiri (semua menu), konten maks `max-w-6xl`, panel kanan opsional "Agenda".
- **FAB [+]** membuka bottom sheet: `Pengeluaran` · `Pemasukan` · `Transfer` · `Tugas` · `Catatan` · `Item Belanja` · `Laporan Kerusakan`. Item yang modulnya belum dirilis disembunyikan (feature flag di `config/modules.php`).

### 9.3 Beranda (Dashboard)
Urutan blok (mobile):
1. Header: "Selamat pagi, {nama}" + nama household.
2. **Kartu Ringkasan Keuangan** (dari Finance): saldo operasional dan saldo tabungan dipisah berdasarkan jenis akun, total gabungan sebagai informasi sekunder, sisa target alokasi periode ini, rencana pengeluaran belum dibayar periode ini. Transfer memindahkan saldo antar kelompok akun, bukan expense; saldo tabungan bukan realisasi setoran alokasi periode.
3. **Alert** (AlertAggregator, maks 5).
4. **Hari Ini & Besok** (CalendarAggregator: hari ini + besok).
5. **Daftar belanja aktif** (dari Organizer, jika ada).
6. **7 hari ke depan** (ringkas, maks 7 item, link "Lihat kalender").

Blok modul yang belum aktif tidak ditampilkan.

### 9.4 Design Tokens (Tailwind v4 `@theme`)
| Token | Nilai |
|---|---|
| `--color-primary` | hijau hutan `#28543e`; dark sage `#bad6ae` |
| `--color-danger` / warning / info | light `#ad3f45` / `#87571f` / `#386578`; dark `#ffafb1` / `#edc48b` / `#a7cad6` |
| Background | light krem `#f6f4ee`, dark `#151f1b`; card light `#fffefa`, dark `#1f2c25` |
| Teks | light ink `#263c32`, muted `#637067`; dark ink `#f3f1e8`, muted `#b2beb2` |
| Font | UI `Inter, system-ui, sans-serif` (Inter jika tersedia/self-host, JANGAN CDN); judul serif lokal `"Iowan Old Style", "Palatino Linotype", "Book Antiqua", Georgia, serif` |
| Radius | card `rounded-3xl`, input `rounded-xl`, tombol `rounded-2xl`, chip `rounded-full` |
| Touch target | min `h-11` (44px) |
| Dark mode | otomatis ikuti sistem + toggle manual (class strategy, disimpan di localStorage) |
| Angka uang | `tabular-nums` |

### 9.5 Komponen Blade Wajib (`resources/views/components`)
`x-card`, `x-money`, `x-input-money`, `x-date`, `x-datetime`, `x-badge-status`, `x-member-avatar`, `x-empty-state` (ilustrasi ikon + teks + tombol aksi), `x-bottom-sheet` (mobile) / modal (desktop) — satu komponen responsif, `x-fab`, `x-confirm` (dialog konfirmasi hapus), `x-period-switcher` (◀ Nov 2026 ▶), `x-filter-chips`, `x-offline-banner`, `x-progress-bar` (warna otomatis: <80% emerald, 80–99% amber, ≥100% rose).

Ikon: **Heroicons** (SVG inline via `blade-ui-kit/blade-heroicons` — *disetujui sebagai bagian UI*).

### 9.6 Form Standard
- Label di atas input, pesan error merah di bawah input.
- Tombol simpan sticky di bawah pada mobile.
- Setelah simpan: toast "Tersimpan" 2 detik + kembali ke halaman asal.
- Hapus selalu dengan `x-confirm`. Untuk data penting: "Ketik HAPUS untuk konfirmasi" (household, akun keuangan).

### 9.7 Mockup frontend
- Prototype terpisah berada di `mockup/index.html`, belum memakai backend Laravel/Livewire.
- Styling memakai Tailwind CSS v4 hasil build lokal: `mockup/styles.css` sebagai sumber (`@theme`, `@apply`, utilities), `mockup/tailwind.css` sebagai output yang ikut disimpan agar HTML dapat langsung dibuka tanpa Node/CDN.
- Untuk mengubah CSS, masuk direktori `mockup`, jalankan `npm ci` lalu `npm run build`; `npm run watch` untuk iterasi lokal. Versi dependency dikunci pada `mockup/package-lock.json`. Tidak perlu Node pada saat hanya membuka mockup.
- Gunakan kontrol browser native: `dialog` untuk modal/fokus/Escape, `details` untuk rincian/catatan, `select`, `input type=date`, checkbox/progress, serta Constraint Validation API. Aturan uang, persentase, dan pasangan akun tetap divalidasi eksplisit; native validation tidak menggantikan validasi backend saat implementasi.
- Native date pada mockup dibatasi periode contoh Oktober 2026. Tema mengikuti preferensi OS saat dibuka dan dapat diganti selama sesi; persistensi preferensi produksi mengikuti §9.4.
- Arah visual terbaru reviewer: **hangat dan organik**, tetap dashboard keluarga. Kanvas krem, hijau hutan, judul serif lokal, ilustrasi botani SVG dekoratif, dan aksen alokasi yang tetap dilengkapi label teks. Kartu ringkasan hijau menjadi fokus; tanpa hero layar penuh atau konten dekoratif yang menghalangi informasi.
- Skill personal `premium-frontend-ui` menjadi referensi craftsmanship, bukan dependency aplikasi. Terapkan hierarki visual, motion berbasis transform/opacity, dan `prefers-reduced-motion`; jangan menambah preloader, scroll hijacking, custom cursor, font/CDN, atau library animasi. Kontrol native, aksesibilitas, dan input cepat tetap lebih penting dari efek sinematik.

---

## 10. PWA

| Item | Spesifikasi |
|---|---|
| Manifest | `name: "Family Hub"`, `short_name: "FamilyHub"`, `start_url: "/?source=pwa"`, `display: "standalone"`, `theme_color: "#28543e"`, `background_color: "#f6f4ee"`, ikon 192, 512, 512 maskable, shortcuts: "Catat Pengeluaran" (`/finance/transactions/create?type=expense`), "Kalender" |
| Service worker | File `public/sw.js` (vanilla, tanpa Workbox agar tidak menambah dependensi) |
| Strategi aset statis (`/build/*`, font, ikon) | Cache-first, versioned cache name `fh-static-{hash}` |
| Strategi navigasi (GET HTML) | **Network-first** timeout 4 detik → fallback ke cache halaman tsb → fallback `/offline` |
| Halaman yang di-cache untuk offline | Whitelist: `/`, `/finance`, `/finance/plan`, `/finance/transactions`, `/finance/loans`, `/calendar`, `/organizer/notes`, `/organizer/shopping`, `/assets`. Maks 50 entri, LRU. |
| Request non-GET & `/livewire/*` | JANGAN di-cache. Network-only. |
| Mode offline (read-only) | `x-offline-banner`: "Anda sedang offline — data yang tampil mungkin bukan yang terbaru (terakhir diperbarui {waktu})". Semua tombol simpan/FAB **disabled** via Alpine store `$store.net.online`. |
| Isolasi cache autentikasi | Halaman HTML terautentikasi dapat berisi data household. Cache tidak boleh disajikan kepada user atau household lain; hapus cache saat konteks autentikasi berubah (termasuk login, logout, atau perpindahan household). Jika isolasi ini tidak dapat dijamin, jangan cache halaman yang memuat data sensitif. |
| Logout | Hapus semua cache halaman (`caches.delete`) — data finansial tidak boleh tertinggal di perangkat. |
| Update | Saat SW baru tersedia → toast "Versi baru tersedia [Muat ulang]". |
| Push | Web Push VAPID. iOS hanya berfungsi jika PWA di-install ke Home Screen (iOS 16.4+) — tampilkan panduan di halaman Pengaturan Notifikasi. |
| Target Lighthouse (mobile) | Installable ✅, Performance ≥ 85, Accessibility ≥ 90, Best Practices ≥ 90 |

---

## 11. Notifikasi

| Jenis | Channel | Contoh |
|---|---|---|
| Reminder jatuh tempo | push + in-app (+ email jika aktif) | "Cicilan KPR ke-14 jatuh tempo 3 hari lagi (Rp 3.250.000)" |
| Aktivitas pasangan | in-app saja | "Istri mencatat pengeluaran Rp 450.000 – Belanja Bulanan" (bisa dimatikan) |
| Ambang anggaran | push + in-app | "Anggaran Makan & Minum mencapai 80%" |
| Digest harian | email / push (pilih) | Ringkasan: jatuh tempo hari ini, tugas hari ini, alert |

Teks notifikasi WAJIB singkat (≤ 120 karakter) dan berisi URL tujuan.

---

## 12. Keamanan & Privasi

| # | Kontrol |
|---|---|
| SEC-1 | HTTPS wajib melalui zrok untuk rilis awal dan Cloudflare Tunnel jika kelak dimigrasikan. `APP_URL` https, `SESSION_SECURE_COOKIE=true`; validasi forwarded headers/trusted proxy secara ketat dan jangan mengekspos port aplikasi langsung ke internet. |
| SEC-2 | Email OTP opsional per user sebagai satu-satunya metode 2FA yang direncanakan; SEBAIKNYA diaktifkan untuk system admin. OTP harus sekali pakai, kedaluwarsa, dibatasi percobaannya, dan tidak dicatat ke log. Keamanan faktor ini bergantung pada keamanan mailbox user; system admin SEBAIKNYA mengaktifkan MFA pada penyedia email. |
| SEC-3 | Rate limit login 5/menit/IP+email; undangan 10/jam. |
| SEC-4 | Isolasi household (§2.4) + test wajib. |
| SEC-5 | File privat via signed route + policy. |
| SEC-6 | Header keamanan: CSP dasar, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin`. |
| SEC-7 | Backup terenkripsi (password zip `BACKUP_ARCHIVE_PASSWORD`). |
| SEC-8 | Ekspor seluruh data household (ZIP berisi CSV + lampiran) oleh owner — hak portabilitas data (selaras UU PDP No. 27/2022). |
| SEC-9 | Hapus household: soft delete 30 hari, lalu purge permanen via job. |
| SEC-10 | System admin tidak punya route untuk melihat data domain household lain. |

---

## 13. Deployment — Server Rumah

### 13.1 Topologi
```
[HP/Laptop] ──HTTPS──> [zrok public share]
                               │
                    [Linux bare-metal server]
                    ├── zrok client/agent
                    ├── Nginx ── PHP-FPM ── Laravel
                    ├── queue worker (managed process)
                    ├── Laravel scheduler (managed process)
                    └── SQLite (default) / MariaDB (opsional)
                    Persistent files: storage/, database/ (jika SQLite)
```
- Deployment bare-metal: Nginx menerima request lokal dari zrok dan meneruskannya ke PHP-FPM; konfigurasi trusted proxy wajib membatasi proxy tepercaya pada jalur zrok yang benar.
- Queue worker, scheduler, Nginx, PHP-FPM, dan zrok client harus dikelola sebagai proses Linux yang dapat dimulai ulang otomatis serta dipantau. Gunakan pengelola service OS yang tersedia; dokumentasikan unit dan perintah aktual setelah implementasi.
- zrok adalah metode akses jarak jauh untuk rilis awal. Jangan mengekspos port Nginx/PHP-FPM/database langsung ke internet; batasi listener internal sesuai topologi.
- HTTPS, forwarded headers, domain/URL publik, cookie aman, dan health check harus diverifikasi end-to-end melalui zrok—jangan hanya menguji akses localhost.
- Cloudflare Tunnel adalah opsi migrasi saat skala membesar, bukan prasyarat rilis awal. Sebelum migrasi, tentukan pemicu skala dan uji perubahan DNS/hostname, HTTPS/proxy headers, sesi/cookie, notifikasi, serta periode overlap/rollback. Jangan menjalankan dua jalur publik bersamaan tanpa pembatasan akses yang eksplisit.

### 13.2 Backup
- `spatie/laravel-backup` harian 02:00: DB + `storage/app/private`.
- Tujuan: (1) disk lokal kedua/eksternal, (2) cloud (Google Drive via rclone atau S3-compatible).
- Retensi: 7 harian, 4 mingguan, 6 bulanan.
- **Uji restore** wajib dilakukan sebelum go-live dan setiap 3 bulan (`docs/runbook/backup-restore.md`).
- Notifikasi email ke admin jika backup gagal.

### 13.3 Lingkungan
| Env | Keterangan |
|---|---|
| `local` | Laptop developer / AI agent sandbox, SQLite |
| `production` | Server rumah |
(Tidak ada staging terpisah; gunakan branch + data seed untuk UAT.)

### 13.4 Scheduler (ringkas semua modul)
| Jadwal | Command | Modul |
|---|---|---|
| tiap menit | `reminders:dispatch` | Core |
| 00:10 harian | `finance:generate-planned-items` | Finance |
| 00:20 harian | `finance:mark-overdue` | Finance |
| 00:30 harian | `organizer:roll-recurring-tasks` | Organizer |
| 00:40 harian | `assets:refresh-due-dates` | Asset |
| sesuai `daily_digest_time` per user (cek tiap 15 menit) | `digest:send` | Core |
| 02:00 harian | `backup:run` | Core |
| 03:00 harian | `backup:clean`, `households:purge-deleted` | Core |

---

## 14. Roadmap & Exit Criteria

| Fase | Isi | Exit criteria (diverifikasi reviewer) |
|---|---|---|
| **Phase 0 – Core** | Task `CORE-01..CORE-16` (§15) | Login/undangan partner berfungsi di HP via HTTPS; PWA bisa di-install; push test notification diterima; backup & restore teruji |
| **Phase 1 – Finance MVP** | `01-MODULE-FINANCE.md` | Suami & istri mencatat transaksi 2 minggu penuh; rencana bulanan & cicilan tampil benar; saldo cocok dengan rekening nyata |
| **Phase 2 – Organizer MVP** | `02-MODULE-ORGANIZER.md` | Kalender menampilkan tagihan + event + tugas; .ics bisa di-import ke Google Calendar; daftar belanja terpakai 2 kali belanja |
| **Phase 3 – Asset MVP** | `03-MODULE-ASSET.md` | Minimal 10 aset tercatat; 1 siklus servis tercatat → transaksi & jadwal berikutnya benar |
| **Phase 4 – Hardening** | Performa, Lighthouse, aksesibilitas, dokumentasi user | Target §10 tercapai; runbook deploy & backup/restore teruji; checklist UAT/go-live lulus |

---

## 15. Task Breakdown — Phase 0 (Core)

> Format setiap task: **ID · Judul** — Deskripsi · *Acceptance Criteria (AC)* · Dependensi

| ID | Judul | Deskripsi & AC | Dep |
|---|---|---|---|
| CORE-01 | Inisialisasi proyek | PHP 8.5, Laravel 13, Fortify + Livewire 4/Blade, Tailwind v4, Vite 8 (Node 24 LTS), Pest 5, Pint, Larastan 3, Boost development, struktur folder §4.1, `config/modules.php` (flag finance/organizer/asset). *AC:* resolusi dependency stabil dan `composer check-platform-reqs` lulus tanpa mengabaikan requirement; build frontend, `composer test`, `pint --test`, `phpstan` lulus di CI (GitHub Actions) untuk SQLite & MariaDB; Boost MCP initialize/daftar tool/panggilan dokumentasi lulus melalui client terminal yang disetujui reviewer (atau Copilot VS Code), tanpa menghapus instruksi proyek. | – |
| CORE-02 | Deploy bare-metal & runbook | Nginx, PHP-FPM, proses worker/scheduler terkelola, zrok, `.env.example`, `docs/runbook/deploy.md`. *AC:* instalasi pada Linux bersih → HTTPS publik melalui zrok; app, worker, scheduler, dan backup aktif; proses pulih otomatis setelah reboot. | 01 |
| CORE-03 | Utilitas Support | `Money`, `PeriodResolver`, `BelongsToHousehold`, komponen `x-money`, `x-input-money`, `x-date`. *AC:* unit test Money::parse (≥10 kasus) & PeriodResolver (S=1, S=25, akhir tahun, tahun kabisat, perubahan setting dengan periode transisi pendek/panjang dan tanpa mengubah key/rentang historis). | 01 |
| CORE-04 | Household & member | Migrasi & model §5.1, enum MemberRole, Policy, slot member database. *AC:* unique household/slot menolak penerimaan undangan bersamaan yang melebihi 2 member; test konkurensi pada SQLite & MariaDB. Integrasi tenant/resolver CORE-03B dilanjutkan setelah model nyata tersedia. | 03A (keputusan LOG-019) |
| CORE-05 | Registrasi invite-only | Admin buat invite household baru; owner undang partner; token hash, kedaluwarsa 7 hari, email undangan. *AC:* registrasi tanpa token ditolak; token dipakai 2x ditolak. | 04 |
| CORE-06 | Onboarding | Wizard 3 langkah: nama household → zona waktu & hari mulai periode → undang partner (bisa dilewati). | 05 |
| CORE-07 | Layout & navigasi | Layout mobile/desktop §9.2, bottom nav, sidebar, FAB + bottom sheet, dark mode, komponen §9.5. *AC:* tidak ada scroll horizontal di 360px. | 03 |
| CORE-08 | Recurrence wrapper | §7.4 + `humanize` bahasa Indonesia. *AC:* unit test tiap preset, termasuk "akhir bulan" Februari. | 01 |
| CORE-09 | Reminder engine | Tabel reminder & deliveries, `ReminderService`, job dispatch, quiet hours. *AC:* test idempoten per penerima, delivery household untuk member baru, tunda hanya delivery penerima terkait, cancel, serta retry sesuai Q-M9. | 04 |
| CORE-10 | Notifikasi & Web Push | Channel database/webpush/mail, halaman pengaturan notifikasi, tombol "Kirim notifikasi uji", notification center. | 09 |
| CORE-11 | Calendar & Alert aggregator | Kontrak §7.3, §7.6, aggregator, tagging. Source dummy di test. | 04 |
| CORE-12 | Dashboard kerangka | Blok §9.3 yang mengonsumsi aggregator & slot widget modul (`DashboardWidget` view-component per modul, dirender jika modul aktif). | 07, 11 |
| CORE-13 | PWA | Manifest, `sw.js`, offline page, offline banner, disable form saat offline, clear cache on auth-context changes, prompt update. *AC:* Lighthouse installable; mode pesawat → halaman whitelist tetap terbuka read-only; cached data tidak bocor saat login/logout atau pergantian household. | 07 |
| CORE-14 | Pengaturan & audit log | Profil, keamanan (password, email OTP opsional—lihat SEC-2 dan Q-M12), household, member (undang/keluarkan/transfer ownership), riwayat aktivitas. | 05, 10 |
| CORE-15 | Admin panel | §5.2 poin 5. *AC:* test bahwa admin tidak dapat membuka route data domain household lain. | 05 |
| CORE-16 | Backup & ekspor data | spatie backup + jadwal + notifikasi gagal; ekspor ZIP household (kerangka; tiap modul menambahkan exporter via kontrak `HouseholdExporter`). `docs/runbook/backup-restore.md`. | 02 |

---

## 16. Aturan Kerja AI Agent

1. **Sebelum mulai:** baca MASTERPLAN + dokumen modul terkait. Kerjakan **satu task ID per branch/PR** (`feature/FIN-03-quick-add`).
2. **Jangan menambah scope.** Ide tambahan ditulis di bagian "Saran" deskripsi PR, bukan diimplementasikan.
3. **Ambiguitas:** gunakan *Default Assumption* di bagian Open Questions dokumen; tandai di kode `// ASSUMPTION(Q-F3): ...` dan sebutkan di PR.
4. **Konflik dokumen:** berhenti, laporkan di PR, usulkan perubahan dokumen.
5. **Template PR wajib:**
   ```
   ## Task: FIN-03 – Quick add transaksi
   ### Ringkasan perubahan
   ### Acceptance Criteria
   - [x] AC1 ...
   ### Asumsi yang diambil
   ### Migrasi DB (ya/tidak, reversible?)
   ### Screenshot mobile 360px (light & dark)
   ### Cara uji manual (langkah untuk reviewer)
   ### Saran (di luar scope)
   ```
6. **Definition of Done:** AC terpenuhi · test lulus (SQLite & MariaDB) · Pint & Larastan bersih · policy & test isolasi · tampil benar di 360px & desktop, light & dark · teks UI di `lang/id` · activity log · dokumen diperbarui bila ada perubahan perilaku.
7. **Migrasi:** setelah rilis produksi, JANGAN ubah migrasi lama; buat migrasi baru. Semua migrasi wajib punya `down()`.
8. **Seeder demo:** setiap modul menyediakan `DemoSeeder` berisi data realistis Indonesia (nama, nominal, tanggal relatif hari ini) untuk UAT.
9. **Pelacakan pekerjaan:** setiap batch perubahan (termasuk dokumentasi, konfigurasi, dependency, tooling, dan skill) wajib mengikuti [development-docs-sync](../.github/skills/development-docs-sync/SKILL.md), memperbarui [logbook](logbook.md) dan [rencana development](development-plan.md). Perubahan struktural wajib meninjau seluruh dokumentasi proyek dan menyelaraskan semua dokumen terdampak, termasuk instruksi/skill; catat cakupan review serta dokumen yang tidak terdampak. Tidak perlu edit kosmetik pada dokumen yang tetap benar. Catat bukti verifikasi dan pemeriksaan yang belum dilakukan; persiapan dependency tidak berarti task implementasi selesai. Investigasi read-only tanpa perubahan tidak memerlukan entri baru.

---

## 17. Architecture Decision Records

| ADR | Keputusan | Status |
|---|---|---|
| ADR-001 | Uang disimpan sebagai BIGINT rupiah | Accepted |
| ADR-002 | Livewire + Alpine (bukan SPA Vue/React) — satu bahasa, cocok untuk dikembangkan AI agent & direview satu orang; offline hanya read-only sehingga SPA tidak diperlukan. Versi 4 menggantikan versi 3 melalui ADR-011 | Accepted; versi diperbarui ADR-011 |
| ADR-003 | Single database multi-household dengan `household_id` + global scope | Accepted |
| ADR-004 | ULID sebagai primary key | Accepted |
| ADR-005 | Recurrence via RRULE subset + `rlanvin/php-rrule` | Accepted |
| ADR-006 | Maksimal 2 member per household; 1 user = 1 household | Accepted |
| ADR-007 | SQLite (WAL) sebagai default produksi; MariaDB opsional | Accepted |
| ADR-008 | Kalender agregasi virtual (tidak menyalin data ke tabel event) | Accepted |
| ADR-009 | Service worker vanilla tanpa Workbox | Accepted |
| ADR-010 | Akses jarak jauh: zrok untuk rilis awal; evaluasi Cloudflare Tunnel saat skala membesar | Accepted; pemicu migrasi ditetapkan sebelum perpindahan |
| [ADR-011](adr/ADR-011-current-stack.md) | Baseline PHP 8.5, Laravel 13, Livewire 4, Fortify menggantikan Breeze, Pest 5, dependency stabil terbaru, dan Boost development | Accepted oleh reviewer di chat 2026-10-26; validasi runtime pada CORE-01 |

---

## 18. Open Questions (Round 2) — dengan Default Assumption

> AI agent boleh melanjutkan memakai *default* bila belum dijawab.

| ID | Pertanyaan | Default Assumption |
|---|---|---|
| Q-M1 | Kapan skala sudah cukup besar untuk migrasi dari zrok ke Cloudflare Tunnel? | Rilis awal memakai zrok; tetapkan indikator kapasitas/operasional sebelum migrasi, bukan blocker go-live |
| Q-M2 | Spesifikasi dan distro/version Linux server rumah? | Linux bare-metal + Nginx, tanpa Docker. Catat distro/version, CPU/RAM/storage dan metode akses admin sebelum deploy |
| Q-M3 | SMTP untuk email (undangan, digest)? | Gmail SMTP dengan App Password |
| Q-M4 | Repository: GitHub private + GitHub Actions? | Ya |
| Q-M5 | Apakah Anda (admin) boleh melihat data household kerabat yang Anda undang? | **Tidak** (privasi) |
| Q-M6 | Periode keuangan mengikuti kalender (tgl 1) atau siklus gajian (mis. tgl 25)? | Dapat diatur per household, default tgl 1; perubahan berlaku mulai batas periode berikutnya, dengan preview periode transisi |
| Q-M7 | Notifikasi aktivitas pasangan ("Istri mencatat pengeluaran…") — diinginkan? | Aktif, bisa dimatikan per user |
| Q-M9 | Berapa retry/backoff reminder sebelum status `failed` terminal, dan bagaimana kegagalan ditampilkan/diberitahukan? | **Open** — putuskan sebelum CORE-09 |
| Q-M11 | Berapa lama data household yang sudah di-purge tetap ada di backup terenkripsi, dan bagaimana permintaan penghapusan berlaku pada backup yang masih dalam retensi? | **Open** — tetapkan kebijakan sebelum go-live |
| Q-M12 | Berapa masa berlaku email OTP, batas percobaan/kirim ulang, dan bagaimana pemulihan akun dilakukan jika mailbox tidak dapat diakses? | **Open** — tetapkan sebelum CORE-14 |

---

## 19. Changelog

| Versi | Perubahan |
|---|---|
| 0.1 | Draft awal satu dokumen, ekspansi ide |
| 0.2 | Dipecah menjadi masterplan + 3 dokumen modul; keputusan discovery D1–D8; household maks 2 member; offline read-only; .ics; deployment server rumah; aturan AI agent |
| 0.3 | 2026-10-26: pembaruan baseline stack stabil, Fortify + Livewire 4 menggantikan Breeze, persetujuan Boost development, requirement Node/CI, dan batas verifikasi kompatibilitas (ADR-011) |
