# Family Hub - Domain Glossary

Dokumen ini adalah *Single Source of Truth* untuk istilah kanonikal dan model domain dalam sistem **Family Hub**. Semua kontributor kode, pengembang, dan agen AI wajib menggunakan istilah ini secara konsisten tanpa mencampuradukkan konsep.

---

## 1. Domain Inti (Core & Tenancy)

### `Household` (Rumah Tangga)
Unit isolasi data multi-tenant utama. Setiap data keuangan, agenda, catatan, dan aset wajib terikat ke tepat satu `Household` melalui `household_id` (menggunakan ULID). Tidak ada data rumah tangga yang boleh bocor lintas household (*strict tenant isolation*).

### `HouseholdMember` (Anggota Rumah Tangga)
Relasi antara `User` dan `Household`. Sistem Family Hub mengadopsi model keluarga dual-partner dengan maksimal 2 anggota per rumah tangga:
- **`Owner` (Kepala / Pembuat Rumah Tangga):** Pemilik utama akun household. Memiliki hak penuh atas konfigurasi rumah tangga, transfer kepemilikan, dan mengundang partner.
- **`Partner` (Pasangan):** Anggota kedua rumah tangga yang bergabung melalui token undangan terverifikasi. Memiliki akses kolaboratif penuh ke seluruh modul operasional.

### `PeriodResolver`
Layanan deterministik untuk menghitung batas tanggal awal dan akhir periode keuangan bulanan rumah tangga berdasarkan `start_day` (hari gajian/cut-off) yang dikonfigurasi pada household. Periode lampau dihitung secara matematis tanpa memerlukan backfill tabel period statis.

### `RecoveryCode` (Kode Pemulihan 2FA)
Kumpulan 8 kode alfanumerik (8 karakter) sekali pakai (*single-use*) yang digenerasikan saat pengguna mengaktifkan Autentikasi Dua Faktor (2FA). Disimpan dalam bentuk hash satu arah di database. Digunakan untuk masuk saat akses email OTP terganggu.

---

## 2. Domain Keuangan (Finance)

### `Account` (Akun Keuangan)
Rekening bank, dompet digital (e-wallet), atau kas fisik yang dimiliki oleh rumah tangga (misal: BCA, Mandiri, Dompet Kas, GoPay). Saldo akun dihitung dari mutasi transaksi atau penyesuaian rekonsiliasi.

### `Transaction` (Transaksi)
Catatan mutasi finansial berupa `income` (pemasukan), `expense` (pengeluaran), atau `transfer` (pindah dana antar akun). Setiap transaksi terikat pada satu akun, kategori, dan tanggal transaksi.

### `TransactionSplit` (Pecahan Transaksi)
Pembagian alokasi kategori jamak pada satu transaksi pengeluaran fisik (misal: belanja supermarket senilai Rp 500.000 dipecah menjadi Rp 350.000 bahan makanan dan Rp 150.000 perlengkapan rumah). Jumlah total pecahan wajib sama persis dengan nilai transaksi induk.

### `PlannedItem` (Kewajiban Rutin Terencana)
Item tagihan berkala atau pos kewajiban yang telah diproyeksikan pada periode berjalan (misal: tagihan listrik, internet, uang sekolah). Transaksi pengeluaran yang terhubung ke `PlannedItem` tidak memotong kuota pagu anggaran belanja variabel kategori (`BR-FIN-030`).

---

## 3. Domain Integrasi & Chatbot (Telegram & OCR)

### `TelegramLink`
Relasi otentikasi antara akun Telegram pengguna (`telegram_chat_id`) dengan akun `User` & `Household` di Family Hub. Verifikasi tautan dilakukan menggunakan kode 6-digit dengan masa kedaluwarsa 15 menit.

### `PendingDraft` (Draft Transaksi Tertunda)
State transaksi sementara hasil parsing bahasa alami atau OCR struk yang dikirim melalui bot Telegram. Draft belum disimpan ke tabel `Transaction` resmi sampai pengguna memberikan konfirmasi eksplisit melalui inline button `[ ✅ Simpan ]`.

### `ReceiptOCR`
Layanan ekstraksi teks lokal menggunakan engine Tesseract pada server ODROID. Mengidentifikasi nama merchant/toko, tanggal transaksi, dan total nominal belanja secara deterministik tanpa mengirim data ke AI cloud pihak ketiga.

---

## 4. Domain Pengorganisasi & Aset (Organizer & Asset)

### `Task` (Tugas Keluarga)
Pekerjaan rumah tangga dengan status `pending` / `completed`, tenggat waktu, checklist item, dan opsi perulangan (*recurrence*). Dapat menghasilkan transaksi pengeluaran otomatis saat tugas diselesaikan.

### `Asset` (Aset & Inventaris)
Barang berharga keluarga (kendaraan, peralatan rumah tangga, properti) yang memiliki riwayat pembelian, lokasi penyimpanan, dan jadwal pemeliharaan.

### `MaintenancePlan` (Rencana Perawatan)
Jadwal perawatan berkala untuk aset berdasarkan interval waktu (hari/bulan) atau interval meter/odometer. Dapat ditunda sementara (*snooze*) tanpa membatalkan siklus perawatannya.
