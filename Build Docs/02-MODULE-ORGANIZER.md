# 🗓️ Modul Organizer — Tugas, Catatan, Belanja & Kalender
**Dokumen:** 02-MODULE-ORGANIZER.md
**Versi:** 0.2
**Fase:** Phase 2
**Prefix tabel:** `org_` · **Prefix task:** `ORG-` · **Namespace:** `App\Modules\Organizer`
**Prasyarat:** Phase 0 selesai. Phase 1 (Finance) SEBAIKNYA selesai (untuk integrasi belanja → transaksi); jika belum, gunakan `NullFinanceGateway`.

---

## 1. Tujuan
Satu tempat bagi suami & istri untuk:
1. Membagi dan mengingat **tugas rumah tangga** (sekali & berulang).
2. Menyimpan **catatan bersama** (info penting, checklist, ide).
3. Mengelola **daftar belanja** bersama secara hampir real-time, lalu mencatatnya sebagai pengeluaran.
4. Melihat **kalender terpadu** (acara keluarga + tagihan/cicilan + tugas + perawatan aset) dan mengunduhnya sebagai **.ics**.

## 2. Scope

| Masuk MVP | Ditunda (v1.1+) | Tidak dibangun |
|---|---|---|
| Tugas sekali & berulang, checklist, penanggung jawab, rotasi suami/istri | Lampiran foto bukti tugas | Sinkron 2 arah Google Calendar |
| Catatan (teks & checklist), pin, label, catatan privat | URL langganan .ics (subscribe) | Kolaborasi real-time per karakter |
| Daftar belanja kolaboratif + "Selesai belanja" → transaksi | Saran item dari riwayat (ML) | |
| Kalender: tampilan Bulan & Agenda, acara berulang, filter sumber | Tampilan Minggu | |
| Ekspor **.ics** (download) | Libur nasional & cuti bersama | |
| Pengingat untuk acara & tugas | Menu makan mingguan | |

## 3. Glosarium

| UI | Kode | Definisi |
|---|---|---|
| Tugas | `Task` | Pekerjaan yang harus dilakukan, punya status selesai |
| Tugas berulang | `Task` + `rrule` | Satu "seri"; instance berikutnya dibuat saat instance sekarang selesai/terlewat |
| Catatan | `Note` | Teks atau checklist bebas |
| Daftar Belanja | `ShoppingList` | Kumpulan item belanja untuk satu kali belanja |
| Acara | `Event` | Kegiatan pada waktu tertentu (ulang tahun, arisan, kontrol dokter) |
| Kalender | – | Tampilan gabungan semua `CalendarSource` (Masterplan §7.3) |

---

## 4. User Stories & Acceptance Criteria

### 4.1 Tugas

**US-ORG-01 Buat tugas**
- **AC1** Field: judul (wajib), deskripsi, penanggung jawab (`Saya` / `Pasangan` / `Siapa saja`), tanggal jatuh tempo (opsional), jam (opsional), prioritas (`Rendah`/`Normal`/`Tinggi`), checklist sub-item, pengulangan (preset Masterplan §7.4), pengingat (`Tidak ada` / `Saat jatuh tempo` / `1 jam sebelum` / `1 hari sebelum` 08:00), "Catat pengeluaran saat selesai" (toggle, + estimasi nominal & kategori).
- **AC2** Quick add dari FAB: hanya judul + chip cepat (`Hari ini`, `Besok`, `Akhir pekan`, `Saya`, `Pasangan`).

**US-ORG-02 Daftar tugas**
- **AC1** Tab: `Hari ini` (termasuk terlambat), `Mendatang`, `Tanpa tanggal`, `Selesai` (30 hari terakhir).
- **AC2** Filter: Saya / Pasangan / Semua. Grup: Terlambat → Hari ini → Besok → Minggu ini → Nanti.
- **AC3** Centang selesai langsung dari daftar (checkbox besar 44px), animasi coret + toast [Batalkan].

