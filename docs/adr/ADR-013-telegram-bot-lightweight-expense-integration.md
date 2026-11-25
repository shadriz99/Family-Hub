# ADR-013: Integrasi Bot Telegram Pengeluaran & OCR Struk Deterministik Ringan ODROID

## Status
Diterima (Accepted) — disepakati pada sesi Grilling Round 1 (mengukuhkan `LOG-074`).

## Konteks
Pencatatan pengeluaran harian (misal saat berbelanja di pasar, minimarket, atau warung) membutuhkan kecepatan (*frictionless capture*). Membuka web browser di ponsel seringkali dirasa lambat saat terburu-buru. Namun, menjalankan model AI LLM lokal pada server keluarga (ODROID-C2 dengan 2GB RAM) sangat tidak mungkin karena akan memicu out-of-memory (OOM) dan freeze sistem. Mengirim data struk ke LLM cloud juga melanggar prinsip privasi keluarga tanpa biaya langganan API berbayar.

## Keputusan
1. **Antarmuka Chatbot Telegram:** Menggunakan bot Telegram resmi berbasis Bot API standar tanpa pustaka SDK berat, memanfaatkan `Illuminate\Support\Facades\Http`.
2. **Parser Deterministik:** Mengembangkan `NaturalExpenseParser` di PHP murni yang mengekstrak nominal berbasis pola angka Indonesia (`25rb`, `50k`, `1.5jt`, `50.000`) dan mencocokkan kata kunci akun/kategori lokal secara deterministik (< 5 MB RAM, < 10 ms).
3. **Pipa OCR Struk Lokal:** Menggunakan binary Tesseract OCR lokal yang terpasang di sistem Linux untuk membaca nama toko, tanggal, dan total nominal dari foto struk yang dikirimkan.
4. **Alur Konfirmasi Draft Wajib (*Safety Guard*):**
   - Transaksi yang diekstrak dari teks maupun foto tidak langsung ditulis ke tabel `Transaction`.
   - Data disimpan sementara dalam kolom `pending_draft` pada `telegram_links`.
   - Bot mengirimkan pesan pratinjau beserta tombol inline keyboard `[ ✅ Simpan ]` dan `[ ❌ Batal ]`.
   - Hanya ketika pengguna menekan tombol simpan, transaksi resmi dibuat dengan metadata `source: telegram`.
5. **Mode Jaringan Fleksibel:** Mendukung mode Webhook dengan secret token guard untuk deployment publik, serta Artisan Poller (`php artisan telegram:poll`) untuk server rumahan di balik NAT/CGNAT tanpa domain publik.

## Konsekuensi
- **Positif:** Pencatatan super cepat di bawah 5 detik; konsumsi RAM minimal dan CPU aman dari freeze; privasi 100% terjaga di server lokal tanpa biaya API eksternal.
- **Negatif:** Parsing berbasis regex/aturan deterministik memerlukan format angka yang wajar, namun mitigasi kesalahan ditangani secara elegan oleh tahap konfirmasi draft.
