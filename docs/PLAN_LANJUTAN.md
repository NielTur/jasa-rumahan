# PLAN_LANJUTAN: Rencana Kerja Setelah P4a

> Dibuat 11 Nov 2026 untuk dilanjutkan di chat berikutnya. Kondisi terkini ada di [`PROGRESS.md`](PROGRESS.md); keputusan di [`DECISIONS.md`](DECISIONS.md). Kalau ada yang bertentangan, `PROGRESS.md` dan kode yang menang, lalu perbaiki file ini.
> Cara memulai chat baru: pakai prompt siap tempel di **`PROMPTS.md` §0** (tidak ada yang perlu diisi); untuk tugas lain pakai §E.7.

## Gambaran

Tujuan: demo di **pertemuan 13** dengan backend lengkap (termasuk Midtrans Sandbox), dua app Flutter yang menjalankan alur utama, dan **Manual Book** sebagai satu-satunya luaran tertulis.

| Kode | Pekerjaan | Bergantung pada | Perkiraan sesi chat | Status |
|---|---|---|---|---|
| T0 | Housekeeping (commit dokumen, key:generate, cek Private) | - | 0,5 | belum |
| T1 | Tes catch-up B-13 untuk P1/P2 | T0 | 1 | kode tes ada di chat lama, belum disimpan |
| T2 | P4c: expiry bayar + auto-complete | T1 | 1 | belum |
| T3 | P5: Admin Blade | T2 | 2 | belum |
| T4 | P4b: Midtrans Sandbox | T3, akun Sandbox, ngrok | 2 | belum |
| T5 | P6: hardening backend | T4 | 1-2 | belum |
| T6 | Flutter (customer + mitra), ChatGPT | T5 (sebagian bisa mulai setelah T3) | 4+ | belum |
| T7 | Manual Book | T6 | 1-2 | belum |

Urutan T2 → T3 → T4 disengaja: P4b butuh akun Sandbox dan tunnel, sedangkan P4c dan P5 tidak. Backend bisa jalan terus sementara akun dibuat.

**Aturan di setiap tugas:** mulai dengan preflight 3 baris (G-1); kode lengkap siap tempel dengan path file (G-6); jangan bilang "sudah dites" tanpa output (G-7); akhiri dengan langkah uji, yang belum terverifikasi, baris PROGRESS, dan Catatan Presentasi (G-10); satu tahap = satu branch = satu commit, lalu push (Git-5); perintah git per blok kecil (Git-6).

**Branch:** tumpukkan di `feat/order-customer-actions` (branch teratas saat ini). Nama yang diusulkan: `feat/test-catchup-b13` (T1), `feat/payment-expiry` (T2), `feat/admin-dashboard` (T3), `feat/midtrans-payment` (T4), `feat/hardening` (T5).

---

## T0 · Housekeeping (kerjakan dulu, ±15 menit)

Semua perintah di PowerShell, dari `C:\laragon\www\jasa-rumahan`. Tempel hasil tiap blok; berhenti kalau ada `error:` atau `fatal:`.

- [ ] **Cek keadaan git.** `git status` lalu `git branch -vv`. Harapan: bersih, branch `feat/order-customer-actions` di commit `ee82fbd` (atau lebih baru).
- [ ] **Masukkan dokumen baru.** Ekstrak zip dokumen ke root repo (struktur folder sudah sama, timpa jika ditanya): `README.md`, `AI_RULES.md`, `CLAUDE.md`, `AGENTS.md`, `scripts/bundle-chatgpt.ps1`, `scripts/dump-code.ps1` di root; `API_CONTRACT.md`, `DECISIONS.md`, `PROGRESS.md`, `PROMPTS.md`, `PLAN_LANJUTAN.md` di `docs/`. Tambahkan satu baris `chatgpt-bundle/` ke `.gitignore`. Lalu `git status` (harapan: 11 file berubah/baru), `git add README.md AI_RULES.md CLAUDE.md AGENTS.md scripts docs .gitignore`, `git commit -m "docs: perbarui paket dokumen (kontrak v0.2, keputusan, rencana lanjutan)"`, `git push`.
- [ ] **Buat APP_KEY baru.** `cd backend` lalu `php artisan key:generate`. Cek `.env.example` masih `APP_KEY=` kosong: `findstr APP_KEY .env.example`. Key lama pernah ter-push, jadi anggap sudah bocor; key baru hanya hidup di `.env` lokal. (Efek samping dev: token/session lama tidak valid, login ulang.)
- [ ] **Pastikan repo Private.** Di GitHub: buka repo → Settings → General → bagian Danger Zone menampilkan "Change repository visibility"; badge di samping nama repo harus "Private". Kalau Public, ubah ke Private.
- [ ] **Cek data master.** Di HeidiSQL (DB `jasa_rumahan`): `SELECT COUNT(*) FROM services;` Harapan: 3. Kalau bukan 3, catat angkanya di PROGRESS dan jangan lanjut asumsi tes sebelum dicek.
- [ ] **Perbarui isi Project.** Claude Project: unggah file asli. ChatGPT: jalankan `powershell -ExecutionPolicy Bypass -File scripts/bundle-chatgpt.ps1`, lalu unggah 3 file di `chatgpt-bundle/` (`PROMPTS.md` §G). Isi Project tidak sinkron otomatis.

