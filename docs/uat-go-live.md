# Checklist UAT & Go-live — Family Hub

**Status:** Template; tandai hasil hanya setelah mencoba build yang berjalan.  
**Rujukan:** [Masterplan](00-MASTERPLAN.md) §12–16 dan dokumen Finance, Organizer, Asset.

## 1. Informasi UAT

LOG-023: SQLite menjadi target aktif sementara; MariaDB ditunda dan belum boleh dinyatakan kompatibel untuk perubahan baru. Main mengintegrasikan fondasi CORE-01 saja, bukan branch support CORE-03/04. Bukti CI fondasi tidak mencentang UAT/produksi; gate kedua DB lama ditunda sesuai keputusan reviewer.

LOG-025: reviewer menyatakan CORE-03 selesai dan MariaDB dikembalikan ke CI. Matriks SQLite/MariaDB dipulihkan pada branch support, tetapi belum ada run baru, belum ada bukti UAT, dan CORE-04/bootstrap household masih parsial. Checkpoint ini tidak mengubah status rilis.

LOG-026: checkpoint CORE-03/04 kini ada di `main` (`bcbbe86`) setelah push dan merge yang disetujui reviewer. Hasil CI remote belum terverifikasi dan tidak ada skenario UAT yang dijalankan; status UAT/produksi tidak berubah.

LOG-029: job SQLite dan MariaDB lulus untuk `6d6f7bc` (laporan reviewer). Status UAT/produksi tetap tidak berubah; belum ada deployment atau data nyata.

| Item | Nilai |
|---|---|
| Versi/commit yang diuji | |
| Lingkungan dan DB | |
| Tanggal mulai / selesai | |
| Penguji (owner / partner) | |
| Browser dan perangkat | |
| Data demo atau data nyata | |

Gunakan household uji dan data sintetis untuk skenario negatif. Jangan mengunggah dokumen/rahasia nyata ke lingkungan non-produksi.

Checkpoint CORE-01 membuktikan auth dasar melalui test SQLite in-memory dan browser lokal (LOG-011), kemudian CI remote SQLite/MariaDB lulus pada `ffe4afe` (LOG-014). Belum ada akun persisten, household/undangan, atau kandidat rilis; bukti fondasi ini tidak mencentang skenario UAT di bawah.

## 2. Syarat mulai UAT

LOG-019 menambahkan skema/model household lokal dan ULID dengan constraint test SQLite in-memory; ini belum menyediakan akun owner/partner persisten, policy/aksi membership, konkurensi kedua DB, atau penerimaan UAT. Checklist berikut tetap tidak dicentang.

LOG-020/021 melanjutkan policy dan action backend membership (keluar/keluarkan partner/transfer ownership) beserta audit transaksional. Test rollback migration dan rollback mutasi saat audit gagal lulus pada SQLite terisolasi; bukan pengujian UI atau konkurensi MariaDB. Akun persisten, caller halaman dan acceptance UAT tetap belum tersedia.

- [ ] Build kandidat dari commit/tag yang diketahui; CI lulus.
- [ ] Tidak ada pertanyaan keamanan/retensi yang masih menjadi blocker go-live (Q-M11, Q-M12; Q-M9 sebelum reminder aktif). Q-M1 tentang ambang migrasi zrok ke Cloudflare Tunnel bukan blocker rilis awal.
- [ ] Deploy dan rollback dipahami oleh operator.
- [ ] Backup dan restore berhasil diuji pada lingkungan terisolasi.
- [ ] Akun owner dan partner uji tersedia; setiap household memiliki paling banyak dua member.
- [ ] PWA dan HTTPS melalui zrok dapat diuji pada ponsel yang benar-benar akan digunakan.

## 3. Skenario penerimaan lintas sistem

### Akun, akses, dan privasi

- [ ] Registrasi tanpa undangan ditolak; undangan valid dapat dipakai sekali dan kedaluwarsa sesuai aturan.
- [ ] Owner dan partner dapat menggunakan alur yang memang diizinkan; admin sistem tidak dapat membaca data domain household lain.
- [ ] Mengakses ID resource household lain melalui URL, Livewire/action, pencarian, ekspor, dan file privat ditolak.
- [ ] Perangkat yang logout tidak menampilkan cache data household sebelumnya; pergantian konteks household tidak membocorkan cache.
- [ ] Email OTP (jika diaktifkan) berfungsi, dibatasi percobaannya, sekali pakai, dan tersedia prosedur recovery yang disepakati.

### Finance

- [ ] Catat pemasukan, pengeluaran, transfer, edit, dan hapus; saldo akun cocok dengan hitung manual.
- [ ] Transfer tidak dihitung sebagai pemasukan/pengeluaran; transaksi terencana tidak mendobel pemakaian anggaran.
- [ ] Anggaran, periode keuangan, transaksi dekat batas periode, dan preview perubahan hari mulai sesuai rentang tanggal.
- [ ] Anggaran historis tidak berubah setelah perubahan periode; budget masa depan hanya disalin dengan tindakan eksplisit.
- [ ] Pinjaman menghasilkan jadwal dan cicilan terakhir sesuai total; pembayaran parsial/lunas memperbarui saldo rencana.
- [ ] "Selisih total" menggunakan label netral dan tidak disajikan sebagai bunga yang dialokasikan.
- [ ] Lima alokasi menampilkan persen custom, target, penggunaan/penempatan dan sisa; total >100% ditolak, sisa <100% belum dialokasikan, cicilan >30% hanya peringatan.
- [ ] Rincian transportasi/bensin, dining out/skincare, utang dan dana darurat tersedia. Transfer tabungan ke akun sendiri tidak menjadi expense atau mengurangi total saldo gabungan; pembayaran cicilan tidak dihitung dua kali.
- [ ] Rekonsiliasi menampilkan selisih; penyesuaian hanya dibuat setelah konfirmasi.
- [ ] Scan struk (jika masuk rilis) hanya memberi saran lokal; transaksi tidak dibuat sebelum konfirmasi user.

