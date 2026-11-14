# PROMPTS: Kumpulan Prompt Coding (Claude + ChatGPT)

Cara kerja: **kontrak dulu, kode kemudian, review silang di akhir fase.**
Satu AI menulis, AI lainnya me-review. Manusia memutuskan.

Isi: [A. Setup](#a-setup-sekali) · [B. Kickoff](#b-prompt-kickoff-tempel-di-awal-setiap-chat-baru) · [C. Template tugas](#c-template-tugas) · [D. Isian per fase](#d-isian-per-fase) · [E. Prompt khusus](#e-prompt-khusus) · [F. Versi ringkas untuk instruksi ChatGPT](#f-versi-ringkas-untuk-kolom-instruksi-chatgpt)

---

## A. Setup (sekali)

1. Buat repo (monorepo) dengan struktur di `README.md`. Taruh `AI_RULES.md` di root, dan salin sebagai `CLAUDE.md` dan `AGENTS.md`. Taruh dokumen desain di `docs/`.
2. Buat **Project baru** di Claude dan di ChatGPT khusus proyek ini (jangan campur dengan chat mata kuliah lain). Unggah: `AI_RULES.md`, dokumen desain, `API_CONTRACT.md`, `DECISIONS.md`, `PROGRESS.md`.
3. Isi kolom instruksi Project dengan §F (versi ringkas) kalau tersedia.
4. Jawab dulu pertanyaan Q-1 sampai Q-7 di `DECISIONS.md` sebelum Fase P1.
5. Aturan harian: setiap sesi mulai dengan §B, berakhir dengan prompt Checkpoint (§E.4), lalu tempel hasilnya ke `PROGRESS.md` dan commit.

---

## B. Prompt Kickoff (tempel di awal setiap chat baru)

```
Kamu asisten coding untuk proyek tugas akhir "Marketplace Jasa Rumahan"
(Flutter + Laravel + MySQL + Midtrans sandbox). Aku mahasiswa; kode harus bisa
kupahami dan kujelaskan saat presentasi.

Lampiran: AI_RULES.md, docs/PROGRESS.md, docs/API_CONTRACT.md, docs/DECISIONS.md
[+ dokumen desain jika ini sesi pertama].

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

## C. Template tugas

Salin, isi bagian `< >`, lalu tempel. Satu prompt = satu tugas yang bisa selesai dalam satu sesi.

```
FASE: <P0-P6>   TUGAS: <satu kalimat>

KONTEKS TERAKHIR: <tempel bagian "Sekarang" dari PROGRESS.md>

INPUT TERLAMPIR: <kontrak bagian mana / file yang diubah / screenshot UI>

HASIL YANG DIMINTA (path file):
- <backend/app/Services/...>
- <apps/customer_app/lib/ui/...>

KRITERIA SELESAI (semua harus terpenuhi):
- <perilaku yang bisa diuji, contoh: "ubah harga di DB → /orders/quote ikut berubah">
- <kasus error: 401, 422, 409, jaringan putus>

BATASAN: ikuti AI_RULES.md. Jangan <hal yang tidak boleh disentuh>.
Jangan tambah dependency tanpa bertanya.

FORMAT JAWABAN:
Preflight (3 baris) → rencana (maks 7 langkah) → kode per file (path di atas
tiap blok) → cara uji → yang belum terverifikasi → baris untuk PROGRESS.md →
Catatan Presentasi.
```

---

## D. Isian per fase

Urutan mengikuti prioritas implementasi di dokumen desain §17, tetapi dipotong per alur supaya bisa dites ujung ke ujung. Tanggal mengikuti asumsi mulai Senin 9 Nov 2026; sesuaikan dengan deadline dosen.

### P0 · Fondasi (Sab-Min 7-8 Nov, maks Sen 9 Nov)

Tujuan: semua orang bisa menjalankan proyek di laptopnya, kontrak v1.0 terkunci.

- **Claude (backend):** init Laravel di `backend/` (pasang Sanctum/API sesuai versi terpasang); `.env.example`; `config/marketplace.php` memuat parameter DECISIONS §1; 16 migration persis dokumen desain §13 + indeks §15.14; model + relasi; seeder master dan akun demo (B-14); zona waktu; lengkapi contoh JSON `API_CONTRACT` untuk grup Auth, Katalog, Quote.
- **ChatGPT (Flutter):** `packages/core` + skeleton `customer_app` dan `mitra_app` sesuai konvensi modul; `ApiClient`, `UserInfo`, `AppConfig` (dari `--dart-define`), interceptor mock, tema dasar, rute awal; review kontrak dari sisi Flutter (nama field, nullability, enum).
- **Selesai bila:** `php artisan migrate:fresh --seed` sukses; kedua app jalan di emulator dengan layar placeholder; `GET /api/service-categories` bisa dipanggil dari app; kontrak v1.0 disetujui.

### P1 · Auth & Katalog (minggu 1)

- **Backend:** register/login/logout/me untuk customer dan mitra; endpoint katalog, tipe/ukuran properti, harga, add-on; `quote`.
- **Customer app:** Splash → Beranda (guest boleh) → daftar jasa → detail jasa (pilih ukuran, add-on, harga langsung dari `quote`) → Login/Register.
- **Mitra app:** Login/Register, beranda kosong.
- **Selesai bila:** guest bisa melihat katalog tanpa login; login salah menampilkan pesan Indonesia; token tersimpan dan hilang saat logout; ubah harga di DB → tampilan ikut berubah.

### P2 · Pemesanan (minggu 2, awal)

- **Backend:** `slots`; `POST /orders` (guest dan login) dengan snapshot nama/telepon/alamat/harga/add-on; `track`; riwayat; `status-history`; awal matching (attempt #1).
- **Customer app:** pilih tanggal dan slot → form data (isi otomatis dari profil, bisa diubah) → ringkasan → kirim → layar tracking (polling) → riwayat (akun) dan lacak pesanan (guest).
- **Selesai bila:** total palsu dari Postman diabaikan server; dobel-tap tidak membuat dua order; guest bisa melacak dengan kode + telepon; telepon salah → "tidak ditemukan".

### P3 · Alur Mitra (minggu 2)

- **Backend:** profil mitra, pilih jasa, jadwal, `offers` (accept/reject), expiry (scheduler + lazy), matching sekuensial, progres status, cek bentrok jadwal, endpoint `dev/simulate-paid`.
- **Mitra app:** profil, pilih jasa, atur jadwal, daftar tawaran (polling), terima/tolak, pesanan aktif dengan tombol progres sesuai status, riwayat.
- **Selesai bila:** dua mitra accept bersamaan → hanya satu berhasil (tes otomatis); tolak/kedaluwarsa → pindah ke mitra berikutnya; tidak ada mitra → order `cancelled`; slot bentrok ditolak.
- **Review silang wajib** di akhir P3 (§E.1): bagian B-5 paling rawan.

### P4 · Pembayaran & Penyelesaian (minggu 3)

- **Backend:** Snap lewat library resmi, tabel `payments`, webhook (B-7), cek status Midtrans, expiry bayar, pembatalan + `refund_pending`, `confirm-completion`, auto-complete.
- **Customer app:** tombol Bayar → WebView `redirect_url` → polling status → layar sukses/gagal; konfirmasi selesai; batalkan pesanan.
- **Selesai bila:** pembayaran sandbox berjalan ujung ke ujung (via tunnel atau Status API); signature salah ditolak; notifikasi ganda tidak menggandakan proses; amount beda ditolak; mitra tidak bisa `depart` sebelum `paid`.

### P5 · Admin Dashboard (minggu 3)

- **Claude:** login admin, dashboard ringkas, CRUD kategori/jasa/jenis/ukuran/harga/add-on, daftar akun/mitra (+ approve), daftar order dan payment, tombol paksa selesai, tandai refunded.
- **ChatGPT:** review keamanan (guard, CSRF, otorisasi) dan kesesuaian dengan aturan A-1 sampai A-4.
- **Selesai bila:** admin mengubah harga lewat form dan customer app melihat harga baru tanpa ubah kode.

### P6 · Hardening & Demo (minggu 4; feature freeze)

- Tutup bug dari daftar `PROGRESS.md`; tes B-13 hijau; seeder demo bersih; Postman collection di-export ke repo; build APK kedua app; skrip demo 10 menit (urutan klik) + rekaman cadangan; README dan dokumen akhir; bahan poster, video, dan KTI.
- **Selesai bila:** orang yang belum pernah melihat proyek bisa clone → jalankan → demo hanya dengan README.

---

## E. Prompt khusus

### E.1 Review silang (AI lain yang me-review)

```
Kamu REVIEWER, bukan penulis. Tinjau hasil kerja AI lain untuk fase <P?>.
Lampiran: AI_RULES.md, API_CONTRACT.md, <file/diff hasil>.

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

## F. Versi ringkas untuk kolom instruksi ChatGPT

Dipakai bila kolom instruksi terbatas. Tetap unggah `AI_RULES.md` sebagai file.

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