**Selesai bila:** `git status` bersih, push berhasil, `key:generate` sudah dijalankan, repo Private, COUNT dicatat.

---

## T1 · Tes catch-up B-13 (P1/P2)

Konteks: P1 dan P2 dites manual saja. Empat file tes (34 tes) sudah ditulis di chat sebelumnya tetapi **belum disimpan dan belum dijalankan**. Target total: 55 + 34 = **89 tes**.

- [ ] Cari empat file itu di chat lama (nama file ada di pesan itu). Kalau tidak ketemu, minta Claude menulis ulang berdasarkan daftar di bawah. Jangan menebak isinya dari ingatan.
- [ ] Buat branch: `git checkout -b feat/test-catchup-b13` (dari branch teratas, `git status` bersih dulu).
- [ ] Simpan ke `backend/tests/Feature/...`, pakai trait `BuildsMitraFixtures` bila cocok.
- [ ] Jalankan `php artisan test`. Harapan: 89 lulus. Tes yang merah **bukan otomatis salah tes**: bisa jadi bug asli. Putuskan satu per satu (bug kode vs asumsi tes salah) dan catat.
- [ ] Commit, push (Git-5).

Cakupan minimal yang harus ada:

| Area | Kasus |
|---|---|
| Auth | register customer/mitra (201 + token), login benar/salah (401), telepon dinormalisasi `+62` → `0`, admin ditolak (403), logout mencabut token, `/auth/me` |
| Katalog + quote | harga benar (mis. 270000), kombinasi jasa+ukuran tanpa harga (422), add-on jasa lain (422), `quantity` > 20 (422), jasa nonaktif (404), ubah harga di DB → quote ikut berubah |
| Slots | 6 slot, slot terlalu dekat `available=false`, slot tanpa mitra `available=false`, mitra bentrok membuat slot penuh |
| Buat order | guest dan login (201), total palsu dari client diabaikan, bukan slot (422), dobel-tap (409), slot tanpa mitra (409) |
| Lacak | kode + telepon benar, telepon salah (404 generik), 5 gagal → 429, polling sukses tidak terkena 429 (CR-10) |
| Transisi status | transisi valid dan invalid lewat `OrderStatusService` (409 lewat API) |
| Matching | attempt dibuat, expired → pindah ke mitra berikutnya, habis → `cancelled` |

**Dugaan bug yang harus dibuktikan dulu lewat tes:** (1) harga bertipe string lolos cast di request; (2) `quantity` negatif belum ditolak. Kalau terbukti, perbaiki di `FormRequest` dan catat sebagai bug yang ditemukan tes.

---

## T2 · P4c: expiry pembayaran + auto-complete

Aturan: D-06, D-07, D-24, B-15, B-4. Semua perubahan status lewat `OrderStatusService`.