### Organizer

- [ ] Tugas sekali/berulang, checklist, rotasi, undo, catatan privat, dan konflik edit bekerja sesuai spesifikasi.
- [ ] Belanja kolaboratif dapat diselesaikan; dengan Finance aktif transaksi dibuat paling banyak sekali.
- [ ] Dengan Finance nonaktif atau gateway menghasilkan null, tugas/daftar tetap selesai dan tidak mengklaim ada transaksi.
- [ ] Kalender menampilkan sumber yang diaktifkan; all-day mempertahankan tanggal lokal dan ekspor ICS memakai akhir eksklusif.
- [ ] Ekspor ICS tidak menyertakan nominal Finance secara default.

### Asset

- [ ] Aset, dokumen, perawatan, servis, vendor, dan issue dapat dicatat serta diedit sesuai hak akses.
- [ ] Reset meter mempertahankan nilai kumulatif/progres servis; pembacaan normal yang menurun ditolak.
- [ ] Servis memperbarui jadwal; catatan servis historis tidak memundurkan jadwal.
- [ ] Integrasi Finance aktif mengaitkan transaksi/rencana secara benar; null/nonaktif tidak membuat ID palsu atau mencegah pencatatan servis.
- [ ] Reminder/perpanjangan yang dibatalkan tidak lagi muncul sebagai item aktif.

### Notifikasi, ekspor, dan operasi

- [ ] Reminder dikirim pada waktu/zona yang benar; quiet hours, preferensi penerima, retry, dan kegagalan sesuai keputusan yang disetujui.
- [ ] Pengiriman ke satu penerima gagal tidak mengubah status penerima lain.
- [ ] Ekspor household mencakup data yang dijanjikan dan tidak membocorkan catatan privat milik member lain.
- [ ] Backup otomatis berhasil; alert kegagalan mencapai admin; restore terakhir lulus.
- [ ] Email undangan, reset password, OTP (jika digunakan), dan email operasional diuji.

## 4. Uji pengalaman pemakaian nyata

Lakukan bersama owner dan partner, tanpa instruksi lisan saat skenario dimulai:

- [ ] Dari beranda, mencatat pengeluaran umum selesai dalam ≤3 tap sesuai standar produk.
- [ ] Kedua pengguna dapat menemukan anggaran/periode aktif dan memahami label rentangnya.
- [ ] Pengingat membantu, tidak berlebihan; preferensi dan quiet hours mudah ditemukan.
- [ ] Pengguna memahami data yang terlihat pasangan dan perbedaan rencana vs transaksi aktual.
- [ ] Teks kosong/error/konfirmasi menjelaskan tindakan berikutnya tanpa menyalahkan pengguna.
- [ ] Ponsel 360px: tidak ada scroll horizontal; target sentuh dan tombol simpan dapat digunakan satu tangan.
- [ ] Light/dark mode, keyboard, zoom teks, dan tampilan desktop layak.

## 5. Kriteria rilis

Rilis hanya jika semua syarat berikut terpenuhi:

- [ ] Semua blocker keamanan, isolasi data, backup/restore, dan kebijakan penghapusan sudah ditutup.
- [ ] Tidak ada kegagalan UAT severity tinggi; temuan lain dicatat dengan pemilik dan rencana tindak lanjut.
- [ ] Kedua pengguna menyetujui alur harian yang akan dipakai dan data awal yang akan dimasukkan.
- [ ] Restore telah diuji, secret tersimpan aman, HTTPS aktif, serta alert backup diterima.
- [ ] Prosedur deploy/rollback dan kontak operator tersedia.
- [ ] Instalasi Linux bare-metal, Nginx/PHP-FPM, zrok client, worker, dan scheduler telah diuji termasuk restart setelah reboot.
- [ ] Rilis, waktu, penguji, hasil, dan known issues dicatat di bawah.

### Catatan rilis UAT

| Hasil | Detail / tautan issue | Keputusan |
|---|---|---|
| Belum dijalankan | Scaffold standar dapat boot/build lokal; belum ada build fitur Family Hub atau kandidat UAT | Belum siap go-live |

## 6. Uji setelah go-live

- [ ] Login owner/partner dan operasi baca/tulis penting berhasil.
- [ ] Operasi baca/tulis pascarilis yang aman sesuai prosedur (tanpa mencemari data nyata); hasil tercatat tepat sekali dan saldo konsisten.
- [ ] Pengingat/email uji diterima; tidak ada penerima yang salah.
- [ ] Backup pertama setelah go-live berhasil dan salinan off-site terverifikasi.
- [ ] Catat status aplikasi, sisa masalah, serta tanggal review operasional berikutnya.
