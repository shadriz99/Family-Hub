# 🚀 Family Hub — Rencana Fitur Peningkatan Pasca-MVP (v1.1+)

**Dokumen:** `04-IMPROVEMENT-PLAN.md`  
**Versi:** 0.1 (Draft Perencanaan Pasca-MVP)  
**Status:** Disetujui Penuh oleh Reviewer pada LOG-076 & LOG-077 (Sesi Grilling Round 1 & 2; ADR-012 disahkan; P-22 ditutup) — Siap dieksekusi pasca kelulusan UAT Phase 4 / rilis v1.0.0  
**Rujukan Utama:** [Masterplan](00-MASTERPLAN.md), [Finance](01-MODULE-FINANCE.md), [Organizer](02-MODULE-ORGANIZER.md), [Asset](03-MODULE-ASSET.md), [Development Plan](development-plan.md), [Pending List](pending.md)

---

## 1. Tujuan & Prinsip Perencanaan

Setelah fondasi Phase 0 (Core), Phase 1 (Finance), Phase 2 (Organizer), Phase 3 (Asset), dan Phase 4 (Hardening Langkah 1–5) selesai dibangun, dokumen ini menetapkan **peta jalan fitur peningkatan (improvement plan) untuk siklus v1.1 dan seterusnya**.

Seluruh usulan fitur dalam dokumen ini wajib mematuhi batas arsitektur Family Hub:

1. **Patuh Non-Goals ([Masterplan §1.2](00-MASTERPLAN.md#12-non-goals-jangan-dibangun-tanpa-adr-baru)):** tidak ada open banking/scraping bank, tidak ada sinkronisasi 2 arah Google Calendar, tidak ada offline-write, tetap maksimal 2 anggota per household (suami & istri), hanya mata uang IDR (`BIGINT`), tidak ada pelacakan harga pasar saham/kripto, dan tidak mengirim data/struk keluarga ke layanan cloud/AI eksternal.
2. **Ramah Perangkat Keras Server Rumah:** dirancang khusus agar ringan di **Odroid-C2 (ARM64 Cortex-A53, RAM 2 GB, SQLite WAL)** — tanpa menambah daemon berat (seperti Meilisearch, Elasticsearch, atau headless Chromium permanen) dan tanpa menambah paket Composer/npm di luar daftar resmi kecuali melalui ADR baru yang disetujui reviewer.
3. **Batas Modular Monolith & Isolasi Tenant:** semua komunikasi lintas modul tetap melalui `Contracts/` (seperti `FinanceGateway`, `CalendarSource`, `AlertSource`, dan kontrak pencarian baru), serta wajib menegakkan `BelongsToHousehold` / `HouseholdContext::runInContext()` dan kompatibilitas ganda **SQLite & MariaDB 10.11+**.

---

## 2. Metodologi Pengurutan Prioritas

Setiap kandidat fitur dinilai menggunakan empat kriteria berbobot (skala **1–5** per kriteria, skor maksimum **20**) dan diurutkan dari **Prioritas 1 (Paling Prioritas / Tertinggi)** hingga **Prioritas 8 (Paling Rendah)**:

- **Dampak Harian Pasangan (1–5):** seberapa sering fitur menghemat waktu/friksi nyata suami & istri setiap minggu.
- **Keamanan & Keandalan Operasional (1–5):** penutupan celah keamanan, kepatuhan privasi (`SEC-*`), atau otomasi jadwal yang mencegah data basi.
- **Sinergi Lintas Modul (1–5):** seberapa kuat fitur menghubungkan Core, Finance, Organizer, dan Asset.
- **Efisiensi Implementasi & Beban Server (1–5):** skor lebih tinggi berarti *footprint* RAM/CPU sangat ringan di Odroid-C2 dan tidak memerlukan dependensi eksternal baru.

### Matriks Ringkasan Prioritas (Tertinggi → Terendah)

| Peringkat | ID Task | Fitur Peningkatan | Modul | Tingkat | Dampak | Keamanan / Ops | Sinergi | Efisiensi Host | Skor Total | Target Rilis |
|---:|---|---|---|---|---:|---:|---:|---:|---:|---|
| **#1** | `IMP-01` | Otomasi Scheduler Penuh, Daily Digest Pagi, & Purge Retensi 30 Hari | Core, Organizer, Asset | **P1 — Kritis** | 5 | 5 | 5 | 5 | **20 / 20** | v1.1.0 |
| **#2** | `IMP-02` | Autentikasi Dua Faktor (2FA) Email OTP & Recovery Codes (`P-19` / `Q-M12`) | Core (Auth & Security) | **P1 — Tinggi** | 3 | 5 | 4 | 5 | **17 / 20** | v1.1.0 |
| **#3** | `IMP-03` | Split Transaksi Multi-Kategori & Integrasi Checkout Struk/Belanja | Finance, Organizer | **P2 — Tinggi** | 5 | 3 | 5 | 4 | **17 / 20** | v1.1.0 |
| **#4** | `IMP-04` | Kantong Tabungan Tujuan / *Sinking Funds* (`FIN-V11-1`) | Finance | **P2 — Tinggi** | 5 | 3 | 4 | 4 | **16 / 20** | v1.1.0 |
| **#5** | `IMP-05` | Pencarian Terpadu Lintas Modul (*Global Household Search* `Ctrl+K`) | Core + Semua Modul | **P2 — Menengah-Tinggi** | 4 | 3 | 5 | 4 | **16 / 20** | v1.2.0 |
| **#6** | `IMP-06` | Langganan Kalender Live `.ics` (`webcal://`) Bertoken & Libur Nasional | Organizer, Core | **P3 — Menengah** | 4 | 3 | 4 | 4 | **15 / 20** | v1.2.0 |
| **#7** | `IMP-07` | Prediksi Servis Berbasis Laju Meter, Analisis TCO, & Label QR Aset | Asset, Finance | **P3 — Menengah-Rendah** | 3 | 3 | 4 | 4 | **14 / 20** | v1.2.0 |
| **#8** | `IMP-08` | Impor CSV Mutasi Bank, Rekonsiliasi Semi-Otomatis, & Laporan Cetak PDF | Finance | **P4 — Terendah** | 3 | 3 | 2 | 4 | **12 / 20** | v1.3.0 |

> **Catatan aturan skor seri:** `IMP-02` ditempatkan di atas `IMP-03` (keduanya skor 17/20) karena kontrol keamanan autentikasi publik (`SEC-2` / `P-19`) wajib didahulukan sebelum perluasan fitur domain; `IMP-04` ditempatkan di atas `IMP-05` (keduanya skor 16/20) karena kebutuhan perencanaan dana tujuan keluarga lebih mendesak pada bulan-bulan awal dibanding pencarian arsip historis yang baru terasa saat volume data membesar.

---

## 3. Rincian Fitur Peningkatan (Terurut Prioritas #1 → #8)

### Prioritas #1 — `IMP-01`: Otomasi Scheduler Penuh, Daily Digest Pagi, & Purge Retensi 30 Hari

- **Modul Terdampak:** Core, Organizer, Asset (`routes/console.php`, `app/Core/Console/Commands/`, `app/Modules/Organizer/`, `app/Modules/Asset/`)
- **Latar Belakang Masalah:**
  - Kolom `users.daily_digest_time` dan pengaturan UI di `/settings/notifications` sudah tersedia, tetapi command pengirim `digest:send` ([Masterplan §11 & §13.4](00-MASTERPLAN.md#134-scheduler-ringkas-semua-modul)) belum diimplementasikan.
  - Command `assets:refresh-due-dates` dan `assets:odometer-reminders` sudah ada di modul Asset tetapi belum didaftarkan ke jadwal harian `routes/console.php`.
  - Command `organizer:roll-recurring-tasks` (menggulung tugas berulang yang terlewat malam hari) dan `households:purge-deleted` (menghapus permanen data & media privat household yang sudah di-soft-delete >30 hari sesuai `SEC-9`) belum tersedia.
- **Ruang Lingkup Solusi:**
  1. **Daily Digest (`digest:send`):** berjalan tiap 15 menit, memeriksa user aktif yang `daily_digest_time` (dalam timezone household masing-masing, default `07:00`) jatuh pada jendela 15 menit berjalan, belum menerima digest pada tanggal lokal tersebut, dan berada di luar `quiet_hours`. Otomatis dilewati (*suppressed*) tanpa mengirim notifikasi/email jika tidak ada agenda, tagihan jatuh tempo, atau tugas tertunda. Mengirim satu ringkasan padat (tagihan jatuh tempo hari ini, tugas hari ini, perawatan/dokumen aset mendesak, dan maks 3 alert teratas) melalui Email dan/atau Web Push sesuai preferensi user.
  2. **Penjadwalan Lengkap `routes/console.php`:** mendaftarkan `organizer:roll-recurring-tasks` (`00:30`), `assets:refresh-due-dates` (`00:40`), `assets:odometer-reminders` (`mingguan/bulanan`), dan `households:purge-deleted` (`03:10`).
  3. **Isolasi Konteks:** seluruh pemrosesan per household wajib dieksekusi di dalam `HouseholdContext::runInContext($household, ...)` agar bebas kebocoran tenant state.
- **Dampak Skema & Paket:**
  - Menambah kolom ringan `last_daily_digest_sent_on` (`date`, nullable) pada tabel `users` untuk penjamin idempotensi harian lintas zona waktu.
  - **0 paket baru.**
- **Acceptance Criteria (AC):**
  - User hanya menerima maksimal 1 digest per tanggal lokal household meskipun scheduler dipicu berulang kali.
  - Household yang baru di-soft-delete 29 hari tidak terhapus oleh `households:purge-deleted`, sedangkan yang >30 hari terhapus bersih bersama direktori media privatnya dan tercatat di log sistem tanpa membocorkan isi data.
  - Test fitur berjalan lulus di SQLite dan MariaDB.

---

### Prioritas #2 — `IMP-02`: Autentikasi Dua Faktor (2FA) Email OTP & Recovery Codes

- **Modul Terdampak:** Core Auth & Pengaturan Keamanan (`P-19`, `Q-M12`, `SEC-2`)
- **Latar Belakang Masalah:**
  - Saat Family Hub diakses dari internet publik via zrok (atau kelak Cloudflare Tunnel), perlindungan akun saat ini mengandalkan password Argon2id/Bcrypt dan rate limiting login. Item `P-19` menunda 2FA Email OTP ke v1.1 dan `SEC-2` merekomendasikannya secara kuat, terutama bagi akun `System Admin` dan opsional bagi pemilik/pasangan household.
- **Ruang Lingkup Solusi:**
  1. **Email OTP Sekali Pakai & Kebijakan Opt-in (ADR-012):** 2FA Email OTP murni bersifat independen per-pengguna (*opt-in* mandiri masing-masing anggota keluarga tanpa paksaan lintas pasangan). Saat user dengan `two_factor_email_enabled = true` berhasil memasukkan email & kata sandi di Fortify login pipeline, sesi diarahkan ke tahap challenge `/two-factor-challenge` sebelum sesi penuh diberikan.
  2. **Keamanan Token & Pembatasan Laju:** kode 6 digit angka acak kriptografis (`random_int`), disimpan hanya sebagai hash di tabel/kolom challenge (tidak pernah ditulis ke log aplikasi), kedaluwarsa dalam **10 menit**, maksimal **5 percobaan salah** per kode, dan batas kirim ulang **3 kali per 15 menit** (dikirim via antrean `ShouldQueue`).
  3. **Recovery Codes (`Q-M12`):** saat mengaktifkan 2FA di `/settings/profile`, sistem men-generate **8 kode pemulihan sekali pakai** (ditampilkan satu kali dengan tombol salin/unduh `.txt`, disimpan sebagai array hash) untuk skenario darurat ketika mailbox email user terkunci/tidak dapat diakses.
  4. **Pengingat Admin:** menampilkan banner anjuran aktivasi 2FA di `/admin` jika akun System Admin belum mengaktifkan Email OTP.
- **Dampak Skema & Paket:**
  - Migrasi tambahan pada `users` (atau tabel khusus `user_two_factor_challenges`): `two_factor_email_enabled`, `two_factor_recovery_codes` (terenkripsi/hash), `two_factor_confirmed_at`, serta state OTP kedaluwarsa & penghitung percobaan.
  - **0 paket baru** (tetap patuh ADR-011: tidak mengaktifkan TOTP/passkey bawaan Fortify).
- **Acceptance Criteria (AC):**
  - Login dengan OTP aktif memblokir akses ke route `/`, `/finance`, dan `/admin` sebelum challenge terverifikasi.
  - OTP kedaluwarsa, salah >5x, atau dipakai kedua kali ditolak; penggunaan 1 recovery code valid meloloskan login dan langsung menghanguskan kode tersebut saja.
  - Keputusan parameter final `Q-M12` dikonfirmasi reviewer sebelum pengodean dimulai.

---

### Prioritas #3 — `IMP-03`: Split Transaksi Multi-Kategori & Integrasi Checkout Struk/Belanja

- **Modul Terdampak:** Finance (`FIN-*`), Organizer (`ShoppingList` via `FinanceGateway`)
- **Latar Belakang Masalah:**
  - Tercatat pada kolom *Ditunda (v1.1+)* di [spesifikasi Finance §2](01-MODULE-FINANCE.md#2-scope). Dalam praktik harian pasangan, satu struk belanja bulanan (mis. Rp 650.000 di supermarket) sering terdiri dari gabungan *Makan & Minum* (Rp 400.000), *Kebutuhan Rumah Tangga* (Rp 180.000), dan *Perawatan Diri / Tersier* (Rp 70.000). Tanpa fitur split, pengguna terpaksa menginput 3 transaksi terpisah (sehingga mutasi rekening tidak cocok dengan rekening koran) atau menyatukan semuanya ke 1 kategori (membuat laporan anggaran & 5 alokasi meleset).
- **Ruang Lingkup Solusi:**
  1. **Baris Rincian Split (`fin_transaction_splits`):** transaksi bertipe `expense` atau `income` dapat memiliki mode tunggal (default cepat ≤3 tap tetap terjaga!) atau mode **"Bagi Kategori (Split)"** berisi ≥2 baris (`category_id`, `allocation`, `amount` BIGINT, `note` opsional).
  2. **Invarian Akuntansi Ketat:** total `SUM(splits.amount)` wajib sama persis dengan `fin_transactions.amount`; penyimpanan ditolak jika terdapat sisa saldo transaksi induk yang belum dialokasikan. Transaksi disimpan atomik dalam `DB::transaction`.
  3. **Akurasi Saldo vs Anggaran:** `AccountBalanceService` tetap menghitung 1 mutasi utuh pada rekening pembayar, sedangkan `BudgetService`, `AllocationService`, notifikasi threshold 80%/100%, laporan per kategori, dan ekspor CSV membaca rincian tiap baris split secara akurat.
  4. **Sinergi OCR & Daftar Belanja:** saat menyelesaikan `ShoppingList` yang memiliki grup barang berbeda atau meninjau hasil scan `ReceiptOcrService`, user dapat langsung membagi nominal ke subkategori tanpa meninggalkan form.
- **Dampak Skema & Paket:**
  - Tabel baru `fin_transaction_splits` dengan ULID PK, `household_id`, `transaction_id`, `category_id`, `allocation`, `amount` (`bigInteger`), `description`, timestamps.
  - Ekstensi opsional DTO `ExpenseData` pada `FinanceGateway` (backward-compatible).
  - **0 paket baru.**
- **Acceptance Criteria (AC):**
  - Submit form split dengan selisih nominal (kurang/lebih Rp 1 sekalipun dari total transaksi) ditolak validasi server & indikator sisa nominal tampil *real-time* via Alpine.
  - Menghapus/mengubah transaksi split memperbarui perhitungan anggaran dan threshold semua kategori terdampak tanpa mendobel saldo akun.
  - Isolasi lintas household teruji penuh.

---

### Prioritas #4 — `IMP-04`: Kantong Tabungan Tujuan / *Sinking Funds* (`FIN-V11-1`)

- **Modul Terdampak:** Finance (`/finance/goals` atau tab pada `/finance/accounts` & `/finance/plan`)
- **Latar Belakang Masalah:**
  - Tercatat resmi sebagai `FIN-V11-1` di [spesifikasi Finance §2](01-MODULE-FINANCE.md#2-scope). Pasangan suami–istri umumnya menaruh dana tabungan di 1–2 rekening bank yang sama, namun uang tersebut memiliki beberapa tujuan masa depan yang berbeda (Dana Darurat 6x pengeluaran, Pajak Tahunan Mobil/STNK, Kurban, Renovasi Rumah, Liburan Keluarga). Saat ini saldo tabungan tampil sebagai satu angka gabungan tanpa pemisahan progres per tujuan.
- **Ruang Lingkup Solusi:**
  1. **Model Tujuan Tabungan (`SavingsGoal`):** nama tujuan, nominal target (`target_amount` BIGINT), tenggat tanggal opsional (`target_date`), akun tabungan acuan (`account_id` bertipe `savings` atau multi-akun tabungan), warna/ikon, dan status (`active`, `achieved`, `archived`).
  2. **Pencatatan Alokasi Virtual (*Earmarking*):** setiap transfer masuk ke akun tabungan (yang sudah terhubung ke pos alokasi *Tabungan & Investasi*) dapat dikaitkan secara opsional ke satu `SavingsGoal`, atau dialokasikan dari "Saldo Tabungan Belum Ditetapkan (*Unallocated Savings*)". Tidak membuat saldo fiktif: jika saldo riil rekening tabungan turun karena penarikan, UI menampilkan peringatan jika total alokasi kantong melebihi saldo fisik rekening.
  3. **Rekomendasi Setoran per Periode:** menghitung otomatis kebutuhan sisihan per periode keuangan (`PeriodResolver`) agar target tercapai tepat waktu pada `target_date`, serta menampilkan kartu progres di ringkasan Finance.
- **Dampak Skema & Paket:**
  - Tabel baru `fin_savings_goals` dan kolom nullable `savings_goal_id` pada `fin_transactions` (khusus transaksi transfer tabungan) atau tabel ledger `fin_savings_goal_entries`.
  - **0 paket baru** (murni target nominal IDR, bukan pelacakan instrumen pasar/investasi).
- **Acceptance Criteria (AC):**
  - Penambahan/penarikan alokasi kantong tidak pernah mengubah saldo fisik `fin_accounts`.
  - Peringatan muncul jelas bila jumlah dana yang dikunci di seluruh `SavingsGoal` melebihi total saldo akun tabungan aktual.
  - Tampil responsif di mobile 360px & desktop 1280px (light/dark) lengkap dengan `x-progress-bar`.

---

### Prioritas #5 — `IMP-05`: Pencarian Terpadu Lintas Modul (*Global Household Search* `Ctrl+K`)

- **Modul Terdampak:** Core (`app/Core/Search/`) + Adapter di Finance, Organizer, dan Asset
- **Latar Belakang Masalah:**
  - Setelah beberapa bulan pemakaian, satu rumah tangga akan memiliki ratusan transaksi, puluhan catatan bersama/privat, daftar belanja, riwayat servis, dokumen garansi/STNK, dan nomor WhatsApp teknisi. Mencari "servis kompor", "token listrik", atau "dr. Anak" saat ini mengharuskan user menebak menu modulnya terlebih dahulu.
- **Ruang Lingkup Solusi:**
  1. **Kontrak `SearchSource` & `SearchAggregator` di Core:** mengikuti pola arsitektur `CalendarAggregator` dan `AlertAggregator` (`$this->app->tag([...], 'search.sources')`) sehingga Core tidak pernah melakukan query langsung ke tabel modul domain (§4.3).
  2. **Cakupan Sumber per Modul Aktif:**
     - *Finance:* Transaksi (payee, deskripsi, catatan), Tagihan Rutin, Pinjaman.
     - *Organizer:* Tugas, Catatan (judul & isi), Daftar Belanja, Acara Kalender.
     - *Asset:* Aset (nama, merek, model, nomor seri), Dokumen, Laporan Kerusakan, Vendor/Teknisi.
  3. **UI Cepat Mobile & Desktop:** tombol ikon kaca pembesar di Top Bar mobile + pintasan keyboard `Ctrl+K` / `Cmd+K` di desktop menggunakan komponen `x-bottom-sheet` / `<dialog>` native dengan `wire:model.live.debounce.300ms`, dikelompokkan per modul (maks 5 hasil teratas per grup agar instan).
  4. **Keamanan & Performa SQLite/MariaDB:** menggunakan query terindeks portabel tanpa ekstensi FTS spesifik DB, wajib di bawah konteks `HouseholdScope`, dan **wajib menegakkan aturan privasi catatan `BR-ORG-010`** (catatan privat milik pasangan tidak boleh bocor melalui pencarian maupun cuplikan teks).
- **Dampak Skema & Paket:**
  - Penambahan composite index pencarian teks bila diperlukan (nama index ≤64 karakter).
  - **0 paket baru & 0 daemon tambahan** di Odroid-C2.
- **Acceptance Criteria (AC):**
  - Modul yang dimatikan pada `config/modules.php` otomatis hilang dari hasil pencarian.
  - Pengujian keamanan membuktikan: (a) data household B tidak pernah muncul pada pencarian household A, dan (b) catatan privat milik Owner tidak muncul saat Partner mencari kata kunci yang sama persis.

---

### Prioritas #6 — `IMP-06`: Langganan Kalender Live `.ics` (`webcal://`) Bertoken & Libur Nasional

- **Modul Terdampak:** Organizer (`CalendarExportController`, Pengaturan Kalender, `CalendarSource`)
- **Latar Belakang Masalah:**
  - Tercatat di kolom *Ditunda (v1.1+)* pada [spesifikasi Organizer §2](02-MODULE-ORGANIZER.md#2-scope): *"URL langganan .ics (subscribe)"* dan *"Libur nasional & cuti bersama"*. Saat ini ekspor `.ics` (`ORG-11`) hanya berupa unduhan file statis sekali waktu, sehingga jadwal cicilan, jatuh tempo pajak kendaraan, atau acara baru tidak tersinkron otomatis ke widget kalender bawaan Android/iOS pasangan.
- **Ruang Lingkup Solusi:**
  1. **Live Feed `.ics` 1-Arah Bertoken Pribadi:** setiap member household dapat men-generate tautan langganan privat `/calendar/feed/{token}.ics` (token acak 64 karakter, hanya `token_hash` SHA-256 yang disimpan di DB; tetap 1-arah *read-only* sehingga patuh Non-Goal §1.2).
  2. **Kontrol Privasi & Pencabutan Seketika:** tombol *"Putar Ulang (Rotate) Token"* dan *"Matikan Tautan"* di halaman Kalender/Pengaturan; token otomatis tidak berlaku jika user dikeluarkan dari household, akun dinonaktifkan (`disabled_at`), atau household dihapus. Secara default nominal rupiah Finance tetap disembunyikan dari judul/deskripsi event kalender eksternal.
  3. **Efisiensi Server Rumah:** respons dilengkapi HTTP `ETag` / `Cache-Control` wajar (mis. 15–30 menit) dan rate limiter agar polling Google/Apple Calendar tidak membebani PHP-FPM Odroid-C2.
  4. **Sumber Kalender Libur Nasional Indonesia:** `HolidayCalendarSource` opsional berbasis file data statis lokal bertahun (mudah diperbarui tanpa koneksi API publik) yang dapat dinyalakan/dimatikan lewat filter chip kalender.
- **Dampak Skema & Paket:**
  - Tabel `org_calendar_feeds` (`id` ULID, `household_id`, `user_id`, `token_hash` unik, `include_finance_amounts` bool default false, `last_accessed_at`, `revoked_at`, timestamps).
  - **0 paket baru** (`spatie/icalendar-generator` 3.x sudah tersedia).
- **Acceptance Criteria (AC):**
  - Mengakses URL feed dengan token tidak valid, sudah di-rotate, milik user nonaktif, atau member yang sudah keluar dari household menghasilkan `404 Not Found`.
  - Output feed lolos validasi RFC 5545, all-day memakai `VALUE=DATE` dengan `DTEND` eksklusif sesuai zona waktu household.

---

### Prioritas #7 — `IMP-07`: Prediksi Servis Berbasis Laju Meter, Analisis TCO, & Label QR Aset

- **Modul Terdampak:** Asset (`app/Modules/Asset/`), integrasi `CalendarSource` & `FinanceGateway`
- **Latar Belakang Masalah:**
  - Merangkum tiga fitur pasca-MVP yang telah dirancang arahnya di [spesifikasi Asset §2](03-MODULE-ASSET.md#2-scope): *(1) Estimasi tanggal dari rata-rata pemakaian meter*, *(2) Total Cost of Ownership (TCO)*, dan *(3) QR label per aset*. Sangat membantu rumah tangga ketika riwayat odometer kendaraan dan servis elektronik mulai terkumpul.
- **Ruang Lingkup Solusi:**
  1. **Prediksi Tanggal Jatuh Tempo dari Laju Meter:** menghitung rata-rata laju pemakaian harian (`daily_rate`) dari minimal 3 pencatatan `ast_meter_logs` terakhir dalam 180 hari (tetap memperhitungkan `cumulative_offset` pada kasus reset odometer `BR-AST-003`). Dari sisa km/jam menuju target `MaintenancePlan`, sistem mengestimasi `estimated_due_date` sehingga perawatan berbasis meter pun dapat muncul proaktif di Kalender & Rencana Bulanan Finance.
  2. **Kartu Total Cost of Ownership (TCO):** pada halaman detail aset (`/assets/{asset}`), menampilkan ringkasan biaya siklus hidup aset (`harga_beli + akumulasi_biaya_servis + akumulasi_perpanjangan_dokumen − nilai_jual_pelepasan`) beserta rata-rata biaya kepemilikan per bulan menggunakan visualisasi bar proporsional CSS yang ringan (tanpa memaksa library chart berat sebelum lisensi ApexCharts diputuskan).
  3. **Cetak Label QR Internal Aset:** halaman cetak stiker rapi (`@media print`) berisi nama aset, lokasi, kode singkat, dan QR SVG lokal yang mengarah ke URL terautentikasi `/assets/{asset}` agar suami/istri cukup memindai stiker di unit AC/mesin cuci untuk langsung melihat histori servis & nomor WhatsApp teknisi langganan.
- **Dampak Skema & Paket:**
  - Kolom cache opsional `estimated_due_date` pada `ast_maintenance_plans` yang diperbarui oleh `UpdateMeter` dan `assets:refresh-due-dates`.
  - Generator QR: hanya menggunakan generator SVG ringan bila disetujui melalui ADR kecil, atau ditunda sub-fitur QR-nya jika reviewer memilih tanpa paket baru.
- **Acceptance Criteria (AC):**
  - Aset dengan <2 pembacaan meter atau laju pemakaian 0 tidak menghasilkan pembagian nol (*division by zero*) dan tetap menampilkan status berbasis sisa meter murni.
  - Pemindaian URL aset oleh pihak luar atau member household lain tetap ditolak (404/redirect login) sesuai kebijakan keamanan `SEC-4`.

---

### Prioritas #8 — `IMP-08`: Impor CSV Mutasi Bank, Rekonsiliasi Semi-Otomatis, & Laporan Cetak PDF

- **Modul Terdampak:** Finance (`/finance/transactions/import`, `/finance/reports`)
- **Latar Belakang Masalah:**
  - Tercatat di kolom *Ditunda (v1.1+)* pada [spesifikasi Finance §2](01-MODULE-FINANCE.md#2-scope): *"Import CSV mutasi bank"* dan *"Laporan PDF"*. Ditempatkan pada **prioritas paling rendah (#8)** karena alur utama Family Hub mengutamakan pencatatan cepat ≤3 tap saat kejadian berlangsung + OCR struk lokal, sehingga impor CSV dan cetak PDF hanya dibutuhkan secara periodik (akhir bulan/tahun) oleh sebagian pengguna.
- **Ruang Lingkup Solusi:**
  1. **Wizard Impor CSV Mutasi Rekening (3 Langkah):**
     - *Langkah 1:* Pilih `Account` tujuan dan unggah file `.csv` (batas ukuran ketat maks 2 MB / 1.000 baris per unggahan demi menjaga memori PHP-FPM di Odroid-C2 2 GB).
     - *Langkah 2:* Pemetaan kolom fleksibel (Tanggal, Deskripsi/Berita, Nominal Masuk/Keluar atau Debit/Kredit) dengan preset format CSV bank populer Indonesia, diproses lokal via `maatwebsite/excel` (sudah termasuk stack resmi §3 — **bukan** koneksi langsung/scraping ke bank sehingga patuh Non-Goal §1.2).
     - *Langkah 3 (Preview & Deduplikasi):* Sistem menandai baris yang diduga duplikat dengan transaksi yang sudah dicatat manual/pasangan (nominal sama persis pada akun yang sama dalam rentang ±2 hari), mengisi saran kategori otomatis via `CategorySuggestionService`, dan hanya menyimpan baris yang dicentang user dalam satu transaksi database.
  2. **Laporan Keuangan Bulanan Ramah Cetak / Save-as-PDF:** menambahkan mode cetak bersih (`@media print` stylesheet khusus A4 potret dengan tipografi serif/tabular-nums rapi, ringkasan 5 alokasi, realisasi anggaran, dan arus kas periode aktif) sehingga user dapat langsung menyimpan laporan PDF dari browser HP/laptop **tanpa memasang engine PDF/headless browser berat di server Linux**.
- **Dampak Skema & Paket:**
  - Kolom opsional `import_hash` (nullable, indexed) pada `fin_transactions` untuk mencegah impor ulang file CSV yang sama dua kali.
  - **0 paket baru** (`maatwebsite/excel` 4.x sudah disetujui dan terpasang).
- **Acceptance Criteria (AC):**
  - Mengunggah kembali file CSV yang sama persis menandai seluruh baris sebagai duplikat secara default sehingga tidak menggandakan mutasi saldo rekening.
  - File salah format, nominal tidak valid, atau ukuran melebihi batas gagal tertutup dengan pesan Bahasa Indonesia yang jelas tanpa membuat transaksi parsial.

---

## 4. Urutan Eksekusi & Ketergantungan Tahap Pasca-MVP

Seluruh fitur di atas **tidak memblokir rilis awal v1.0.0** (Phase 4 UAT & Go-Live tetap diselesaikan lebih dulu). Urutan pengerjaan yang direkomendasikan setelah v1.0.0 stabil:

```
[Rilis v1.0.0 Lulus UAT]
        │
        ▼
Gelombang 1 (v1.1 — Keandalan, Keamanan, & Friksi Harian Utama)
  1. IMP-01 (Scheduler penuh, Daily Digest, Purge 30 hari)
  2. IMP-02 (2FA Email OTP & Recovery Codes — setelah Q-M12 diputuskan)
  3. IMP-03 (Split Transaksi Multi-Kategori)
  4. IMP-04 (Kantong Tabungan Tujuan / FIN-V11-1)
        │
        ▼
Gelombang 2 (v1.2 — Produktivitas Lintas Modul & Otomasi Cerdas)
  5. IMP-05 (Pencarian Terpadu Lintas Modul Ctrl+K)
  6. IMP-06 (Langganan Kalender Live .ics Bertoken & Libur Nasional)
  7. IMP-07 (Prediksi Servis Meter, Analisis TCO, & Label QR Aset)
        │
        ▼
Gelombang 3 (v1.3 — Utilitas Akhir Periode)
  8. IMP-08 (Impor CSV Mutasi Bank, Deduplikasi, & Laporan Cetak PDF)
```

### Aturan Pengerjaan Setiap Task `IMP-*`

1. Tetap ikuti aturan **satu task ID per commit** di `main` (sesuai keputusan alur kerja `LOG-035`) setelah reviewer memberi persetujuan mulai.
2. Untuk perubahan yang menyentuh UI/browser (`IMP-02` s.d. `IMP-08`), wajib menjalankan alur terkoordinasi `premium-frontend-ui` → `web-design-reviewer` → `webapp-testing` pada viewport `360px` dan `1280px` (light & dark).
3. Setiap penyelesaian task wajib lulus `composer test` (SQLite & MariaDB CI), `npm test`, `composer lint` (Pint), `composer analyse` (Larastan Level 6), `npm run build`, serta disinkronkan ke `docs/logbook.md`, `docs/development-plan.md`, dan `docs/pending.md`.