- [ ] **Preflight.** Tentukan di preflight: sumber waktu `waiting_payment` (baris `order_status_histories` terakhir dengan `new_status = waiting_payment`); sumber waktu `mitra_completed` (idem).
- [ ] **`PaymentExpiryService` + command `payments:expire-pending`.** Dua kasus: (a) `payments.payment_status = pending` dan `expired_at` lewat → `expired`, order `waiting_payment` → `cancelled` (`cancelled_by = system`, `cancelled_at`); (b) order `waiting_payment` yang lewat `PAYMENT_EXPIRY_MINUTES` sejak transisi dan **tidak punya payment** → `cancelled`. Jangan sentuh order yang payment-nya `paid`.
- [ ] **`orders:auto-complete`.** `mitra_completed` yang lewat `AUTO_COMPLETE_HOURS` → `completed` (`completed_at` terisi, actor system).
- [ ] **Lazy expiry (B-15).** Panggil logika yang sama dari endpoint baca: `show`, `track`, `history`, `payment`, `customer/orders`, `mitra/orders*`. Cukup per order yang dibaca, bukan scan seluruh tabel.
- [ ] **`Kernel.php`:** `payments:expire-pending` tiap menit, `orders:auto-complete` tiap jam. Hapus komentar "menyusul di P4".
- [ ] **Tes** dengan `Carbon::setTestNow`: bayar belum lewat → tidak berubah; lewat → `cancelled`; order tanpa payment lewat batas → `cancelled`; payment `paid` tidak dibatalkan; auto-complete lewat/belum lewat; lazy via GET; idempoten (command dijalankan dua kali). Target ±10 tes.
- [ ] **Dokumen:** `API_CONTRACT` §9 dan §11; `DECISIONS` §1 (D-06/D-07/D-24 pindah ke "dipakai di kode"), G-07 dan G-09 jadi TUTUP; baris PROGRESS.

**Selesai bila:** semua tes hijau; di Postman, order `waiting_payment` yang dibiarkan lewat batas berubah `cancelled` hanya dengan memanggil GET; `php artisan schedule:list` menampilkan 3 command.

---

## T3 · P5: Admin Dashboard (Blade)

Aturan: A-1..A-4, B-1, B-12, D-20, G-04. Blade memakai Service yang sama dengan API, tidak memanggil API lewat HTTP.

- [ ] **Auth admin.** Guard `web` (session), login telepon + password, middleware role admin, CSRF aktif. Customer/mitra yang mencoba masuk ditolak. Layout Bootstrap 5 tunggal (`resources/views/layouts/admin.blade.php`).
- [ ] **Dashboard** ringkas: jumlah order per status, order hari ini, payment `refund_pending`.
- [ ] **CRUD master:** `service-categories`, `services`, `property-types`, `property-sizes`, `service-prices`, `service-addons`. Tombol **Nonaktifkan** (bukan hapus) untuk data yang pernah dipakai (A-3, B-12). Harga hanya lewat form (A-4).
- [ ] **Akun dan mitra:** daftar `users`, daftar `mitra` + approve/suspend (relevan saat `MITRA_AUTO_APPROVE=false`).
- [ ] **Orders dan payments:** daftar dengan pagination + filter status; detail order (riwayat status); tombol **paksa selesai** (lewat `OrderStatusService`, actor admin); tombol **tandai refunded** / **refund gagal** untuk payment `refund_pending` (D-08; isi `refunded_at`).
- [ ] **Tes:** guest dialihkan ke login; customer/mitra tidak bisa membuka `/admin`; ubah harga lewat form → `/orders/quote` berubah (A-4); nonaktifkan jasa → tidak muncul di katalog publik; paksa selesai menulis riwayat status; tandai refunded mengisi `refunded_at`.
- [ ] **Dokumen:** kontrak §8 jadi "sudah diimplementasi", §11, PROGRESS. Screenshot untuk Manual Book (T7).

**Selesai bila:** admin mengubah harga lewat form, dan customer yang memanggil `/orders/quote` melihat harga baru tanpa menyentuh kode.

---

## T4 · P4b: Midtrans Sandbox

Aturan: B-7, B-3, F-8, D-11, D-22, D-23. Ini satu-satunya API eksternal yang dipersyaratkan dosen, jadi **bukan opsional**.

**Prasyarat (manusia, sebelum sesi):**

- [ ] Buat akun Midtrans **Sandbox** (bukan Production). Ambil Server Key dan Client Key dari Settings → Access Keys.
- [ ] Isi `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION=false` di `backend/.env` saja. **Jangan** di `.env.example`, jangan di chat, jangan di commit (G-9).
- [ ] ngrok: `ngrok http 8000`. URL berubah tiap restart; isi **Payment Notification URL** di dashboard Sandbox dengan `https://<tunnel>/api/webhooks/midtrans` dan perbarui setiap tunnel baru.
- [ ] Konfirmasi versi: `composer show laravel/framework` (G-8), lalu `composer require midtrans/midtrans-php` (sudah disetujui; catat versi terpasang di PROGRESS).

