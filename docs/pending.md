# Daftar Tertunda dan Keputusan Reviewer - Family Hub

**Tujuan:** satu tempat untuk semua hal yang belum selesai dan butuh masukan reviewer: konflik spesifikasi, keputusan desain, pekerjaan yang sengaja ditunda, dan bukti yang masih menunggu. Daftar ini adalah ringkasan; bukti dan riwayat tetap ada di [logbook](logbook.md).

**Aturan pemakaian:**

1. Setiap batch perubahan wajib memeriksa daftar ini dan memperbarui item yang tersentuh, termasuk menambah item baru dan menutup item yang sudah diputuskan.
2. Setiap item punya ID stabil (`P-NN`), status, dan satu langkah berikutnya yang jelas.
3. Item yang butuh keputusan reviewer ditulis di bagian A dan dilaporkan juga di jawaban akhir batch.
4. Keputusan reviewer dicatat di logbook (`LOG-NNN`), lalu item di sini ditutup atau diubah menjadi pekerjaan tertunda di bagian B.
5. Jangan menandai item selesai hanya karena kode sudah ada; sertakan bukti verifikasi atau keputusan eksplisit.

**Status:** diperbarui terakhir pada LOG-056 (resolusi batch pending P-05, P-06, P-15, P-16, P-18, P-19, P-20 sesuai rekomendasi reviewer).

## A. Butuh keputusan reviewer

| ID | Topik | Keputusan yang dibutuhkan | Dampak bila ditunda |
|---|---|---|---|
| P-09 | Bukti CI untuk commit setelah `731fb45` | Menunggu laporan reviewer; repo privat tanpa GitHub CLI/API terautentikasi. `d077170` (LOG-036) dan commit sesudahnya sudah di-push dan workflow berjalan otomatis, tetapi hasilnya belum bisa dibaca dari mesin ini | Laporkan hasil SQLite/MariaDB per commit, lalu tutup item bila hijau; jangan sebut commit mana pun lulus CI sebelum ada laporan |

## B. Pekerjaan tertunda (tidak menahan batch saat ini)

| ID | Topik | Status / alasan ditunda | Langkah berikutnya |
|---|---|---|---|
| P-12 | Modul Asset (Phase 3) | `config/modules.php` mengaktifkan `finance` (Phase 1, LOG-055) dan `organizer` (Phase 2, LOG-059); `asset` dinonaktifkan menunggu fase berikutnya | Buka feature flag `asset` pada Phase 3 |
| P-21 | Konfigurasi SMTP produksi dan verifikasi pengiriman email fisik | Keputusan LOG-053: driver email memakai mode `log` (`MAIL_MAILER=log`) untuk testing lokal/clipboard dan tautan undangan langsung tersedia di UI. Kredensial SMTP nyata dipasang di Phase 4 sebelum go-live | Masukkan kredensial SMTP produksi pada Phase 4 Hardening |
| P-19 | Q-M12: 2FA Email OTP | Keputusan LOG-056: ditunda ke rilis v1.1. Untuk rilis awal keluarga (MVP), keamanan mengandalkan password hash Argon2id/Bcrypt, rate limiting per IP/email, dan pendaftaran tertutup via token undangan | Evaluasi implementasi 2FA email OTP pada siklus v1.1 |

## Riwayat penutupan item

