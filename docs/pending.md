# Daftar Tertunda dan Keputusan Reviewer - Family Hub

**Tujuan:** satu tempat untuk semua hal yang belum selesai dan butuh masukan reviewer: konflik spesifikasi, keputusan desain, pekerjaan yang sengaja ditunda, dan bukti yang masih menunggu. Daftar ini adalah ringkasan; bukti dan riwayat tetap ada di [logbook](logbook.md).

**Aturan pemakaian:**

1. Setiap batch perubahan wajib memeriksa daftar ini dan memperbarui item yang tersentuh, termasuk menambah item baru dan menutup item yang sudah diputuskan.
2. Setiap item punya ID stabil (`P-NN`), status, dan satu langkah berikutnya yang jelas.
3. Item yang butuh keputusan reviewer ditulis di bagian A dan dilaporkan juga di jawaban akhir batch.
4. Keputusan reviewer dicatat di logbook (`LOG-NNN`), lalu item di sini ditutup atau diubah menjadi pekerjaan tertunda di bagian B.
5. Jangan menandai item selesai hanya karena kode sudah ada; sertakan bukti verifikasi atau keputusan eksplisit.

**Status:** diperbarui terakhir pada LOG-037 (Playwright jadi alat utama verifikasi browser).

## A. Butuh keputusan reviewer

| ID | Topik | Keputusan yang dibutuhkan | Dampak bila ditunda |
|---|---|---|---|
| P-04 | Invite household baru oleh admin | `users.is_system_admin` sudah ada, tetapi belum ada policy, route, action, atau halaman `/admin`. Tentukan target kartu (CORE-05 atau CORE-15) dan apakah admin boleh membuat household tanpa menjadi anggota | Alur A pada §5.1 (admin membuat invite household baru) belum bisa diimplementasikan |
| P-05 | Backfill histori periode keuangan | `CreateHousehold` hanya membuat satu baris periode berlaku hari ini. Tentukan apakah perlu periode lampau saat bootstrap dan sejauh mana | `PeriodResolver` aman untuk data baru, tetapi belum ada data historis household lama |
| P-06 | `Activity` tanpa `household_id` dan `HouseholdMember` tanpa trait tenant | §2.4 masterplan menyebut setiap tabel domain punya `household_id`. Konfirmasi pengecualian permanen (audit global; membership di-scope eksplisit) atau setujui kolom/trait tambahan | Ketidakpastian pada uji isolasi lintas modul dan ekspor data |
| P-07 | Bukti konkurensi penerimaan slot | Saat ini hanya ada penalaran unique `(household_id, slot)` + row lock, tanpa test proses paralel nyata. Tentukan apakah perlu test paralel SQLite/MariaDB sebelum CORE-04 ditutup | AC CORE-04 "test konkurensi" belum terbukti secara empiris |

## B. Pekerjaan tertunda (tidak menahan batch saat ini)

| ID | Topik | Status / alasan ditunda | Langkah berikutnya |
|---|---|---|---|
| P-08 | Bootstrap konteks tenant untuk job/command | Ditunda karena belum ada job/command nyata (LOG-030). Job nanti wajib membawa household ID eksplisit dan memasang konteks sendiri | Bangun base/middleware job saat job pertama (CORE-09/CORE-10) dibuat |
| P-09 | Bukti CI untuk commit setelah `731fb45` | Menunggu laporan reviewer; repo privat tanpa GitHub CLI/API terautentikasi. `d077170` (LOG-036) sudah di-push dan workflow akan berjalan otomatis, tetapi hasilnya belum bisa dibaca dari mesin ini | Catat hasil SQLite/MariaDB, tutup item bila hijau; jangan sebut LOG-036 lulus CI sebelum ada laporan |
| P-10 | Branch lama `core-01-foundation` dan `core-03-support` | Sejak LOG-035 semua pekerjaan langsung di `main`; `core-03-support` tidak lagi disinkronkan dan tertinggal di `28ebeb2`, `core-01-foundation` tetap di `42bda38`. Keduanya sudah menjadi ancestor `main` | Putuskan hapus branch remote atau biarkan sebagai arsip |
| P-12 | Modul Finance/Organizer/Asset | `config/modules.php` menonaktifkan ketiganya; belum ada izin reviewer untuk fitur modul | Tunggu keputusan reviewer atas task modul |
| P-13 | Undangan ganda yang masih pending | `InviteHouseholdMember` tidak mencegah lebih dari satu undangan pending per household; token kedua otomatis gagal karena slot penuh | Putuskan bila perlu undangan tunggal atau pencabutan otomatis saat undangan baru dibuat |
| P-14 | Rate limit dan pengiriman email undangan | `InviteHouseholdMember` belum membatasi 10 undangan/jam sesuai SEC-3 dan mengirim `HouseholdInvitationMail` sinkron setelah commit; kegagalan mail meninggalkan undangan pending yang tidak pernah sampai ke penerima | Tambahkan limiter saat endpoint undang dibangun dan pindahkan pengiriman ke antrean/retry saat CORE-10 |
| P-15 | Throttle dan timing permintaan reset kata sandi | Pesan sudah seragam untuk email terdaftar maupun tidak, tetapi form forgot-password belum punya limiter per IP dan email terdaftar merespons lebih lambat karena pengiriman mail | Putuskan throttle dan respons waktu seragam sebelum rilis; catat pada review keamanan CORE-14 |
| P-16 | Status Playwright sebagai alat verifikasi | Keputusan LOG-037: Playwright dipakai lebih dulu, tetapi `playwright-core` hanya dipasang di direktori sementara dan memakai Chrome sistem, jadi tidak ada dependency repo, lockfile, atau job CI. Tentukan apakah ini permanen atau Playwright dipromosikan menjadi devDependency + langkah CI browser | Selama tertunda, setiap batch UI memasang ulang `playwright-core` di `%TEMP%` dan bukti browser tetap lokal, bukan bukti CI |

## Riwayat penutupan item

| ID | Ditutup oleh | Catatan |
|---|---|---|
| P-01 | LOG-034 | Reviewer memutuskan dokumentasi jadi acuan; migrasi/model/aksi undangan diselaraskan ke §5.1 (`household_id` nullable, `email`, `type`, `revoked_at`), `accepted_by` dihapus karena tidak ada di spesifikasi |
| P-02 | LOG-036 | Reviewer memutuskan email undangan membawa tautan ke layar buat kata sandi; pengiriman tautan membuat akun household, dan `AcceptInvitation` menolak token bila email akun tidak cocok dengan email undangan |
| P-03 | LOG-036 | Reviewer memutuskan reset kata sandi masuk CORE-05: `Features::resetPasswords()` aktif dengan view proyek sendiri dan jawaban seragam untuk email tak dikenal |
| P-11 | LOG-036 | Route `invitation.show`, halaman penerimaan Livewire, email undangan, dan copy Indonesia tersedia serta diuji pada 360px/1280px light/dark; rate limit undangan dipisah ke P-14 |