**Tugas:**

- [ ] `config/midtrans.php` (dari `.env`); `MidtransService` yang membungkus library (buat Snap, ambil status). Dibungkus supaya tes bisa memalsukannya tanpa internet.
- [ ] **`POST /orders/{kode}/payments`:** hanya dari `waiting_payment`; `gateway_order_id = {kode}-{n}` (D-11); `amount` = `total_price` order (integer); simpan `payments` `pending`, `expired_at`; balas `snap_token`, `redirect_url`, `expired_at`. Jika sudah ada payment `pending` yang masih berlaku, pakai ulang.
- [ ] **`POST /webhooks/midtrans`:** verifikasi `signature_key` = SHA512(`order_id` + `status_code` + `gross_amount` + ServerKey) dari string persis diterima; cari payment lewat `gateway_order_id`; abaikan status yang "lebih mundur"; cocokkan `gross_amount`; update dalam transaksi + simpan payload mentah (`gateway_payload`); `paid` dan order `waiting_payment` → `OrderStatusService` ke `confirmed`; balas 200. Signature salah → 403. D-23: `paid` untuk order yang sudah `cancelled` → order tetap `cancelled`, payment `refund_pending`.
- [ ] **Fallback Status API:** `GET /orders/{kode}/payment` menanyakan Midtrans bila `pending`, memakai logika update yang sama dengan webhook.
- [ ] **D-22:** cancel customer saat payment `pending` mencoba membatalkan transaksi di Midtrans (best-effort, gagal tidak menggagalkan cancel).
- [ ] **Tes** (Midtrans dipalsukan): signature salah, duplikat, out-of-order (`pending` setelah `settlement`), amount beda, `order_id` tak dikenal (putuskan di preflight: 200 + log supaya Midtrans tidak retry terus), `paid` untuk order `cancelled` (D-23), mitra tidak bisa `depart` sebelum `paid`.
- [ ] **Uji nyata di Sandbox** dengan Payment Simulator Midtrans: buat order → mitra accept → bayar via Snap → webhook masuk → order `confirmed`. Catat hasilnya (G-7).
- [ ] **Dokumen:** kontrak §7 (hapus label "belum"), §10 contoh `POST /payments` dan webhook, §11; DECISIONS D-11/D-22/D-23 jadi dipakai; PROGRESS.

**Selesai bila:** satu pembayaran Sandbox sungguhan berhasil ujung ke ujung dan semua tes webhook hijau.

---

## T5 · P6: hardening backend

- [ ] **`dev:race-accept`.** Command artisan yang menyiapkan data lalu menjalankan dua proses `php artisan` sekaligus (Symfony Process) memanggil accept untuk attempt yang sama; tepat satu boleh sukses. Skenario pasti (double submit satu mitra vs accept melawan `process-expired-offers`) ditetapkan di preflight karena matching sekuensial hanya menawari satu mitra per attempt. Hasil dicatat di PROGRESS sebagai bukti B-5 yang tidak diberikan tes PHPUnit.
- [ ] **Postman collection** untuk alur utama (auth → katalog → quote → order → mitra accept → bayar → progres → konfirmasi) di `docs/postman/`. Ini sekaligus verifikasi kontrak §10.10 yang masih "belum diuji Postman"; perbaiki kontrak lewat CR jika ada beda.
- [ ] **Review silang sekali** oleh ChatGPT: tempel `git diff main...<branch-teratas>` dengan prompt `PROMPTS.md` §E.1. Perbaiki Blocker dan Major; catat sisanya.
- [ ] **Merge.** Dari `main` bersih: `git merge --ff-only <branch-teratas>`, lalu `git push`. Hapus branch lama hanya setelah `main` terbukti memuat semuanya.
- [ ] **Uji "clone baru".** Clone ke folder kosong, ikuti README apa adanya sampai `migrate:fresh --seed` dan `php artisan test`. Perbaiki README untuk tiap langkah yang macet.
- [ ] **Bekukan kontrak v1.0** (ubah header `API_CONTRACT`), dan ubah status D-xx yang sudah disetujui kelompok jadi DISEPAKATI.