**US-ORG-03 Tugas berulang**
- **AC1** Saat instance diselesaikan, sistem membuat instance berikutnya dengan due_date = kejadian RRULE berikutnya **setelah due_date instance sekarang** (bukan setelah tanggal penyelesaian), dengan judul/checklist (di-reset)/penanggung jawab/pengingat yang sama.
- **AC2** Jika instance terlewat (due_date < hari ini dan belum selesai) **dan** sudah ada kejadian berikutnya yang lewat, job harian **tidak** menumpuk instance; instance lama tetap satu dan ditandai terlambat (mencegah 30 tugas "cuci piring" menumpuk).
- **AC3** Opsi **Rotasi**: jika aktif, penanggung jawab instance berikutnya bergantian antara 2 member.
- **AC4** Edit tugas berulang: pilihan "Hanya tugas ini" / "Tugas ini & seterusnya". Hapus: pilihan yang sama ("seterusnya" = hentikan seri).

**US-ORG-04 Selesai + pengeluaran**
- **AC1** Jika "Catat pengeluaran saat selesai" aktif dan integrasi Finance tersedia, saat dicentang muncul bottom sheet transaksi dengan nominal estimasi, kategori, akun default → simpan via `FinanceGateway::recordExpense` (source `org_task`). User boleh "Lewati". Jika gateway mengembalikan `null` (integrasi dinonaktifkan), tugas tetap selesai dan `expense_transaction_id` tetap null; UI tidak mengklaim transaksi dibuat.

### 4.2 Catatan

**US-ORG-05 Catatan**
- **AC1** Jenis: `Teks` (markdown ringan: bold, italic, list, link, heading — dirender dengan `league/commonmark` *(bawaan Laravel `Str::markdown`)*, HTML di-escape) atau `Checklist`.
- **AC2** Field: judul, isi/item, warna kartu (6 pilihan), label (maks 5, teks bebas), pin, **privat** (hanya pembuat yang melihat — pengecualian transparansi, misal rencana kejutan ulang tahun).
- **AC3** Tampilan grid 2 kolom (mobile) / masonry 3–4 kolom (desktop); pinned di atas; pencarian judul+isi; filter label.
- **AC4** Konflik edit: jika `updated_at` berubah sejak dibuka, saat simpan tampilkan dialog: "Catatan ini diubah oleh {nama} pada {waktu}. [Lihat versinya] [Timpa dengan versi saya] [Simpan sebagai salinan]".
- **AC5** Checklist: tambah item dengan Enter, centang, urutkan (drag), "Hapus item yang dicentang".
- **AC6** Arsip & tempat sampah (soft delete, dipulihkan dalam 30 hari).

### 4.3 Daftar Belanja

**US-ORG-06 Daftar belanja kolaboratif**
- **AC1** Bisa punya beberapa daftar aktif (mis. "Bulanan – Superindo", "Pasar Pagi"). Field daftar: nama, toko (opsional), status `active`/`completed`/`archived`.
- **AC2** Item: nama (wajib), jumlah (desimal 2), satuan (pcs, kg, gr, liter, pak, dus, ikat), estimasi harga satuan (opsional), catatan, kelompok (Sayur & Buah, Daging & Ikan, Sembako, Bumbu, Minuman, Kebersihan, Bayi, Lainnya — auto-tebak dari kamus sederhana, bisa diubah).
- **AC3** Tambah item cepat: ketik "telur 2 kg" → parser mengenali jumlah & satuan.
- **AC4** Autocomplete nama item dari 200 nama item yang pernah dibeli household (urut frekuensi).
- **AC5** Halaman daftar aktif melakukan `wire:poll.10s` agar centangan pasangan terlihat (hanya saat tab terlihat — `wire:poll.visible`).
- **AC6** Item dicentang pindah ke bawah, menampilkan avatar pencentang.
- **AC7** Total estimasi = Σ (qty × harga) item yang punya harga.

