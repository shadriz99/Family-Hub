# Runbook Deploy — Family Hub

**Status:** Draft untuk diselaraskan dengan instalasi Linux bare-metal aktual.  
**Rujukan:** [Masterplan](../00-MASTERPLAN.md) §12–14.

Riwayat pekerjaan dan bukti verifikasi dicatat di [logbook](../logbook.md); urutan implementasi dan gate CORE-02/CORE-16 mengikuti [rencana development](../development-plan.md). Runbook ini belum merupakan bukti deployment berhasil.

Scaffold saat ini hanya berjalan lokal melalui `php artisan serve` pada loopback; ini bukan Nginx/PHP-FPM/zrok atau deployment produksi. Jangan memakai konfigurasi session/cache berbasis file pada scaffold sebagai keputusan akhir operasional; verifikasi konfigurasi produksi sesuai implementasi sebelum deploy.

LOG-023: reviewer memilih SQLite dahulu dan menunda MariaDB. CI aktif main hanya SQLite; kompatibilitas MariaDB untuk perubahan lanjutan belum terverifikasi. Main memuat fondasi CORE-01, bukan checkpoint CORE-03/04 dari branch support. Keputusan merge ini bukan izin deploy/migrate atau bukti UAT.

Login/logout dan konfirmasi kata sandi dasar tersedia (LOG-011), tetapi belum ada alur provisioning admin/undangan atau skema household. Tidak ada akun persisten yang disediakan oleh setup ini. Jangan membuka akses publik atau menjalankan migrasi placeholder untuk membuat akun pertama; tunggu implementasi Core dan gate rilis.

Runbook ini berlaku untuk deploy langsung di Linux bare-metal dengan Nginx, PHP-FPM, Laravel, dan zrok sebagai akses publik. Tidak menggunakan Docker. Jangan menyalin command, path, atau nama service sebelum disesuaikan dengan distro Linux, konfigurasi Nginx/PHP-FPM, dan versi aplikasi aktual. Jika prasyarat go-live belum terpenuhi, hentikan deploy.

## 1. Data lingkungan produksi

Catat nilai operasional di password manager atau tempat privat, bukan di Git:

| Informasi | Nilai |
|---|---|
| Host/server dan akses administrator | Isi sebelum deploy |
| Domain/URL HTTPS melalui zrok | Isi sebelum deploy |
| Metode akses jarak jauh | zrok untuk rilis awal; Cloudflare Tunnel dipertimbangkan saat skala membesar |
| Distro/version Linux dan lokasi repo | Isi setelah host ditetapkan |
| Nginx site config dan PHP-FPM socket/pool | Isi setelah instalasi |
| Zona waktu server | Catat nilai aktual; zona waktu household dikonfigurasi di aplikasi |
| Lokasi backup lokal dan off-site | Isi setelah target ditentukan |
| Kontak administrator/penerima alert | Isi sebelum go-live |

Jangan masukkan password, token, `APP_KEY`, kredensial SMTP, kunci VAPID, atau secret tunnel ke dokumen ini.

## 2. Prasyarat rilis

- [ ] Rilis berasal dari commit/tag yang diketahui; catat ID rilis dan waktu deploy.
- [ ] CI lulus: test, Pint, Larastan, serta kompatibilitas DB yang menjadi target produksi.
- [ ] Catatan rilis dan migrasi telah ditinjau; seluruh migrasi baru memiliki `down()`.
- [ ] Backup produksi terbaru berhasil dan pemulihan pernah diuji.
- [ ] Linux bare-metal, Nginx, PHP-FPM, ekstensi PHP, Composer, Node/build assets (bila dibutuhkan), dan kapasitas disk sesuai kebutuhan aplikasi.
- [ ] zrok client berjalan sebagai service yang terkelola; HTTPS publik, host, dan forwarded headers diuji end-to-end.
- [ ] Port PHP-FPM, DB, serta listener internal tidak diekspos langsung ke internet; akses admin SSH dibatasi.
- [ ] Secret produksi disimpan di `.env`/secret store di server, memiliki izin file terbatas, dan tidak masuk source control/log.
- [ ] SMTP uji berhasil; alamat admin untuk kegagalan backup tersedia.
- [ ] Q-M11 (retensi/penghapusan backup) telah diputuskan sebelum menyimpan data pengguna nyata.

Jangan melakukan migrasi database produksi sebelum ada backup terverifikasi.

## 3. Deploy pertama

1. Siapkan host dan direktori aplikasi dengan izin minimum yang diperlukan.
2. Ambil source pada tag/commit rilis yang disetujui. Jangan deploy dari branch kerja yang berubah-ubah.
3. Buat konfigurasi produksi dari `.env.example` secara lokal di server. Set minimum:
   - `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` HTTPS;
   - `SESSION_SECURE_COOKIE=true`;
   - kredensial DB/SMTP, konfigurasi mail, VAPID, dan konfigurasi zrok sesuai keputusan final;
   - `BACKUP_ARCHIVE_PASSWORD` yang kuat dan tersimpan terpisah dari arsip backup.
