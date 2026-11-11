# PROMPTS: Kumpulan Prompt Coding (Claude + ChatGPT)

Cara kerja: **kontrak dulu, kode kemudian, review silang sekali di akhir backend.**
Satu AI menulis, AI lainnya me-review. Manusia memutuskan.

Isi: **[0. Prompt siap tempel](#0-mulai-di-sini-prompt-siap-tempel-tanpa-isian)** · [A. Setup](#a-setup-sekali) · [B. Kickoff](#b-prompt-kickoff-tempel-di-awal-setiap-chat-baru) · [C. Template tugas](#c-template-tugas) · [D. Isian per fase](#d-isian-per-fase) · [E. Prompt khusus](#e-prompt-khusus) (termasuk **E.7 lanjut sesi**) · [F. Versi ringkas untuk instruksi ChatGPT](#f-versi-ringkas-untuk-kolom-instruksi-chatgpt) · [G. Bundel 3 file untuk ChatGPT](#g-bundel-3-file-untuk-chatgpt)

---

## 0. Mulai di sini: prompt siap tempel (tanpa isian)

Bagian B sampai F di bawah adalah **templat** dengan kolom `< >` untuk tugas lain di masa depan. Untuk pekerjaan yang sudah direncanakan, **tidak ada yang perlu diisi**: pilih satu prompt di bawah, tempel, lampirkan file yang disebut.

| Mau apa | Chat di | Pakai | Lampiran |
|---|---|---|---|
| Tes catch-up P1/P2 (T1) | Claude | 0.1 | `AI_RULES.md`, `PROGRESS.md`, `PLAN_LANJUTAN.md`, `DECISIONS.md`, `API_CONTRACT.md`, `KODE_BACKEND.txt` |
| Expiry bayar + auto-complete (T2) | Claude | 0.2 | sama seperti 0.1 (perbarui `PROGRESS.md` dan `KODE_BACKEND.txt` dulu) |
| Mulai Flutter (T6) | ChatGPT | 0.3 | 3 file bundel (§G) |
| Akhir setiap sesi | chat yang sama | §E.4 | tidak ada |

`KODE_BACKEND.txt` = semua kode backend dalam satu file (tanpa `.env`, tanpa `vendor`). Buat dengan satu perintah dari root repo, lalu lampirkan hasilnya di `chatgpt-bundle/`:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/dump-code.ps1
```

Belum dijalankan di Windows saat ditulis; kalau error, kirim outputnya. Jalankan ulang setiap kali kode berubah, karena file lama basi.

Satu-satunya hal yang kamu putuskan sendiri: **mau mengerjakan tugas yang mana** (kolom pertama tabel). Peran AI, tugas, environment, dan aturan sudah terisi.

### 0.1 Claude: T1 tes catch-up B-13

```
Lanjut proyek Marketplace Jasa Rumahan (Laravel 10 + Flutter).
Peranmu: penulis backend. Baca dulu AI_RULES.md, bagian "Sekarang" di PROGRESS.md,
PLAN_LANJUTAN.md bagian T1, dan DECISIONS.md §5.

Tugas: T1, tes catch-up B-13 untuk P1/P2 (target 34 tes baru, total 89 hijau).
Empat file tes dari chat lama tidak ada lagi, jadi tulis ulang sesuai tabel
cakupan di T1: auth, katalog + quote, slots, buat order, lacak guest + throttle,
transisi status, matching. Pakai trait tests/Concerns/BuildsMitraFixtures.php
dan MySQL test DB jasa_rumahan_test.
Buktikan dulu dua dugaan bug lewat tes: (1) harga bertipe string lolos cast,
(2) quantity negatif belum ditolak. Kalau terbukti, perbaikannya di FormRequest,
dan laporkan sebagai bug yang ditemukan tes.

Kode: semua ada di KODE_BACKEND.txt. Jangan menebak isi file yang tidak ada di
sana; sebutkan path file yang kamu butuhkan di preflight.
Environment: Windows, Laragon, PowerShell, PHP 8.3, Laravel 10.50.4, MySQL 8.0.
Kode lengkap siap tempel. Perintah git per blok kecil, berhenti kalau ada error.
Jangan menulis kode dulu; mulai dengan preflight (G-1).
```

### 0.2 Claude: T2 P4c expiry bayar + auto-complete

Pakai setelah T1 hijau dan sudah di-push.

```
Lanjut proyek Marketplace Jasa Rumahan (Laravel 10 + Flutter).
Peranmu: penulis backend. Baca dulu AI_RULES.md, bagian "Sekarang" di PROGRESS.md,
PLAN_LANJUTAN.md bagian T2, dan DECISIONS.md §5.

Tugas: T2 (P4c), kerjakan persis sesuai checklist T2. Aturan: D-06, D-07, D-24,
B-15, B-4. D-22 sampai D-24 masih USULAN: pakai usulan itu sebagai asumsi kerja
dan tulis di preflight bahwa itu asumsi (G-2).

Kode: semua ada di KODE_BACKEND.txt (versi terbaru setelah T1). Jangan menebak
isi file yang tidak ada di sana.
Environment: Windows, Laragon, PowerShell, PHP 8.3, Laravel 10.50.4, MySQL 8.0.
Kode lengkap siap tempel. Perintah git per blok kecil, berhenti kalau ada error.
Jangan menulis kode dulu; mulai dengan preflight (G-1).
```

### 0.3 ChatGPT: T6 mulai Flutter

Taruh §F di kolom instruksi Project, unggah 3 bundel (§G), lalu tempel ini.

```
Kamu asisten coding proyek tugas akhir "Marketplace Jasa Rumahan" (Flutter + Laravel).
Peranmu: penulis packages/core dan apps/* (customer_app, mitra_app). Backend sudah
selesai dan dites sampai P4a; backend ditulis AI lain, jadi kamu TIDAK mengubah
kontrak: kalau ada yang kurang atau janggal, tulis CHANGE REQUEST (AI_RULES §9).

Lampiran: 3 file bundel (aturan + status + rencana, kontrak API, keputusan + desain sistem).
Bahan utama: API_CONTRACT §10 (contoh respons nyata) dan §11 (endpoint yang sudah ada).

Asumsi kerja (belum ada jawaban dosen/kelompok): emulator Android
(http://10.0.2.2:8000/api), tanpa build APK, belum ada desain UI. Untuk tampilan,
ikuti dokumen desain §6-§7 dengan Material 3 sederhana. Aturan Flutter: F-1 sampai F-13,
D-21. Versi paket: jangan menebak (G-8); beri perintah `flutter pub add` dan minta
aku menempel `flutter --version` bila perlu.
Environment: Windows, PowerShell, VS Code, Flutter 3.41.6 / Dart 3.11.4.

Langkah 1, balas hanya dengan: (1) ringkasan aturan inti dengan kata-katamu sendiri,
maks 8 baris; (2) maks 3 aturan yang paling mudah kamu langgar; (3) maks 5
pertentangan/kekosongan antar-dokumen (lokasi, dampak, usulan); (4) satu kalimat
"Siap, tugas pertama = ...". Jangan menulis kode di balasan ini.

Tugas pertama setelah itu: skeleton monorepo (packages/core dengan ApiClient, UserInfo,
AppConfig dari --dart-define, interceptor mock; kerangka customer_app dan mitra_app)
dan satu layar bukti di customer_app yang menampilkan GET /service-categories.
Selesai bila: kedua app jalan di emulator, layar itu menampilkan data dari backend lokal,
dan mode USE_MOCK=true menampilkan data tiruan.
```

---

## A. Setup (sekali)

1. Buat repo (monorepo) dengan struktur di `README.md`. Taruh `AI_RULES.md` di root; `CLAUDE.md` dan `AGENTS.md` cukup pointer pendek ke sana (jangan salin isinya). Taruh dokumen desain di `docs/`.
2. Buat **Project baru** di Claude dan di ChatGPT khusus proyek ini (jangan campur dengan chat mata kuliah lain). Di Claude Project unggah file aslinya. Di ChatGPT (Project gratis: maks 5 file) unggah **bundel 3 file** dari §G. Perbarui file di Project setiap kali dokumen berubah (isi Project tidak otomatis sinkron dengan repo).
3. Isi kolom instruksi Project dengan §F (versi ringkas) kalau tersedia.
4. Pertanyaan ke dosen: Q-1, Q-3, Q-7 (sebagian) sudah dijawab 10 Nov. Sisa yang masih terbuka di `DECISIONS.md` §3: Q-2, Q-4, Q-5, Q-6, Q-8, Q-9.
5. Aturan harian: setiap sesi mulai dengan §B (sesi pertama) atau §E.7 (melanjutkan), berakhir dengan prompt Checkpoint (§E.4), lalu tempel hasilnya ke **paling atas Log** `PROGRESS.md` dan commit. Setelah tahap hijau: push (Git-5).

---

## B. Templat Kickoff (hanya untuk sesi di luar §0)

Kolom `<ISI: ...>` di bawah wajib dipilih salah satu. Untuk tugas yang sudah ada di §0, pakai §0 saja.

```
Kamu asisten coding untuk proyek tugas akhir "Marketplace Jasa Rumahan"
(Flutter + Laravel + MySQL + Midtrans sandbox). Aku mahasiswa; kode harus bisa
kupahami dan kujelaskan saat presentasi.

Lampiran: AI_RULES.md, docs/PROGRESS.md, docs/PLAN_LANJUTAN.md, docs/API_CONTRACT.md,
docs/DECISIONS.md, dokumen desain (di ChatGPT: 3 file bundel §G, sudah mencakup semuanya).

Perananmu: <ISI: "Penulis backend/ + kontrak, reviewer Flutter" ATAU
"Penulis packages/core + apps/*, reviewer backend">.

Langkah 1, sebelum menulis kode apa pun, balas hanya dengan:
1. Ringkasan aturan inti proyek dengan kata-katamu sendiri (maks 8 baris).
2. Maks 3 aturan di AI_RULES.md yang paling mudah kamu langgar pada tugas pertama.
3. Maks 5 pertentangan/kekosongan antar-dokumen yang kamu temukan
   (tiap item: lokasi, dampak, usulan).
4. Satu kalimat: "Siap, tugas pertama = ...".

Jangan menulis kode di balasan ini.
```

---

## C. Templat tugas (hanya untuk tugas di luar §0)

Salin, isi bagian `< >`, lalu tempel. Satu prompt = satu tugas yang bisa selesai dalam satu sesi.

```
FASE: <P0-P6>   TUGAS: <satu kalimat>

KONTEKS TERAKHIR: <tempel bagian "Sekarang" dari PROGRESS.md>
ENVIRONMENT: Windows, Laragon, PowerShell, PHP 8.3, Laravel 10.50.4, MySQL 8.0.

INPUT TERLAMPIR: <kontrak bagian mana / file yang diubah / screenshot UI>

HASIL YANG DIMINTA (path file):
- <backend/app/Services/...>
- <apps/customer_app/lib/ui/...>

KRITERIA SELESAI (semua harus terpenuhi):
- <perilaku yang bisa diuji, contoh: "ubah harga di DB → /orders/quote ikut berubah">
- <kasus error: 401, 422, 409, jaringan putus>

BATASAN: ikuti AI_RULES.md. Jangan <hal yang tidak boleh disentuh>.
Jangan tambah dependency tanpa bertanya. Perintah git per blok kecil (Git-6).

FORMAT JAWABAN:
Preflight (3 baris) → rencana (maks 7 langkah) → kode per file (path di atas
tiap blok) → cara uji → yang belum terverifikasi → baris untuk PROGRESS.md →
Catatan Presentasi.
```

---

## D. Isian per fase

Urutan mengikuti prioritas implementasi di dokumen desain §17, dipotong per alur supaya bisa dites ujung ke ujung. Jadwal dipatok ke **pertemuan** (presentasi = pertemuan 13, freeze = akhir pertemuan 12 *usulan*), bukan tanggal, karena tanggal pasti belum diketahui (DECISIONS Q-7). Per 10 Nov 2026 proyek ada di sekitar pertemuan 8.

**Keputusan urutan kerja (10 Nov):** selesaikan **seluruh backend dulu**, baru Flutter (ChatGPT). Urutan sisa backend: tes catch-up B-13 → P4c → P5 → P4b → P6. Bagian "Customer app" / "Mitra app" di bawah dikerjakan **setelah backend selesai**; checkpoint Flutter akhir pertemuan 9 (*usulan*). Status tiap fase ditulis di judulnya; kebenaran terbarunya ada di `PROGRESS.md`.

### P0 · Fondasi: backend SELESAI, Flutter belum

- Backend: Laravel di `backend/`, 16 migration, 14 model, `config/marketplace.php`, seeder (akun demo di README). `migrate:fresh --seed` sukses.
- Flutter (belum): `packages/core` + skeleton `customer_app` dan `mitra_app`; `ApiClient`, `UserInfo`, `AppConfig`, interceptor mock, tema dasar.
- Kontrak v1.0 belum dibekukan (rencana pertemuan 12).

### P1 · Auth & Katalog: backend SELESAI (tes otomatis menyusul di catch-up)

- **Backend:** register/login/logout/me, katalog, `quote`.
- **Customer app:** Splash → Beranda (guest boleh) → daftar jasa → detail jasa (pilih ukuran, add-on, harga langsung dari `quote`) → Login/Register. **Mitra app:** Login/Register, beranda kosong.
- **Selesai bila:** guest bisa melihat katalog tanpa login; login salah menampilkan pesan Indonesia; token tersimpan dan hilang saat logout; ubah harga di DB → tampilan ikut berubah.

### P2 · Pemesanan: backend SELESAI (tes otomatis menyusul di catch-up)

- **Backend:** `slots`; `POST /orders` (guest dan login) dengan snapshot dan harga server; `track`; riwayat; `status-history`; matching attempt sekuensial.
- **Customer app:** pilih tanggal dan slot → form data → ringkasan → kirim → tracking (polling) → riwayat dan lacak pesanan (guest).
- **Selesai bila:** total palsu dari Postman diabaikan server; dobel-tap tidak membuat dua order; guest bisa melacak dengan kode + telepon; telepon salah → "tidak ditemukan".

### P3 · Alur Mitra: backend SELESAI dan dites (P3a, P3b, P3c)

- **Backend:** profil, pilih jasa, jadwal, offers accept/reject (B-5), progres status, `dev/simulate-paid`.
- **Mitra app:** profil, pilih jasa, atur jadwal, daftar tawaran (polling), terima/tolak, pesanan aktif dengan tombol progres, riwayat.
- **Belum:** bukti race nyata (`dev:race-accept`, P6) dan review silang (sekali, di akhir backend; B-5 paling rawan).

### P4 · Pembayaran & Penyelesaian: dipecah tiga

- **P4a SELESAI (hijau, 55 tes):** cancel (+ `refund_pending`), `confirm-completion`, `GET payment`.
- **P4c BERIKUTNYA:** `payments:expire-pending`, `orders:auto-complete`, lazy expiry bayar, jadwal di `Kernel.php`; tes dengan `Carbon::setTestNow`. Batas bayar dihitung dari `order_status_histories` (D-24).
- **P4b setelah P5:** Midtrans Sandbox. Prasyarat: akun Sandbox, kunci di `.env`, tunnel ngrok, `composer require midtrans/midtrans-php`. Isi: `MidtransService`, `POST /orders/{kode}/payments`, webhook B-7, Status API, tes webhook (signature salah, duplikat, out-of-order, amount beda).
- **Customer app:** tombol Bayar → WebView `redirect_url` → polling status → layar sukses/gagal; konfirmasi selesai; batalkan pesanan.
- **Selesai bila:** pembayaran Sandbox berjalan ujung ke ujung (via tunnel atau Status API); signature salah ditolak; notifikasi ganda tidak menggandakan proses; amount beda ditolak; mitra tidak bisa `depart` sebelum `paid`.

### P5 · Admin Dashboard (Blade): BERIKUTNYA setelah P4c

- **Claude:** login admin (guard `web`, telepon + password), dashboard ringkas, CRUD kategori/jasa/jenis/ukuran/harga/add-on (nonaktifkan, bukan hapus), daftar akun/mitra (+ approve), daftar order dan payment, tombol paksa selesai, tandai refunded. Bootstrap 5.
- **ChatGPT:** review keamanan (guard, CSRF, otorisasi) dan kesesuaian aturan A-1 sampai A-4.
- **Selesai bila:** admin mengubah harga lewat form dan `/orders/quote` memakai harga baru tanpa ubah kode.

### P6 · Hardening & Demo (sebelum freeze, akhir pertemuan 12)

- `dev:race-accept` (dua proses `php artisan` memanggil accept bersamaan, tepat satu menang); Postman collection di-export ke `docs/postman/`; **satu kali** review ChatGPT untuk seluruh branch bertumpuk (§E.1), lalu PR dan merge ke `main`; seeder demo bersih; uji "clone baru → README → jalan" di folder kosong; kontrak dibekukan v1.0; skrip demo 10 menit + rekaman cadangan.
- Luaran: **Manual Book** (§E.8). Poster, karya tulis ilmiah, dan laporan akhir **tidak diminta dosen**. Build APK hanya jika Q-8 menjawab perlu.
- **Selesai bila:** orang yang belum pernah melihat proyek bisa clone → jalankan → demo hanya dengan README.

---

## E. Prompt khusus

### E.1 Review silang (AI lain yang me-review)

```
Kamu REVIEWER, bukan penulis. Tinjau hasil kerja AI lain untuk fase <P?>.
Lampiran: AI_RULES.md, API_CONTRACT.md, <file/diff hasil>.
Untuk branch bertumpuk: tempel hasil `git diff main...<branch-teratas>` (satu kali,
bukan per branch), dan sebut ID tahap yang tercakup (mis. P3a-P4c).

Periksa berurutan:
1. Pelanggaran aturan ber-ID (sebut ID-nya).
2. Ketidaksesuaian dengan kontrak (field, enum, status code, nullability, format uang/waktu).
3. Bug logika dan race condition.
4. Keamanan (otorisasi, validasi, rahasia, signature).
5. Bagian yang akan sulit dijelaskan oleh mahasiswa.

Keluaran: tabel [Severity (Blocker/Major/Minor) | Lokasi | Masalah | Perbaikan singkat],
maks 12 temuan, urut keparahan. Jangan menulis ulang file. Jangan memuji.
Akhiri dengan verdict: LAYAK MERGE atau PERBAIKI DULU.
```

### E.2 Bug fix

```
MODE: <belajar | cepat>
BUG: <apa yang terjadi>        HARAPAN: <apa yang seharusnya>
LANGKAH REPRO: <1, 2, 3>
BUKTI: <error lengkap / storage/logs/laravel.log / output flutter run / respons Postman>
FILE TERKAIT: <path, tempel isinya>

Mode belajar: beri 2-3 hipotesis berurut peluang dan SATU cara cek per hipotesis.
Tunggu hasil cekku, baru beri perbaikan minimal.
Mode cepat: sebut penyebab paling mungkin, perbaikan minimal, dan cara memastikan
berhasil. Jangan mengubah file di luar penyebab bug.
```

### E.3 Change Request

Pakai format di `AI_RULES.md` §9. Minta AI lain menilai dampaknya sebelum kamu putuskan:

```
Nilai CHANGE REQUEST berikut dari sisi <backend/Flutter>: apa yang rusak jika
disetujui, apa yang rusak jika ditolak, dan migrasi minimal-nya. Maks 10 baris.
<tempel CHANGE REQUEST>
```

### E.4 Checkpoint / Handoff (akhir sesi)

```
Akhir sesi. Buat blok untuk docs/PROGRESS.md (maks 15 baris), dengan format:
Fase | Selesai (file + status) | Setengah jadi (file + kondisinya) | Keputusan baru |
CHANGE REQUEST tertunda | Bug yang diketahui | 3 langkah berikutnya |
Perintah untuk menjalankan. Hanya fakta dari sesi ini; jangan menambah klaim
yang belum diuji.
```

### E.5 Persiapan presentasi

```
Aku akan mempresentasikan fitur <X>. Berdasarkan kode di <file>:
1. Jelaskan alurnya dalam 6 langkah dengan bahasa sederhana.
2. Tunjukkan 3 potongan kode terpenting (file + fungsi) dan fungsinya.
3. Beri 5 pertanyaan yang mungkin diajukan dosen beserta jawaban singkat.
4. Sebutkan 2 keterbatasan yang jujur dan rencana pengembangannya.
```

### E.6 Menyusun dokumen kode program

Silabus menuntut kemampuan debugging, pengujian unit, integrasi, dan **dokumen kode program**. Ini bahan nilai, bukan pelengkap.

```
Susun bagian dokumentasi untuk modul <X> berdasarkan kode terlampir: tujuan,
alur data, endpoint/kelas utama, cara uji, dan batasan. Maks 1 halaman, tanpa
mengarang fitur yang tidak ada di kode.
```

---

### E.7 Melanjutkan sesi (awal chat baru)

Pakai ini sebagai pengganti §B kalau proyek sudah berjalan. Lampirkan: `AI_RULES.md`, `docs/PROGRESS.md`, `docs/PLAN_LANJUTAN.md`, `docs/DECISIONS.md`, dan `docs/API_CONTRACT.md` (di ChatGPT: 3 bundel §G).

```
Lanjut proyek Marketplace Jasa Rumahan (Laravel 10 + Flutter).
Baca PROGRESS.md dan DECISIONS §5 dulu, lalu PLAN_LANJUTAN.md.
Tugas: <contoh: "T0 housekeeping lalu T1 tes catch-up" / "T2 P4c expiry + auto-complete">.
Environment: Windows, Laragon, PowerShell, Laravel 10.50.4, PHP 8.3, MySQL 8.0.
Kode lengkap siap paste. Perintah git per blok kecil, berhenti kalau ada error.
Jangan nulis kode dulu, mulai dengan preflight (G-1).
```

### E.8 Menyusun Manual Book

Dosen meminta luaran hanya Manual Book berisi panduan cara memakai aplikasi, tanpa template baku. Kerangka ada di `PLAN_LANJUTAN.md` T7.

```
Susun bagian Manual Book untuk role <Customer | Mitra | Admin> berdasarkan
fitur yang BENAR-BENAR sudah jalan (lampirkan API_CONTRACT §11 dan daftar layar).
Format: langkah bernomor, satu aksi per langkah, tulis di mana screenshot
dipasang ([SCREENSHOT: nama layar]), dan pesan error yang mungkin muncul beserta
artinya. Bahasa Indonesia baku, ringkas. Jangan menjelaskan fitur yang belum
ada; tandai sebagai "belum tersedia".
```

---

## F. Versi ringkas untuk kolom instruksi ChatGPT

Dipakai bila kolom instruksi terbatas. Aturan lengkapnya ada di bundel 1 (§G).

```
Kamu asisten coding proyek "Marketplace Jasa Rumahan" (Flutter + Laravel + MySQL +
Midtrans sandbox) untuk mahasiswa. Patuhi AI_RULES.md; sumber kebenaran urut:
API_CONTRACT.md > DECISIONS.md > dokumen desain > PROGRESS.md. Jika bertentangan,
berhenti dan lapor. Jangan mengarang endpoint/tabel/kolom/status. Jangan tambah
fitur backlog atau dependency tanpa izin. Struktur Flutter ikuti modul kuliah
(lib/ui, model, service, helpers), tanpa state-management package. Harga dihitung
ulang di server; uang integer rupiah; status pesanan terpisah dari status bayar;
bayar valid hanya dari webhook/Status API, bukan redirect. Setiap jawaban:
Preflight 3 baris → kode (path di atas tiap blok, tanpa "...") → cara uji → yang
belum terverifikasi → baris PROGRESS → Catatan Presentasi. Jangan klaim sudah
dites jika belum dijalankan. Kode harus sederhana dan bisa dijelaskan mahasiswa.
```

---

## G. Bundel 3 file untuk ChatGPT

Project ChatGPT paket gratis hanya menampung **5 file** (terlihat di halaman Sumber; paket berbayar 25). Tiga bundel memakai 3 slot dan menyisakan 2. Dokumen sumber tetap terpisah di repo (satu topik = satu file, gampang di-diff); bundel dibuat otomatis dengan `scripts/bundle-chatgpt.ps1`, jangan diedit tangan.

| Bundel | Isi (berurutan) | Gunanya |
|---|---|---|
| `1_ATURAN_STATUS_RENCANA.md` | `AI_RULES.md` + `docs/PROGRESS.md` + `docs/PLAN_LANJUTAN.md` | aturan, kondisi terkini, tugas berikutnya |
| `2_KONTRAK_API.md` | `docs/API_CONTRACT.md` | kesepakatan Flutter <-> Laravel (bahan utama Flutter) |
| `3_KEPUTUSAN_DAN_DESAIN.md` | `docs/DECISIONS.md` + `docs/Dokumentasi_Sistem_Marketplace_Jasa_Rumahan.md` | keputusan, CR, desain sistem + 16 tabel |

Tidak ikut (tempel di chat bila perlu): `KODE_BACKEND.txt` (boleh diunggah sebagai file ke-4 hanya saat review backend, lalu dihapus lagi agar slot kosong; untuk Flutter tidak perlu), `PROMPTS.md` (kamu yang menempel promptnya; §F masuk kolom instruksi), `README.md`, dan kode PHP/Dart yang sedang dikerjakan.

**Pembagian kerja paralel:** satu Project ChatGPT dengan 3 bundel itu dipakai untuk Flutter (penulis `packages/core` dan `apps/*`) dan, nanti, untuk review backend (§E.1 + lampirkan `git diff`). Backend tetap di Claude Code/Claude Project dengan file asli. Karena kontrak adalah satu-satunya pertemuan kedua sisi, perubahan kontrak selalu lewat CHANGE REQUEST (AI_RULES §9), lalu bundel dibuat ulang dan diunggah ulang ke kedua Project.

**Membuat ulang bundel** (PowerShell, dari root repo; belum dijalankan di Windows saat ditulis, laporkan kalau error):

```powershell
powershell -ExecutionPolicy Bypass -File scripts/bundle-chatgpt.ps1
```

Hasil di `chatgpt-bundle/`. Di halaman Sumber Project ChatGPT: hapus file lama (termasuk README, API_CONTRACT, AI_RULES, PROGRESS, DECISIONS tertanggal 8 Nov yang sudah basi), lalu unggah 3 file baru. Lakukan setiap selesai satu tahap (setelah PROGRESS diperbarui) dan setiap kontrak/keputusan berubah.