**US-ORG-07 Selesai belanja → transaksi**
- **AC1** Tombol "Selesai Belanja": input total aktual (default total estimasi item dicentang), akun, kategori (default sistem `shopping`), tanggal, foto struk.
- **AC2** Jika integrasi Finance aktif, simpan memanggil `FinanceGateway::recordExpense` (source `org_shopping_list`) satu kali → daftar `completed`; simpan `transaction_id` hanya jika ID dikembalikan. Jika gateway mengembalikan `null` atau integrasi off, daftar tetap dapat diselesaikan tanpa langkah akun/kategori dan `transaction_id` tetap null; UI tidak mengklaim transaksi dibuat.
- **AC3** Item yang **belum** dicentang: opsi "Pindahkan ke daftar baru" / "Hapus".
- **AC4** "Belanja lagi" pada daftar selesai → menyalin semua item ke daftar baru (belum dicentang).

### 4.4 Kalender & Acara

**US-ORG-08 Acara**
- **AC1** Field: judul, tanggal & jam mulai/selesai atau tanggal/rentang "Sepanjang hari", lokasi (teks), deskripsi, untuk (`Keduanya`/`Saya`/`Pasangan`), warna (default warna member / hijau untuk keduanya), pengulangan (preset; termasuk "Setiap tahun" untuk ulang tahun/anniversary), pengingat (maks 2: pilihan 10 menit, 1 jam, 1 hari, 3 hari, 1 minggu sebelum). Rentang all-day yang ditampilkan inklusif disimpan sebagai `[start_date,end_date)`; acara satu hari memakai `end_date` hari berikutnya.
- **AC2** Jenis cepat: `Acara`, `Ulang Tahun` (otomatis berulang tahunan, sepanjang hari, judul "🎂 Ulang tahun {nama}", tahun lahir opsional → tampilkan usia), `Anniversary`.
- **AC3** Edit/hapus acara berulang: "Hanya acara ini" (exception) / "Acara ini & seterusnya" / "Semua".

**US-ORG-09 Tampilan kalender**
- **AC1** **Bulan**: grid 7 kolom (Senin awal minggu). Mobile: setiap tanggal menampilkan maks 3 titik berwarna sesuai sumber; tap tanggal → panel daftar item tanggal tsb di bawah grid. Desktop: judul item tampil (maks 3 + "+N lainnya").
- **AC2** **Agenda**: daftar 60 hari ke depan dikelompokkan per tanggal, infinite scroll; item terlambat (tugas/tagihan) di bagian atas "Terlambat".
- **AC3** Filter sumber (chip, disimpan per user di localStorage): Acara · Tugas · Tagihan & Cicilan · Perawatan Aset · Dokumen/Garansi.
- **AC4** Filter orang: Semua / Saya / Pasangan.
- **AC5** Tap item → buka URL sumber (detail acara/tugas/rencana keuangan/aset).
- **AC6** Tombol "Hari ini", swipe kiri/kanan untuk ganti bulan (mobile).
- **AC7** Kalender dibangun **tanpa library kalender eksternal** (Blade + Alpine + Tailwind); data dari `CalendarAggregator`.

**US-ORG-10 Ekspor .ics**
- **AC1** Tombol "Unduh .ics" → dialog: rentang (`Bulan ini`, `3 bulan ke depan`, `12 bulan ke depan`, `Kustom` maks 400 hari), sumber (checkbox, default semua), orang, dan toggle `Sembunyikan nominal` (default aktif).
- **AC2** File `familyhub-{household-slug}-{from}-{to}.ics` dibuat oleh `spatie/icalendar-generator` dari `CalendarAggregator` (bukan hanya acara Organizer). Jika `Sembunyikan nominal` aktif, hapus nominal dari judul item sumber Finance pada file ekspor. Acara berulang Organizer diekspor sebagai **kejadian-kejadian terpisah** dalam rentang (bukan RRULE) agar konsisten dengan sumber lain dan aman diimpor ulang.
- **AC3** Setiap VEVENT punya `UID` stabil = `CalendarItem::uid` → impor ulang ke Google Calendar memperbarui, bukan menduplikasi (selama Google menghormati UID).
- **AC4** Zona waktu: `Asia/Jakarta` (atau timezone household) dengan VTIMEZONE; item allDay sebagai DATE.
- **AC5** Panduan singkat di dialog: cara impor ke Google Calendar & Outlook.