4. Siapkan direktori persisten untuk database (SQLite bila dipilih), `storage/`, file privat, log, dan backup. Atur owner/group minimum yang diperlukan untuk Nginx/PHP-FPM; jangan membuat file sensitif world-readable.
5. Konfigurasikan Nginx ke document root `public/` dan PHP-FPM melalui socket/listener lokal. Pastikan hanya Nginx dan endpoint publik zrok yang menjadi jalur request; tolak akses langsung ke file konfigurasi, source, storage privat, dan dotfiles.
6. Pasang source pada rilis yang disetujui, install dependency produksi dari lockfile, dan build frontend sesuai prosedur implementasi. Jangan menjalankan aplikasi sebagai root.
7. Konfigurasikan worker, scheduler, PHP-FPM, Nginx, dan zrok client sebagai service Linux dengan restart policy dan log yang dapat diperiksa. Catat nama unit dan command aktual di runbook setelah diverifikasi.
8. Jalankan migrasi satu kali menggunakan user aplikasi; jangan menjalankannya serentak dari beberapa proses.
9. Jalankan cache/config/asset optimization sesuai dokumentasi versi aplikasi.
10. Buat akun admin/household pertama melalui jalur onboarding yang disetujui. Jangan mengaktifkan registrasi publik.
11. Pastikan Nginx, PHP-FPM, worker, scheduler, zrok client, dan DB sehat sebelum membuka akses pengguna.

Command instalasi dan nama unit systemd harus ditambahkan setelah distro serta konfigurasi host diketahui dan prosedur diuji.

## 4. Deploy pembaruan

1. Catat versi aktif dan pastikan backup terbaru sukses.
2. Tinjau changelog, perubahan konfigurasi, migrasi, dan dampak kompatibilitas.
3. Ambil rilis baru pada direktori/release terpisah atau mekanisme yang mempertahankan versi sebelumnya. Jangan menimpa source aktif tanpa jalur kembali.
4. Ikuti urutan update yang diuji: hentikan/drain worker bila migrasi memerlukan, jalankan migrasi satu kali dengan user aplikasi, lalu restart proses worker/scheduler dan reload service yang perlu.
5. Periksa health check, log startup, status queue/scheduler, login, dan operasi baca/tulis penting.
6. Catat hasil, waktu, versi sebelum/sesudah, dan masalah yang ditemukan.

### Rollback

- Rollback kode hanya dilakukan jika versi aplikasi sebelumnya kompatibel dengan skema database yang sudah termigrasi.
- Jangan menjalankan `migrate:rollback` secara spontan di produksi. Migrasi yang sudah menyentuh data dapat menyebabkan kehilangan atau inkonsistensi.
- Jika migrasi tidak kompatibel atau data berubah, hentikan perubahan lanjutan dan ikuti prosedur restore § `backup-restore.md` setelah memahami kehilangan data sejak backup.
- Catat insiden dan penyebab sebelum deploy ulang.

## 5. Pemeriksaan sesudah deploy

- [ ] URL HTTPS publik melalui zrok dan forwarded headers valid end-to-end; pastikan informasi scheme/host yang dilihat Laravel benar.
- [ ] Login, logout, invite-only, dan pemulihan sesi bekerja.
- [ ] App/worker/scheduler/DB sehat; tidak ada exception berulang atau job tertahan.
- [ ] Scheduler menjalankan reminder, backup, purge, dan job modul pada jadwal yang benar.
- [ ] Email undangan dan notifikasi uji diterima.
- [ ] File privat dapat dibuka oleh pemilik yang berwenang dan ditolak untuk user/household lain.
- [ ] PWA dapat diperbarui; logout membersihkan cache sensitif.
- [ ] Backup pasca-deploy berhasil dan dapat ditemukan di target yang dikonfigurasi.

## 6. Pemantauan dan eskalasi

Periksa secara berkala: kapasitas disk, status backup/off-site, umur backup terakhir, queue gagal/tertunda, ketersediaan HTTPS publik melalui zrok, service Nginx/PHP-FPM/zrok, dan notifikasi admin. Pada dugaan kebocoran atau korupsi data: batasi akses, jangan menghapus log/backup, dokumentasikan waktu dan gejala, lalu pulihkan hanya setelah cakupan dampak diketahui.

---

## Keputusan yang wajib ditutup

- Q-M1: indikator skala untuk migrasi zrok ke Cloudflare Tunnel.
- Q-M2: distro/version, kapasitas host, lokasi direktori, dan unit service aktual.
- Q-M3: penyedia SMTP dan prosedur rotasi kredensial.
- Q-M11: retensi backup, lokasi off-site, serta penghapusan data dari backup.
- Pilihan DB produksi: SQLite atau MariaDB, termasuk path database, backup konsisten, dan prosedur restore aktual.
