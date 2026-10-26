# Daftar Tertunda dan Keputusan Reviewer - Family Hub

**Tujuan:** satu tempat untuk semua hal yang belum selesai dan butuh masukan reviewer: konflik spesifikasi, keputusan desain, pekerjaan yang sengaja ditunda, dan bukti yang masih menunggu. Daftar ini adalah ringkasan; bukti dan riwayat tetap ada di [logbook](logbook.md).

**Aturan pemakaian:**

1. Setiap batch perubahan wajib memeriksa daftar ini dan memperbarui item yang tersentuh, termasuk menambah item baru dan menutup item yang sudah diputuskan.
2. Setiap item punya ID stabil (`P-NN`), status, dan satu langkah berikutnya yang jelas.
3. Item yang butuh keputusan reviewer ditulis di bagian A dan dilaporkan juga di jawaban akhir batch.
4. Keputusan reviewer dicatat di logbook (`LOG-NNN`), lalu item di sini ditutup atau diubah menjadi pekerjaan tertunda di bagian B.
5. Jangan menandai item selesai hanya karena kode sudah ada; sertakan bukti verifikasi atau keputusan eksplisit.

**Status:** diperbarui terakhir pada LOG-041 (satu undangan aktif, admin bukan anggota household, branch lama dihapus).

## A. Butuh keputusan reviewer

| ID | Topik | Keputusan yang dibutuhkan | Dampak bila ditunda |
|---|---|---|---|
| P-05 | Backfill histori periode keuangan | `CreateHousehold` hanya membuat satu baris periode berlaku hari ini. Tentukan apakah perlu periode lampau saat bootstrap dan sejauh mana | `PeriodResolver` aman untuk data baru, tetapi belum ada data historis household lama |
| P-06 | `Activity` tanpa `household_id` dan `HouseholdMember` tanpa trait tenant | §2.4 masterplan menyebut setiap tabel domain punya `household_id`. Konfirmasi pengecualian permanen (audit global; membership di-scope eksplisit) atau setujui kolom/trait tambahan | Ketidakpastian pada uji isolasi lintas modul dan ekspor data |
| P-09 | Bukti CI untuk commit setelah `731fb45` | Menunggu laporan reviewer; repo privat tanpa GitHub CLI/API terautentikasi. `d077170` (LOG-036) dan commit sesudahnya sudah di-push dan workflow berjalan otomatis, tetapi hasilnya belum bisa dibaca dari mesin ini | Laporkan hasil SQLite/MariaDB per commit, lalu tutup item bila hijau; jangan sebut commit mana pun lulus CI sebelum ada laporan |
| P-15 | Throttle dan timing permintaan reset kata sandi | Pesan sudah seragam untuk email terdaftar maupun tidak, tetapi form forgot-password belum punya limiter per IP dan email terdaftar merespons lebih lambat karena pengiriman mail | Putuskan batas throttle dan target timing, lalu catat pada review keamanan CORE-14 |
| P-16 | Status Playwright sebagai alat verifikasi | Keputusan LOG-037: Playwright dipakai lebih dulu, tetapi `playwright-core` hanya dipasang di direktori sementara dan memakai Chrome sistem, jadi tidak ada dependency repo, lockfile, atau job CI | Tentukan apakah ini permanen atau Playwright dipromosikan menjadi devDependency + langkah CI browser |
| P-17 | Pembuatan system admin pertama | `users.is_system_admin` ada, tetapi tidak ada jalur resmi untuk membuat admin pertama (seeder stok tidak disetujui, belum ada command/route). Alur A CORE-05 tidak bisa diuji end-to-end tanpa ini | Putuskan jalurnya (mis. command artisan sekali pakai atau langkah manual terdokumentasi) sebelum Alur A dikerjakan |

## B. Pekerjaan tertunda (tidak menahan batch saat ini)

| ID | Topik | Status / alasan ditunda | Langkah berikutnya |
|---|---|---|---|
| P-08 | Bootstrap konteks tenant untuk job/command | Ditunda karena belum ada job/command nyata (LOG-030). Job nanti wajib membawa household ID eksplisit dan memasang konteks sendiri | Bangun base/middleware job saat job pertama (CORE-09/CORE-10) dibuat |
| P-12 | Modul Finance/Organizer/Asset | `config/modules.php` menonaktifkan ketiganya; belum ada izin reviewer untuk fitur modul | Tunggu keputusan reviewer atas task modul |
| P-14 | Pengiriman email undangan | Limiter 10 undangan/jam sudah ada (LOG-040), tetapi `HouseholdInvitationMail` masih dikirim sinkron setelah commit; kegagalan mail meninggalkan undangan pending yang tidak pernah sampai ke penerima | Pindahkan pengiriman ke antrean/retry saat CORE-10 |

## Riwayat penutupan item

| ID | Ditutup oleh | Catatan |
|---|---|---|
| P-01 | LOG-034 | Reviewer memutuskan dokumentasi jadi acuan; migrasi/model/aksi undangan diselaraskan ke §5.1 (`household_id` nullable, `email`, `type`, `revoked_at`), `accepted_by` dihapus karena tidak ada di spesifikasi |
| P-02 | LOG-036 | Reviewer memutuskan email undangan membawa tautan ke layar buat kata sandi; pengiriman tautan membuat akun household, dan `AcceptInvitation` menolak token bila email akun tidak cocok dengan email undangan |
| P-03 | LOG-036 | Reviewer memutuskan reset kata sandi masuk CORE-05: `Features::resetPasswords()` aktif dengan view proyek sendiri dan jawaban seragam untuk email tak dikenal |
| P-11 | LOG-036 | Route `invitation.show`, halaman penerimaan Livewire, email undangan, dan copy Indonesia tersedia serta diuji pada 360px/1280px light/dark; rate limit undangan dipisah ke P-14 |
| P-07 | LOG-039 | AC konkurensi CORE-04 diuji deterministik: unique `(household_id, slot)` menolak anggota ketiga tanpa guard aplikasi, race yang kalah gagal tertutup dengan pesan `household.invite_full` (bukan 500), dan slot unik per household. Tidak ada test proses paralel OS-level; batas itu dicatat di logbook |
| P-04 | LOG-041 | Reviewer memutuskan akun system admin murni akun manajemen: boleh membuat invite household baru tanpa menjadi anggota, dan pembuatan invite admin tetap milik CORE-05 sesuai lingkup masterplan (CORE-15 hanya panel + uji batas akses). Penerimaan undangan dan pembuatan household oleh akun admin kini ditolak. Alur A belum dibangun; jalur admin pertama tetap P-17 |
| P-10 | LOG-041 | Branch remote `core-01-foundation` (`42bda38`) dan `core-03-support` (`28ebeb2`) dihapus, begitu juga salinan lokalnya. Hanya `main` yang tersisa di remote |
| P-13 | LOG-041 | Hanya satu undangan aktif per household: `InviteHouseholdMember` mencabut undangan pending sebelumnya di dalam transaksi yang sama, sehingga token lama tidak bisa dipakai lagi |