### 4.5 Pengingat
**US-ORG-11** Halaman "Pengingat" (`/organizer/reminders`) menampilkan pengingat pending 14 hari ke depan untuk user (dari semua modul) dengan aksi "Tunda 1 jam / besok 08:00" dan "Batalkan".

---

## 5. Data Model

Konvensi: ULID, `household_id`, `created_by`, timestamps, softDeletes (Masterplan §6).

### 5.1 `org_tasks`
| Kolom | Tipe | Aturan |
|---|---|---|
| series_id | ULID nullable index | sama untuk semua instance dari satu seri berulang |
| title | string(150) | |
| description | text nullable | |
| assignee_user_id | FK users nullable | null = siapa saja |
| due_date | date nullable | index |
| due_time | time nullable | hanya jika due_date ada |
| priority | string `TaskPriority` | `low`,`normal`,`high` |
| rrule | string nullable | |
| rotate_assignee | bool default false | |
| reminder_offset | string nullable `TaskReminderOffset` | `at_due`,`1h_before`,`1d_before_0800` |
| status | string `TaskStatus` | `open`,`done`,`cancelled` |
| completed_at | datetime nullable | |
| completed_by | FK users nullable | |
| expense_on_complete | bool default false | |
| expense_estimate | bigint nullable | |
| expense_category_id | ULID nullable | (kategori Finance; tanpa FK constraint lintas modul) |
| expense_transaction_id | ULID nullable | |
| sort_order | int default 0 | |

### 5.2 `org_task_items` (checklist)
`id`, `task_id` FK cascade, `text` string(200), `is_done` bool, `sort_order` int, timestamps.

### 5.3 `org_notes`
| Kolom | Tipe | Aturan |
|---|---|---|
| type | string `NoteType` | `text`,`checklist` |
| title | string(150) nullable | |
| body | text nullable | untuk type text, maks 20.000 karakter |
| color | string(20) default `default` | `default`,`yellow`,`green`,`blue`,`pink`,`purple` |
| labels | text (cast array) | maks 5 label × 30 karakter |
| is_pinned | bool | |
| is_private | bool default false | hanya `created_by` yang bisa melihat (global scope tambahan) |
| archived_at | datetime nullable | |
| last_edited_by | FK users nullable | |

### 5.4 `org_note_items`
`id`, `note_id` FK cascade, `text` string(300), `is_done`, `sort_order`, timestamps.

### 5.5 `org_shopping_lists`
| Kolom | Tipe | Aturan |
|---|---|---|
| name | string(80) | |
| store | string(80) nullable | |
| status | string `ShoppingListStatus` | `active`,`completed`,`archived` |
| completed_at | datetime nullable | |
| completed_by | FK users nullable | |
| actual_total | bigint nullable | |
| transaction_id | ULID nullable | |

### 5.6 `org_shopping_items`
| Kolom | Tipe | Aturan |
|---|---|---|
| shopping_list_id | FK cascade | |
| name | string(100) | |
| quantity | decimal(10,2) default 1 | (pengecualian DB-5 karena bukan uang) |
| unit | string(20) nullable | |
| estimated_price | bigint nullable | harga satuan |
| group | string `ShoppingGroup` | |
| note | string(150) nullable | |
| is_checked | bool | |
| checked_by | FK users nullable | |
| checked_at | datetime nullable | |
| sort_order | int | |

### 5.7 `org_events`
| Kolom | Tipe | Aturan |
|---|---|---|
| kind | string `EventKind` | `event`,`birthday`,`anniversary` |
| title | string(150) | |
| description | text nullable | |
| location | string(200) nullable | |
| all_day | bool | |
| start_at | datetime (UTC) nullable | waktu mulai untuk acara berjam; tidak dipakai untuk menentukan tanggal all-day |
| end_at | datetime (UTC) nullable | waktu selesai untuk acara berjam; ≥ start_at |
| start_date | date | tanggal lokal, untuk all_day & recurrence |
| end_date | date nullable | hanya untuk all-day; tanggal lokal akhir eksklusif, wajib > `start_date`; default `start_date + 1 day` |
| rrule | string nullable | |
| for_user_id | FK users nullable | null = keduanya |
| color | string(7) nullable | |
| reminder_offsets | text (cast array) | menit sebelum, maks 2, mis. `[1440, 60]` |
| birth_year | smallint nullable | untuk birthday |