**Selesai bila:** orang lain bisa clone → jalankan → demo hanya dengan README; `main` = semua kode hijau.

---

## T6 · Flutter (ChatGPT)

Dikerjakan setelah backend stabil (sebagian bisa mulai setelah T3). Penulis: ChatGPT; reviewer: Claude (kebalikan dari backend). Aturan: F-1..F-13, D-21.

- [ ] Jawab dulu: Q-5 (ada desain UI?), Q-4 dan Q-8 (emulator atau HP; perlu APK?). Tanpa jawaban, asumsi kerja: emulator Android, tanpa APK.
- [ ] Mulai chat ChatGPT dengan Prompt Kickoff `PROMPTS.md` §B (peran: penulis `packages/core` + `apps/*`). Lampirkan 3 file bundel (`PROMPTS.md` §G); `API_CONTRACT.md` §10-§11 adalah bahan utama.
- [ ] Urutan: skeleton monorepo (`packages/core`, `ApiClient`, `UserInfo`, `AppConfig`) → customer: katalog + login/register → customer: buat order (slot, form, ringkasan) → tracking (polling 5-10 detik, F-9) → mitra: login, profil, jasa, jadwal, offers → mitra: pesanan + tombol progres → customer: bayar (WebView + polling, F-8), konfirmasi, batal.
- [ ] **Checkpoint akhir pertemuan 9 (usulan):** login + katalog + buat pesanan jalan dari emulator ke backend lokal. Jika meleset, pangkas layar (bukan backend); gunakan urutan prioritas DECISIONS §4.
- [ ] Review silang oleh Claude di akhir tiap blok besar (`PROMPTS.md` §E.1), dan pastikan nama field sama persis dengan kontrak.
- [ ] Kumpulkan screenshot tiap layar yang jadi, untuk Manual Book.

---

## T7 · Manual Book

Format bebas (tidak ada template dari dosen); isi = panduan memakai aplikasi. Gunakan `PROMPTS.md` §E.8 per bagian, hanya untuk fitur yang benar-benar jalan.

1. **Pendahuluan:** tujuan aplikasi, peran (Customer, Mitra, Admin), gambaran alur pesanan.
2. **Persiapan:** instal/jalankan aplikasi, akun demo (README), catatan pembayaran Sandbox.
3. **Panduan Customer:** daftar/masuk atau guest, cari jasa, pilih ukuran + add-on, pilih slot, buat pesanan, pantau status, bayar, konfirmasi selesai, batalkan, lacak pesanan sebagai guest.
4. **Panduan Mitra:** daftar/masuk, atur profil, pilih jasa, atur jadwal, terima/tolak tawaran, jalankan pesanan (berangkat, tiba, mulai, selesai).
5. **Panduan Admin:** login, kelola kategori/jasa/harga/add-on, approve mitra, pantau order dan payment, paksa selesai, tandai refund.
6. **Troubleshooting:** pesan error umum (jadwal bentrok, tidak ada mitra, pembayaran kedaluwarsa) dan artinya.
7. **Lampiran:** daftar akun demo, daftar status pesanan dan artinya, keterbatasan yang jujur (mis. refund manual, tanpa push notification).

**Selesai bila:** orang yang belum pernah melihat aplikasi bisa menyelesaikan satu pesanan penuh hanya dengan membaca Manual Book.

---

## Yang butuh tindakan manusia (bukan AI)

| Apa | Kapan | Catatan |
|---|---|---|
| Tanya dosen: Q-8 (perlu APK?), Q-9 (Sandbox cukup?), tanggal pasti pertemuan 13 | secepatnya | jawab di DECISIONS §3 |
| Isi tabel tim di README (Q-6) | sebelum T7 | Manual Book perlu nama dan peran |
| Buat akun Midtrans Sandbox + ambil kunci | sebelum T4 | kunci hanya di `.env` |
| Jawab Q-5 (desain UI) dan Q-4 (lingkungan demo) | sebelum T6 | |
| Setujui atau ubah D-22, D-23, D-24 | sebelum T2 dan T4 | usulan Claude, belum disetujui |
| Perbarui isi Project Claude dan ChatGPT setelah dokumen berubah | tiap sesi besar | tidak sinkron otomatis |
