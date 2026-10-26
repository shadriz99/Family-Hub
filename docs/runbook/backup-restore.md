# Runbook Backup & Restore — Family Hub

**Status:** Draft; prosedur final harus diuji pada stack dan lokasi backup produksi.  
**Rujukan:** `Build Docs/00-MASTERPLAN.md` §12–14.

## 1. Cakupan dan aturan keselamatan

Backup harus memulihkan satu aplikasi yang konsisten: database, file privat/media, dan metadata/config yang diperlukan. Secret produksi disimpan terpisah dari arsip. Jangan menganggap backup berhasil hanya karena file arsip ada—uji integritas dan restore.

- Jadwal yang direncanakan: harian pukul 02:00.
- Tujuan: salinan lokal pada media/disk terpisah dan salinan off-site.
- Retensi yang direncanakan: 7 harian, 4 mingguan, 6 bulanan.
- Arsip dienkripsi dengan `BACKUP_ARCHIVE_PASSWORD`; password tidak boleh ikut tersimpan di lokasi yang sama dengan arsip.
- Rincian retensi final, enkripsi off-site, dan perlakuan penghapusan data di backup menunggu keputusan Q-M11. Jangan go-live dengan data nyata sebelum diputuskan.

## 2. Isi backup

Sertakan dan verifikasi komponen berikut:

- Database aktif (SQLite beserta kondisi WAL/checkpoint yang konsisten, atau dump MariaDB yang konsisten).
- `storage/app/private` dan media/dokumen yang diperlukan untuk membuka data.
- Metadata/version rilis dan informasi pemulihan non-secret.

Jangan memasukkan `.env`, `APP_KEY`, kredensial, token, secret tunnel, atau password arsip ke backup umum. Simpan secret yang dibutuhkan untuk decrypt/memulihkan secara terpisah di password manager yang aksesnya diuji.

## 3. Pemeriksaan backup rutin

Setiap hari atau saat menerima alert kegagalan:

1. Periksa status job, waktu selesai, ukuran arsip, dan log tanpa mencetak data sensitif.
2. Pastikan backup memuat database dan file privat; pastikan enkripsi aktif.
3. Pastikan salinan off-site selesai dan dapat diakses dengan kredensial yang benar.
4. Catat hasil, waktu, versi aplikasi, dan lokasi arsip (tanpa secret).
5. Jika gagal, jangan menghapus backup sebelumnya. Perbaiki penyebab, jalankan ulang backup, dan verifikasi hasil baru.

## 4. Uji restore berkala

Lakukan sebelum go-live dan minimal setiap 3 bulan. Restore ke host/volume terisolasi, bukan menimpa produksi.

1. Pilih arsip yang representatif, termasuk salinan off-site.
2. Verifikasi integritas arsip dan akses secret dekripsi secara aman.
3. Siapkan lingkungan restore yang terisolasi dan versi aplikasi yang kompatibel dengan backup.
4. Pulihkan DB dan file privat; jangan hubungkan transport akses publik.
5. Jalankan pemeriksaan integritas DB dan perintah maintenance yang sesuai versi aplikasi.
6. Verifikasi login lokal, jumlah household/member, sampel transaksi, lampiran/dokumen, dan relasi inti.
7. Verifikasi job/scheduler tidak mengirim reminder atau email ke pengguna sungguhan dari lingkungan uji.
8. Catat durasi, versi, hasil, selisih yang ditemukan, dan langkah yang perlu diperbaiki.
9. Hapus lingkungan uji dan salinan data sementara dengan prosedur aman setelah hasil dicatat.

### Kriteria lulus restore

- Database dapat dibuka dan aplikasi melakukan boot tanpa error skema.
- Data domain dan file lampiran yang dipilih cocok satu sama lain.
- Pengguna yang berwenang dapat membaca record; isolasi household tetap berlaku.
- Waktu pemulihan dan usia data yang hilang sesuai target yang ditetapkan sebelum go-live.

## 5. Pemulihan produksi

Gunakan saat kehilangan/korupsi data atau host:

1. Batasi akses aplikasi dan hentikan penulisan (app/worker/scheduler) untuk mencegah perubahan lebih lanjut.
2. Dokumentasikan waktu mulai insiden, gejala, versi aplikasi, dan lokasi backup kandidat. Jangan menghapus file/log/backup.
3. Pilih backup tervalidasi terbaru yang konsisten dan catat estimasi data sejak backup yang mungkin hilang.
4. Siapkan host/volume bersih; amankan konfigurasi produksi dan secret pemulihan.
5. Pulihkan database dan storage privat sebagai satu set dari waktu backup yang sama.
6. Jalankan aplikasi pada jaringan terbatas dan lakukan kriteria lulus §4.
7. Periksa konfigurasi URL, HTTPS, mail, queue, scheduler, dan hak akses sebelum membuka kembali akses.
8. Aktifkan worker/scheduler dan akses pengguna secara bertahap; pastikan tidak ada pengiriman reminder duplikat.
9. Catat keputusan, data yang hilang, waktu pemulihan, dan tindak lanjut.

Jangan memulihkan file privat dan database dari backup waktu berbeda tanpa memastikan relasi media tetap benar. Jangan mengimpor backup ke produksi aktif sebelum menghentikan penulisan.

## 6. Penghapusan data dan retensi

Q-M11 harus menjawab: berapa lama data yang sudah dipurge tetap berada di arsip backup, bagaimana permintaan penghapusan dipenuhi pada salinan yang tidak dapat diedit per household, dan siapa yang mengotorisasi penghapusan arsip. Sampai diputuskan:

- Jangan menjanjikan purge menyeluruh dari semua backup.
- Jangan hapus arsip yang mungkin diperlukan untuk pemulihan tanpa mengikuti kebijakan retensi.
- Catat tanggal purge dan backup yang masih mengandung salinan sesuai kebijakan yang kelak dipilih.

## 7. Log uji restore

| Tanggal | Rilis/backup yang diuji | Tujuan (terisolasi) | Durasi | Hasil | Tindak lanjut |
|---|---|---|---|---|---|
| Belum diuji | – | – | – | Go-live belum boleh dinyatakan lulus | Isi setelah uji pertama |