### 5.8 `org_event_exceptions`
`id`, `event_id` FK cascade, `original_date` date, `is_cancelled` bool, `override` text (cast array: title/start_at/end_at/start_date/end_date/location; all-day override memakai tanggal lokal) nullable. Unique (`event_id`,`original_date`).

### 5.9 Enum
`TaskPriority`, `TaskStatus`, `TaskReminderOffset`, `NoteType`, `ShoppingListStatus`, `ShoppingGroup`, `EventKind` — masing-masing dengan `label()`, dan `color()/icon()` bila relevan.

---

## 6. Aturan Bisnis

| ID | Aturan |
|---|---|
| BR-ORG-001 | Tugas berulang: hanya **satu** instance `open` per `series_id` pada satu waktu. |
| BR-ORG-002 | Next due = `Recurrence::next(rrule, dtstart = due_date seri pertama, after = due_date instance saat ini)`. Jika null (UNTIL/COUNT habis) → seri berakhir. |
| BR-ORG-003 | Membatalkan centang (undo) instance berulang dalam 5 detik → hapus instance berikutnya yang baru dibuat bila belum disentuh. Setelah itu, uncheck hanya membuka kembali instance tsb (instance berikutnya tetap). |
| BR-ORG-004 | Rotasi: assignee berikutnya = member lain di household; jika household hanya 1 member, rotasi diabaikan. |
| BR-ORG-005 | Tugas tanpa due_date tidak tampil di kalender. |
| BR-ORG-010 | Catatan privat tidak muncul di pencarian, ekspor household milik member lain, maupun notifikasi. Ekspor household oleh owner **menyertakan** catatan privat milik owner saja. |
| BR-ORG-011 | Body markdown dirender dengan `Str::markdown($body, ['html_input' => 'escape', 'allow_unsafe_links' => false])`. |
| BR-ORG-020 | Daftar belanja `completed` bersifat read-only, kecuali dibuka kembali ("Buka lagi") — jika sudah ada transaksi, membuka lagi **tidak** menghapus transaksi (user diberi tahu). |
| BR-ORG-021 | Menghapus daftar belanja yang punya `transaction_id` tidak menghapus transaksinya. |
| BR-ORG-022 | `transaction_id` tugas/daftar belanja hanya diisi dengan ID non-null dari FinanceGateway; gateway `null` atau integrasi off tidak menggagalkan penyelesaian tugas/daftar belanja dan tidak boleh ditampilkan sebagai transaksi tercatat. |
| BR-ORG-030 | Ekspansi acara berulang dalam rentang menggunakan `Recurrence::occurrencesBetween` lalu menerapkan `org_event_exceptions`. |
| BR-ORG-031 | Ulang tahun 29 Feb → di tahun non-kabisat tampil 28 Feb. |
| BR-ORG-032 | Acara multi-hari tampil di setiap tanggal yang dilaluinya pada tampilan Bulan. |
| BR-ORG-033 | Notifikasi pengingat acara dikirim ke `for_user_id` atau kedua member jika null. |
| BR-ORG-040 | Ekspor .ics dibatasi 400 hari dan 5.000 item; jika lebih, minta user mempersempit. |

---

## 7. Halaman & Komponen

