# ADR-012: Autentikasi Dua Faktor (2FA) Berbasis Email OTP & Recovery Codes

## Status
Diterima (Accepted) — disepakati pada sesi Grilling Round 1 (menutup `P-19` / `Q-M12`).

## Konteks
Sebagai aplikasi keuangan dan manajemen rumah tangga privat, keamanan akun pengguna adalah prioritas. Beberapa anggota keluarga mungkin enggan menginstal aplikasi pihak ketiga seperti Google Authenticator atau Authy (TOTP authenticator app). Namun, mengandalkan kata sandi saja menyisakan risiko jika terjadi pembobolan kredensial.

Spesifikasi awal menyisakan pertanyaan terbuka `Q-M12` (`P-19`) terkait metode 2FA dan parameter keamanannya untuk rilis pasca-MVP (`IMP-02`).

## Keputusan
1. **Mekanisme Otentikasi:** Menggunakan Email OTP (One-Time Password) numerik 6-digit yang dikirimkan ke email terdaftar pengguna saat login berhasil melewati verifikasi kata sandi.
2. **Parameter Keamanan:**
   - **Masa Berlaku OTP:** 10 menit sejak dikirim.
   - **Batas Percobaan:** Maksimal 5 kali kegagalan input sebelum kode OTP dinyatakan hangus (*invalidated*).
   - **Rate Limiting Pengiriman:** Maksimal 1 permintaan kode OTP baru per 60 detik.
3. **Mekanisme Darurat (Recovery Codes):**
   - Saat pengguna mengaktifkan fitur 2FA, sistem menggenerasikan **8 Recovery Codes** sekali pakai berformat 8 karakter alfanumerik.
   - Kode hanya ditampilkan sekali kepada pengguna untuk dicatat atau diunduh.
   - Disimpan di database dalam bentuk hash satu arah (Bcrypt/Argon2id).
   - Setiap penggunaan kode pemulihan langsung membakar (*burn/consume*) kode tersebut sehingga tidak dapat digunakan kembali.
4. **Tingkat Adopsi:**
   - Bersifat opsional per pengguna (*opt-in*).

## Konsekuensi
- **Positif:** Tidak membebani anggota keluarga untuk memasang aplikasi authenticator tambahan; tetap memberikan proteksi lapis kedua terhadap pembajakan akun; mitigasi kegagalan email disediakan lewat recovery codes.
- **Negatif:** Bergantung pada keandalan layanan email pengiriman SMTP (telah diuji aman pada `LOG-066`).