| ID | Ditutup oleh | Catatan |
|---|---|---|
| P-05 | LOG-056 | Reviewer menyetujui rekomendasi Opsi A: tidak perlu backfill periode keuangan lampau saat bootstrap household. `PeriodResolver` secara deterministik menghitung periode lampau dari setting awal jika ada transaksi bertanggal lampau |
| P-06 | LOG-056 | Reviewer menyetujui rekomendasi Opsi A: pengecualian permanen kolom `household_id` pada tabel `activity_log` (Spatie audit global berbasis polymorphic ULID). `HouseholdMember` tetap di-scope ketat via composite unique constraint `(household_id, slot)` dan foreign key `user_id` |
| P-15 | LOG-056 | Rate limiter 5 permintaan per menit per IP diterapkan pada endpoint `POST /forgot-password` melalui `RateLimiter::for('forgot-password')` di `FortifyServiceProvider` dan middleware `throttle:forgot-password` di `routes/web.php`, diverifikasi dengan feature test |
| P-16 | LOG-056 | `@playwright/mcp` dipasang secara global (`/usr/local/bin/playwright-mcp`) dan didaftarkan ke `.vscode/mcp.json` dengan flag `--no-sandbox`. Repository tetap bersih tanpa dependensi tambahan di `package.json` |
| P-18 | LOG-056 | Reviewer menyetujui kebijakan default reminder (Q-M9): maksimal 3 kali percobaan dengan staged backoff (5m, 15m, 30m), status terminal `failed`, dan kegagalan terisolasi per penerima |
| P-20 | LOG-056 | Reviewer menyetujui kebijakan retensi backup terenkripsi (Q-M11): disetel di `config/backup.php` (7 hari harian, 4 minggu mingguan, 6 bulan bulanan, 1 tahun tahunan; clean otomatis via scheduler 01:30) |
| P-08 | LOG-048 | Helper `HouseholdContext::runInContext(Household $household, callable $callback)` dibuat dan digunakan oleh `ReminderService` serta command scheduler `reminders:dispatch` untuk mengeksekusi delivery di bawah konteks tenant household terkait tanpa membocorkan state |
| P-14 | LOG-049 | `HouseholdInvitationMail` mengimplementasikan `ShouldQueue` (CORE-10) sehingga pengiriman email undangan berpindah ke antrean asinkron dan kegagalan mail driver tidak membatalkan atau memblokir transaksi undangan |
| P-01 | LOG-034 | Reviewer memutuskan dokumentasi jadi acuan; migrasi/model/aksi undangan diselaraskan ke §5.1 (`household_id` nullable, `email`, `type`, `revoked_at`), `accepted_by` dihapus karena tidak ada di spesifikasi |
| P-02 | LOG-036 | Reviewer memutuskan email undangan membawa tautan ke layar buat kata sandi; pengiriman tautan membuat akun household, dan `AcceptInvitation` menolak token bila email akun tidak cocok dengan email undangan |
| P-03 | LOG-036 | Reviewer memutuskan reset kata sandi masuk CORE-05: `Features::resetPasswords()` aktif dengan view proyek sendiri dan jawaban seragam untuk email tak dikenal |
| P-11 | LOG-036 | Route `invitation.show`, halaman penerimaan Livewire, email undangan, dan copy Indonesia tersedia serta diuji pada 360px/1280px light/dark; rate limit undangan dipisah ke P-14 |
| P-07 | LOG-039 | AC konkurensi CORE-04 diuji deterministik: unique `(household_id, slot)` menolak anggota ketiga tanpa guard aplikasi, race yang kalah gagal tertutup dengan pesan `household.invite_full` (bukan 500), dan slot unik per household. Tidak ada test proses paralel OS-level; batas itu dicatat di logbook |
| P-04 | LOG-041 | Reviewer memutuskan akun system admin murni akun manajemen: boleh membuat invite household baru tanpa menjadi anggota, dan pembuatan invite admin tetap milik CORE-05 sesuai lingkup masterplan (CORE-15 hanya panel + uji batas akses). Penerimaan undangan dan pembuatan household oleh akun admin kini ditolak. Alur A belum dibangun; jalur admin pertama tetap P-17 |
| P-10 | LOG-041 | Branch remote `core-01-foundation` (`42bda38`) dan `core-03-support` (`28ebeb2`) dihapus, begitu juga salinan lokalnya. Hanya `main` yang tersisa di remote |
| P-13 | LOG-041 | Hanya satu undangan aktif per household: `InviteHouseholdMember` mencabut undangan pending sebelumnya di dalam transaksi yang sama, sehingga token lama tidak bisa dipakai lagi |
| P-17 | LOG-043 | Command Artisan `php artisan core:create-admin` tersedia untuk membuat atau mempromosikan akun admin pertama dengan validasi email/password dan penolakan akun yang sudah memiliki membership household |