| Route | Komponen | Keterangan |
|---|---|---|
| `GET /calendar` | `Organizer\Calendar\Index` | Tab Bulan/Agenda, filter, tombol Unduh .ics, FAB "Acara" |
| `GET /calendar/export` | Controller `CalendarExportController` | query: from, to, sources[], member |
| `GET /organizer/events/create`, `/{id}`, `/{id}/edit` | `Organizer\Events\Form`, `Show` | |
| `GET /organizer/tasks` | `Organizer\Tasks\Index` | US-ORG-02 |
| `GET /organizer/tasks/create`, `/{id}` | `Organizer\Tasks\Form`, `Show` | |
| `GET /organizer/notes` | `Organizer\Notes\Index` | |
| `GET /organizer/notes/{id}` | `Organizer\Notes\Editor` | autosave debounce 1,5 detik + indikator "Tersimpan" |
| `GET /organizer/shopping` | `Organizer\Shopping\Index` | daftar aktif & riwayat |
| `GET /organizer/shopping/{id}` | `Organizer\Shopping\Show` | US-ORG-06/07, poll 10 detik |
| `GET /organizer/reminders` | `Organizer\Reminders\Index` | US-ORG-11 |

Navigasi: `Kalender` di bottom nav. `Tugas`, `Catatan`, `Belanja` di menu `Lainnya` (desktop: sidebar). Item FAB: `Tugas`, `Catatan`, `Item Belanja` (memilih daftar aktif terakhir / membuat "Daftar Belanja" baru).

---

## 8. Integrasi

### 8.1 Calendar Sources
| Source key | Kelas | Item | Warna | URL |
|---|---|---|---|---|
| `organizer.events` | `EventCalendarSource` | Acara (ekspansi rrule) | warna acara / member | `/organizer/events/{id}?date=` |
| `organizer.tasks` | `TaskCalendarSource` | Tugas `open` dengan due_date + tugas `done` (status done, dicoret) | high `#E11D48`, normal `#6366F1`, low `#94A3B8` | `/organizer/tasks/{id}` |

Judul tugas di kalender: `"☑️ {title}"` (+ `" · {nama assignee}"` jika ada).

### 8.2 Alert Source — `OrganizerAlertSource`
| Level | Kondisi |
|---|---|
| warning | Tugas terlambat milik user/siapa saja (digabung: "4 tugas terlambat") |
| info | Ulang tahun/anniversary dalam 7 hari ("🎂 Ulang tahun Ibu 3 hari lagi") |

### 8.3 Dashboard Widgets
- `ShoppingWidget`: daftar aktif teratas — nama, x/y item dicentang, tombol buka.
- Tugas hari ini termasuk di blok "Hari Ini" melalui CalendarAggregator.

### 8.4 Reminder
| Sumber | Key | Waktu |
|---|---|---|
| Task | `due` | sesuai `reminder_offset` (jika due_time null & offset `at_due` → 08:00) |
| Event | `off-{menit}` | start − offset, per kejadian; dibuat untuk kejadian dalam 35 hari ke depan oleh job harian `organizer:schedule-event-reminders` |

### 8.5 Finance
- Menggunakan `FinanceGateway::recordExpense` (US-ORG-04, US-ORG-07); dropdown akun/kategori dari `accountOptions()` / `expenseCategoryOptions()`.
- Form transaksi mengikuti Finance US-FIN-01 AC7 dan US-FIN-04A: pilih rincian kategori dan alokasi (mis. belanja dapur → Kebutuhan Hidup, dining out → Tersier). Kirim alokasi lewat ExpenseData; transaksi yang sama mengisi realisasi alokasi, jangan membuat salinan transaksi.
- Jika integrasi off → tugas dan tombol "Selesai Belanja" tetap berfungsi tanpa langkah transaksi; field pilihan Finance disembunyikan dan ID transaksi tidak disimpan.

### 8.6 Household Exporter
`tasks.csv`, `notes.json` (beserta item), `shopping_lists.csv`, `shopping_items.csv`, `events.csv`, plus `calendar-12-bulan.ics`.

---

## 9. Scheduled Jobs
| Command | Jadwal | Fungsi |
|---|---|---|
| `organizer:roll-recurring-tasks` | 00:30 | Pastikan setiap seri aktif punya tepat satu instance open (BR-ORG-001); perbaiki bila hilang |
| `organizer:schedule-event-reminders` | 00:35 | Buat reminder untuk kejadian acara 35 hari ke depan |
| `organizer:purge-trash` | 03:30 | Hapus permanen catatan di tempat sampah > 30 hari |

---

## 10. Test Wajib
1. Isolasi household semua route/Livewire + catatan privat tidak terlihat pasangan (route, pencarian, ekspor).
2. Tugas berulang: selesai → instance baru tanggal benar; tidak menumpuk; undo 5 detik; rotasi; "ini & seterusnya".
3. Recurrence acara + exception + ulang tahun 29 Feb + acara multi-hari.
4. Parser item belanja: "telur 2 kg", "2 indomie", "minyak goreng 1 liter", "sabun" (default 1 pcs).
5. Selesai tugas/belanja: FinanceGateway dipanggil paling banyak sekali dengan source morph benar; hasil `null`/integrasi off tidak menghalangi penyelesaian dan tidak menyimpan ID transaksi.
6. Ekspor .ics: valid (parse ulang dengan library), UID stabil antar ekspor, allDay sebagai DATE, batas 400 hari.
7. Konflik edit catatan (updated_at stale) memunculkan dialog.
8. CalendarAggregator dengan source Finance + Organizer: urutan & filter.
9. Acara all-day satu hari dan multi-hari mempertahankan tanggal lokal; `DTEND` iCalendar eksklusif sesuai `end_date`, termasuk di zona waktu non-UTC.

---

## 11. Task Breakdown

| ID | Judul | Isi | Dep |
|---|---|---|---|
| ORG-01 | Skema & model | Migrasi §5, model, enum, factory, provider, routes, feature flag | CORE-* |
| ORG-02 | Tugas – CRUD & daftar | US-ORG-01, 02, checklist | 01 |
| ORG-03 | Tugas berulang & rotasi | US-ORG-03 + job roll | 02, CORE-08 |
| ORG-04 | Tugas → pengeluaran | US-ORG-04 | 02, FIN-13 |
| ORG-05 | Catatan | US-ORG-05 (teks, checklist, privat, konflik, arsip, sampah) | 01 |
| ORG-06 | Daftar belanja | US-ORG-06 (parser, autocomplete, poll) | 01 |
| ORG-07 | Selesai belanja | US-ORG-07 | 06, FIN-13 |
| ORG-08 | Acara | US-ORG-08 + exceptions | 01, CORE-08 |
| ORG-09 | Calendar sources | §8.1 untuk acara & tugas | 03, 08, CORE-11 |
| ORG-10 | UI Kalender | US-ORG-09 (Bulan, Agenda, filter) | 09 |
| ORG-11 | Ekspor .ics | US-ORG-10 | 10 |
| ORG-12 | Pengingat & alert | §8.2, §8.4, US-ORG-11, job event reminders | 09, CORE-09 |
| ORG-13 | Widget, exporter, polish | Dashboard widget, exporter, empty states, dark mode, 360px, offline cache `/calendar` `/organizer/notes` `/organizer/shopping` | semua |

---

## 12. Open Questions — Organizer

| ID | Pertanyaan | Default Assumption |
|---|---|---|
| Q-O1 | Perlu tampilan kalender Minggu di MVP? | Tidak (Bulan + Agenda) |
| Q-O2 | Libur nasional & cuti bersama Indonesia ditampilkan? Sumber data dikelola manual per tahun? | v1.1, file seed JSON per tahun yang diperbarui manual |
| Q-O3 | Daftar belanja perlu harga per toko/perbandingan harga? | Tidak |
| Q-O4 | Catatan privat — benar dibutuhkan (mis. kejutan), atau semua harus transparan? | Ada, default **tidak** privat |
| Q-O5 | Apakah tugas perlu "poin"/gamifikasi antara suami-istri? | Tidak |
| Q-O6 | Ekspor .ics menyertakan nominal uang pada judul tagihan? (file bisa tersimpan di Google) | Toggle tersedia; nominal **disembunyikan secara default** dan hanya disertakan jika user memilihnya |
