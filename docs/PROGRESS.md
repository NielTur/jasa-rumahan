# PROGRESS: Catatan Serah-Terima Antar Sesi

> Ini satu-satunya "ingatan" proyek untuk AI. Ringkas, faktual, **terbaru di atas**.
> Tempel bagian **Sekarang** ke prompt tugas (`PROMPTS.md` §C), atau pakai prompt lanjut sesi (`PROMPTS.md` §E.7). Tambahkan entri baru di paling atas **Log** setiap akhir sesi (`PROMPTS.md` §E.4).
> Rencana kerja berikutnya yang rinci ada di [`PLAN_LANJUTAN.md`](PLAN_LANJUTAN.md).

## Sekarang

| Item                            | Isi                                                                                                                                                                                                                                                                                                          |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Fase aktif                      | **P4 (backend) dan P1 (Flutter Foundation), dikerjakan secara paralel.** Urutan pekerjaan backend tetap: tes catch-up B-13 → P4c → P5 → P4b → P6 → Manual Book.                                                                                                                                              |
| Terakhir diperbarui             | 11 Nov 2026 oleh GPT-6, setelah pengerjaan Flutter Foundation – Shared Core selesai.                                                                                                                                                                                                                         |
| Selesai (backend, dites hijau)  | P1 auth + katalog + quote · P2 slots + buat order + matching · P3a-c sisi mitra + simulate-paid · P4a cancel + confirm-completion + GET payment. `php artisan test`: **55 lulus, 239 assertion**.                                                                                                            |
| Setengah jadi                   | 1) **34 tes catch-up B-13** (P1/P2) sudah ditulis di chat sebelumnya, tetapi belum disimpan dan dijalankan; jika file hilang, perlu ditulis ulang (T1). 2) Review ChatGPT untuk P3a-P4a belum dilakukan; ditunda sampai backend selesai.                                                                     |
| Sudah dikonfirmasi (11 Nov, T0) | `php artisan key:generate` dijalankan; `.env.example` berisi `APP_KEY=` kosong dan hanya `.env.example` yang dilacak Git; repo GitHub **Private**; `SELECT COUNT(*) FROM services;` = **3**; `scripts/dump-code.ps1` berjalan (126 file, 243 KB → `chatgpt-bundle/KODE_BACKEND.txt`).                        |
| Belum terkonfirmasi             | `scripts/bundle-chatgpt.ps1` belum dijalankan di Windows (3 bundel ChatGPT yang diunggah dibuat Claude dengan logika yang sama).                                                                                                                                                                             |
| Bug yang diketahui              | Dugaan, belum dibuktikan tes: harga bertipe string lolos cast; `quantity` negatif belum ditolak. Periksa melalui tes catch-up.                                                                                                                                                                               |
| Keputusan USULAN yang menunggu  | D-22, D-23, D-24 (DECISIONS §1); Q-2, Q-4, Q-5, Q-6, Q-8, Q-9 terbuka.                                                                                                                                                                                                                                       |
| 3 langkah berikutnya (backend)  | 1) **T1:** tes catch-up B-13 (tulis ulang 34 tes, target 89 hijau). 2) **T2:** P4c — `payments:expire-pending`, `orders:auto-complete`, lazy expiry. 3) **T3:** P5 admin Blade.                                                                                                                              |
| Pekerjaan paralel (Flutter)     | 1) Flutter Foundation selesai: scaffold Customer App dan Mitra App, integrasi Shared Core, konfigurasi dependency, serta pengujian dasar. 2) Berikutnya: mulai pengembangan UI dan alur Customer App. 3) Lanjutkan fondasi UI dan alur Mitra App. Pekerjaan dilakukan pada branch `feat/flutter-foundation`. |
| Cara menjalankan                | `cd backend` → `php artisan migrate:fresh --seed` → `php artisan serve --host=0.0.0.0 --port=8000`; terminal lain `php artisan schedule:work`. Tes: `php artisan test` (MySQL DB `jasa_rumahan_test` harus ada, lihat README).                                                                               |
|                                 |

## Versi terpasang (dari output perintah)

| Alat                                           | Versi                                  |
| ---------------------------------------------- | -------------------------------------- |
| PHP                                            | 8.3.32                                 |
| Laravel                                        | 10.50.4                                |
| Laravel Sanctum                                | 3.3.3                                  |
| Composer                                       | 2.11.1                                 |
| MySQL (Laragon)                                | 8.0.30                                 |
| PHPUnit                                        | ^10 (dari `composer.json`)             |
| HeidiSQL                                       | 12.1.0.6537                            |
| Flutter / Dart                                 | 3.41.6 / 3.11.4                        |
| Dio, shared_preferences, intl, webview_flutter | belum dipasang (Flutter belum dimulai) |

Lingkungan: Windows, Laragon, VS Code, PowerShell.

## Repo dan branch

GitHub: https://github.com/NielTur/jasa-rumahan (target: Private). Branch ditumpuk (stacked): tiap branch dibuat dari branch sebelumnya, jadi review dan merge dilakukan **sekali di branch teratas**.

| Branch                        | Commit                                                                                                                                                          | Isi                                                                             | Tes setelah branch ini |
| ----------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------- | ---------------------- |
| `main`                        | `09a0859`                                                                                                                                                       | P1 + P2 (sebelumnya `97e25bc`) + perbaikan `.env.example` (APP_KEY dikosongkan) | belum ada tes otomatis |
| `feat/mitra-setup`            | `f9bbefd` (kode P3a; ujung branch `9cf6850` = commit dokumen di atasnya)                                                                                        | P3a profil, jasa, jadwal mitra + `phpunit.xml` ke DB test                       | 12                     |
| `feat/mitra-offers`           | `52991be`                                                                                                                                                       | P3b offers accept/reject (B-5)                                                  | 24                     |
| `feat/mitra-orders`           | `3c64ced`                                                                                                                                                       | P3c order mitra, progres, simulate-paid                                         | 38                     |
| `feat/order-customer-actions` | `c04bcac` (P4a), lalu `adefd70` (perbaikan `.env.example`, fast-forward), `ee82fbd` (dokumen), ujung branch **`fa5f42f`** (paket dokumen 11 Nov, sudah di-push) | P4a cancel, confirm-completion, GET payment                                     | **55**                 |

Cek keadaan sebenarnya dengan `git branch -vv` dan `git log --oneline -5` sebelum mulai; tabel ini ditulis dari output yang ditempel di chat.

## Catatan teknis yang perlu diingat

- **Tes memakai MySQL terpisah** `jasa_rumahan_test` (bukan SQLite, karena `lockForUpdate` diabaikan di SQLite). `phpunit.xml` memaksa `DB_CONNECTION` dan `DB_DATABASE` dengan `force="true"`, tes memakai `RefreshDatabase`. DB demo `jasa_rumahan` tidak tersentuh (diverifikasi lewat HeidiSQL). Jangan `php artisan config:cache` di dev.
- **Trait fixture tes:** `backend/tests/Concerns/BuildsMitraFixtures.php` (`seedFixtures`, `makeMitra`, `makeOrder`, `loginSebagai`, `jadwalBesok`). Dipakai ulang oleh tes baru.
- **Tes "accept bersamaan"** berjalan satu proses PHPUnit, jadi hanya membuktikan re-check, bukan race nyata. Bukti nyata: `dev:race-accept` (P6).
- **Kernel.php** baru menjadwalkan `orders:process-expired-offers` (tiap menit). `payments:expire-pending` dan `orders:auto-complete` ditambah di P4c.
- **Akun demo** (`php artisan migrate:fresh --seed`, password semua `password123`): admin `081200000001`; customer `081200000011`, `081200000012`; mitra `081200000021`, `081200000022`, `081200000023`.
- **Midtrans:** akun Sandbox belum dibuat. ngrok sudah ada di laptop (URL berubah tiap restart). Paket `midtrans/midtrans-php` disetujui, dipasang di awal P4b.
- **Port 3306 sempat diblok Windows.** Fix di PowerShell admin: `net stop winnat`, `netsh int ipv4 add excludedportrange protocol=tcp startport=3306 numberofports=1`, `net start winnat`.

## Log sesi (terbaru di atas)

### 2026-11-11 · T0 housekeeping selesai

- AI: Claude · Fase: P4 (antara P4a dan T1)
- Selesai: paket dokumen di-commit dan di-push (`fa5f42f`, 12 file); APP_KEY baru dibuat; cek rahasia `base64:` di diff kosong; repo Private; `services` = 3; `dump-code.ps1` jalan (126 file, 243 KB). Bundel ChatGPT (3 file) sudah dibuat dan dikirim; **belum dikonfirmasi** apakah sudah diunggah ke Project ChatGPT dan 5 file lama (8 Nov) dihapus.
- Setengah jadi: T1 (tes catch-up) belum dimulai.
- Keputusan baru: Project ChatGPT gratis menampung 5 file, bukan 3 (bundel tetap 3 file).
- Bug diketahui: lihat "Sekarang".
- Berikutnya: 1) T1. 2) T2. 3) Flutter paralel di ChatGPT.

### 2026-11-11 · Update paket dokumen (tanpa kode)

- AI: Claude · Fase: P4 (antara P4a dan tes catch-up)
- Selesai: menulis ulang API_CONTRACT (v0.2, +§10.10, +§11 status implementasi), DECISIONS (D-22..D-24, Q-8/Q-9, CR-12..CR-15, jawaban dosen), PROGRESS, AI_RULES, PROMPTS, README, dan `PLAN_LANJUTAN.md` baru. Dirampingkan: `CLAUDE.md` dan `AGENTS.md` jadi pointer ke `AI_RULES.md` (satu salinan aturan); `scripts/bundle-chatgpt.ps1` (belum dijalankan di Windows) menggabung dokumen jadi 3 file untuk ChatGPT (`PROMPTS.md` §G). `PROMPTS.md` §0 berisi prompt siap tempel tanpa isian (T1, T2, Flutter); `scripts/dump-code.ps1` (juga belum dijalankan) menggabung kode backend jadi `KODE_BACKEND.txt` untuk lampiran.
- Setengah jadi: dokumen belum di-commit. Empat file tes catch-up (34 tes) masih hanya ada di chat lama.
- Keputusan baru: tidak ada keputusan baru, hanya mencatat yang sudah disepakati. Item bertanda USULAN tetap USULAN.
- Bug diketahui: lihat "Sekarang".
- Berikutnya: 1) T0 housekeeping. 2) T1 tes catch-up. 3) T2 P4c.

### 2026-11-10 · GitHub, push per tahap, dan APP_KEY bocor

- AI: Claude · Fase: P4
- Selesai: remote `NielTur/jasa-rumahan` terpasang, semua branch di-push. Aturan baru Git-5 (push tiap tahap hijau) dan Git-6 (perintah git per blok kecil, berhenti jika ada `error:`/`fatal:`).
- Insiden: `APP_KEY` asli terkirim di `backend/.env.example` dan sudah ter-push. Dikosongkan (`APP_KEY=`), di-cherry-pick ke `main` (`09a0859`) dan ke branch fitur (`adefd70`); diverifikasi kosong di `main` dan `origin/main`. **Belum terkonfirmasi:** user sudah menjalankan `php artisan key:generate`, dan repo Private. Riwayat git lama masih memuat key lama, jadi key lama harus dianggap bocor dan tidak dipakai lagi.
- Insiden kecil: `git checkout main` gagal karena dokumen belum di-commit, PowerShell lanjut menjalankan perintah berikutnya, branch fix terbentuk dari branch fitur. Dibereskan dengan fast-forward, commit dokumen `ee82fbd`, cherry-pick, hapus branch fix.
- Keputusan baru: push ke GitHub setelah tiap tahap hijau. Review ChatGPT ditunda sampai backend selesai, lalu dilakukan sekali di branch teratas.
- Berikutnya: 1) key:generate + cek Private. 2) Tes catch-up. 3) P4c.

### 2026-11-10 · P4a hijau + hasil konsul dosen

- AI: Claude · Fase: P4
- Selesai: P4a (`php artisan test`: 55 lulus, 239 assertion, commit `c04bcac`, branch `feat/order-customer-actions`, ditumpuk di `feat/mitra-orders`): cancel (`refund_pending` kalau sudah bayar), confirm-completion, GET payment (`payment: null` kalau belum ada). 17 tes `CustomerOrderActionTest`.
- Setengah jadi: tes catch-up B-13 untuk P1/P2; P4b Midtrans; P4c expiry + auto-complete; review ChatGPT.
- Keputusan baru (konsul dosen 10 Nov): Q-1 Laravel + MySQL boleh. Presentasi pertemuan 13. Luaran hanya Manual Book. Minimal 1 API eksternal (Midtrans), jadi `simulate-paid` bukan pengganti. Disarankan masuk GitHub.
- Bug diketahui: (kosong)
- Berikutnya: 1) Daftar Midtrans Sandbox + push GitHub. 2) P4b. 3) Skeleton Flutter paralel (ChatGPT).

### 2026-11-10 · P3b hijau, P3c progres mitra + simulate-paid

- AI: Claude · Fase: P3
- Selesai: P3b hijau (24 lulus, commit `52991be`, `feat/mitra-offers`). P3c hijau (38 lulus, commit `3c64ced`, `feat/mitra-orders`): GET `/mitra/orders` (+filter status koma), GET `/mitra/orders/{kode}`, POST depart/arrive/start/finish lewat `OrderStatusService` (B-4, depart wajib payment paid); `POST /dev/orders/{kode}/simulate-paid`; `MitraOrderService`, `DevPaymentService`; `MitraOrderTest` (14 tes) + trait `BuildsMitraFixtures`.
- Keputusan baru: CR-14 (rute simulate-paid selalu terdaftar, service membalas 404 di luar local). Kontrak §10.6-10.8 ditempel.
- Bug diketahui: (kosong)
- Catatan P4c: order `waiting_payment` yang customer-nya tidak pernah membuka pembayaran tidak punya baris `payments`. Batas D-06 dihitung dari waktu transisi `waiting_payment` di `order_status_histories` (D-24).
- Berikutnya: 1) P4a. 2) Review ChatGPT (ditunda). 3) P4b/P4c.

### 2026-11-10 · P3b offers accept/reject

- AI: Claude · Fase: P3
- Selesai: GET `/mitra/offers`, POST accept/reject; `MitraOfferService` (B-5: kunci order → mitra_profile → attempt, lalu re-check status/mitra_id/attempt/expiry/jadwal); `MitraOfferResource`; `MitraOfferTest` (12 tes). Branch `feat/mitra-offers`.
- Keputusan baru: offer menampilkan `expires_at`, tanpa `order_code` (D-12, CR-13). Accept membalas `OrderResource`.
- Berikutnya: P3c.

### 2026-11-10 · P3a mitra setup

- AI: Claude · Fase: P3
- Selesai: GET/PUT `/mitra/profile`, `/mitra/services`, `/mitra/availabilities`; `MitraSetupService`; `CurrentMitra`; `phpunit.xml` dipaksa ke DB `jasa_rumahan_test` (MySQL). `php artisan test`: 12 lulus (10 tes `MitraSetupTest` + 2 `ExampleTest`). Branch `feat/mitra-setup`, commit `f9bbefd`.
- Keputusan baru: body PUT availabilities dibungkus key `availabilities` (CR-12).
- Bug diketahui: `.phpunit.result.cache` sempat ke-commit; dibuang (`git rm --cached`), di-gitignore, commit di-amend.
- Berikutnya: P3b.

### 2026-11-09 · P1 + P2 selesai (backend)

- AI: Claude · Fase: P2 selesai, berikutnya P3
- Selesai (dites manual hijau, commit `97e25bc`, kini di `main` sebagai bagian `09a0859`):
  - P1: auth (register/login/logout/me), katalog publik, `PricingService` + `/orders/quote`.
  - P2a: `OrderStatusService` (satu pintu status), `SchedulingService` + `/slots`, `POST /orders` (guest/login, snapshot, harga server, anti-dobel), track (429 setelah 5 gagal), status-history, `customer/orders`, middleware role.
  - P2b: `MatchingService` (attempt sekuensial D-16), command `orders:process-expired-offers` + scheduler tiap menit, lazy expiry di endpoint baca. Attempt `offered` pada order yang sudah tidak mencari mitra ditutup `expired`.
  - CR-01 s/d CR-11 tercatat di DECISIONS §5.
- Setengah jadi: tes otomatis (B-13) belum ada untuk P1/P2 (dicatat sebagai tes catch-up).
- Bug diketahui: (kosong)
- Berikutnya: P3 backend, daftar Midtrans Sandbox, tanya dosen Q-1.

### 2026-11-09 · CR-01/02 + log perubahan

- AI: Claude · Fase: P1 selesai, masuk P2
- Selesai: DECISIONS §5 (CR-01..CR-08), migration kolom pembatalan `orders` + payload/refund `payments`, model Order/Payment diperbarui, kontrak v0.2 (nama field, contoh JSON §10).
- Keputusan baru: CR-01..CR-08 disetujui.

### 2026-11-09 · P1 Auth + Katalog + Quote (backend)

- AI: Claude · Fase: P1
- Selesai: auth (register customer/mitra, login, logout, me; error 401/403/422 dicoba), katalog publik, `PricingService` + `/orders/quote` (dicoba: 270000, 422 kombinasi tanpa harga, 422 add-on jasa lain). 10 route api.
- Keputusan baru: register mengembalikan token; admin ditolak login API; kombinasi jasa+ukuran tanpa harga = 422; error field bersarang berformat bertitik.

### 2026-11-08 · Backend P0 (migration + model)

- AI: Claude · Fase: P0
- Selesai: 16 migration (`migrate:fresh` hijau), 14 model + relasi, `config/marketplace.php`.
- Keputusan baru: Laravel 10 dipasang pakai `--no-security-blocking` (EOL, demo lokal saja; CR-08).

### 2026-11-07 · Setup dokumen

- AI: Claude · Fase: pra-P0
- Selesai: menyusun AI_RULES, PROMPTS, API_CONTRACT (draft v0.1), DECISIONS, README.
- Catatan: semua D-xx masih USULAN; kontrak belum dibekukan.

### 2026-11-11 · Flutter Foundation – Shared Core

- AI: GPT-6 · Fase: P1
- Selesai:
  - `packages/core/lib/model/user_info.dart` — penyimpanan token autentikasi dan data guest menggunakan SharedPreferences.
  - `packages/core/lib/service/api_exception.dart` — pemetaan HTTP error, timeout, connection error, dan validasi API.
  - `packages/core/lib/service/api_client.dart` — Dio singleton, konfigurasi base URL, header, dan interceptor token.
  - `packages/core/lib/helpers/app_formatters.dart` — formatter mata uang Rupiah, tanggal Indonesia, dan waktu WIB.
  - `packages/core/lib/core.dart` — export komponen shared core.
  - `packages/core/test/api_exception_test.dart` — 6 unit test berhasil.
  - `packages/core/test/app_formatters_test.dart` — 4 unit test berhasil.
  - `dart format lib test` — berhasil, 7 file diperiksa.
  - `flutter test` — 10 test passed.
  - `flutter analyze` — No issues found.
- Setengah jadi:
  - Belum ada pada pekerjaan Shared Core yang diperiksa dalam sesi ini.
- Keputusan baru:
  - Pengujian bawaan `core_test.dart` dihapus karena masih menguji class Calculator yang tidak digunakan.
  - Pengujian Shared Core menggunakan unit test yang relevan dengan implementasi aktual.
- Bug diketahui:
  - Tidak ada bug yang terdeteksi pada hasil pengujian dan analisis terakhir.
- Berikutnya:
  1. Periksa konfigurasi dan integrasi package `core` dengan Customer App dan Mitra App.
  2. Siapkan konfigurasi awal Customer App.
  3. Siapkan konfigurasi awal Mitra App.

### 2026-11-11 · Flutter Foundation – Customer dan Mitra App

- AI: GPT-6 · Fase: P1
- Selesai:
  - `apps/customer_app` — scaffold Flutter dan konfigurasi package `core`.
  - `apps/mitra_app` — scaffold Flutter dan konfigurasi package `core`.
  - `packages/core` — implementasi Shared Core selesai.
  - `flutter pub get` — berhasil pada kedua aplikasi.
  - `flutter test` — Customer App 1 test passed, Mitra App 1 test passed.
  - `flutter analyze` — kedua aplikasi tidak memiliki masalah analisis.
  - `git diff --cached --check` — tidak menemukan masalah whitespace.
- Setengah jadi:
  - Belum ada pada pekerjaan Flutter Foundation.
- Keputusan baru:
  - Customer App dan Mitra App disimpan sebagai dua aplikasi Flutter terpisah.
  - Package `core` digunakan bersama untuk kebutuhan API, autentikasi, penyimpanan, dan formatter.
  - Seluruh platform bawaan Flutter dipertahankan.
- Bug diketahui:
  - Belum ada bug yang terdeteksi pada pengujian dasar.
- Berikutnya:
  1. Mulai fondasi UI Customer App.
  2. Implementasikan alur Customer berdasarkan API Contract.
  3. Mulai fondasi UI Mitra App.

### 2026-11-12 · T2 P4c expiry bayar + auto-complete selesai

- AI: Claude · Fase: P4 (P4c selesai)
- Selesai: `OrderExpiryService` (expiry bayar D-06/D-24, auto-complete D-07, lazy expiry B-15), command `payments:expire-pending` dan `orders:auto-complete`, ketiga command terdaftar di `Kernel.php`. Lazy expiry dipanggil di show, track, status-history, payment, customer/orders, dan /mitra/orders\*. `OrderExpiryTest` 12 tes. `php artisan test`: 101 lulus, 720 assertion. Branch `feat/payment-expiry`, commit kode `8c36a47`, dokumen `320afe3` dan `38d2dda`, sudah di-push. API_CONTRACT (§9, §10.10, §11) dan DECISIONS sudah disinkronkan.
- Setengah jadi: uji manual PAYMENT_EXPIRY_MINUTES=1 lewat Postman belum dilakukan. Review ChatGPT masih ditunda sampai backend selesai.
- Keputusan baru: tidak ada. D-22, D-23, D-24 dipakai sebagai asumsi kerja, masih USULAN.
- Bug diketahui: tidak ada. Ditemukan: jasa di kategori nonaktif masih lolos di detail, quote, slots, dan order (ditangani CR-16 di T3-0).
- Berikutnya: 1) T3 P5 Admin Blade. 2) T4 P4b Midtrans (butuh akun Sandbox). 3) Flutter paralel di ChatGPT.

### 2026-11-11 · T1 tes catch-up B-13 selesai

- AI: Claude · Fase: P4 (antara P4a dan T2)
- Selesai: 4 file tes baru (Auth 7, Catalog+Quote 8, SlotAndCreateOrder 10, TrackAndLifecycle 9 = 34 tes). `php artisan test`: 89 lulus, 655 assertion. Branch `feat/test-catchup-b13`, commit tes `eb00a17`, dokumen `2f7dc6c`, sudah di-push.
- Setengah jadi: bundel ChatGPT belum dikonfirmasi diunggah. Review ChatGPT ditunda sampai backend selesai.
- Keputusan baru: tidak ada.
- Bug diketahui: dua dugaan (harga string lolos, quantity negatif) TIDAK terbukti oleh tes.
- Berikutnya: 1) T2 P4c. 2) T3 P5 Admin Blade. 3) Flutter paralel di ChatGPT.

<!-- Salin blok ini untuk sesi berikutnya, tempel DI ATAS entri terbaru:
### YYYY-MM-DD · judul singkat
- AI: <nama> · Fase: <P?>
- Selesai: <file + status>
- Setengah jadi: <file + kondisinya>
- Keputusan baru: <ID + isi>
- Bug diketahui: <gejala + file>
- Berikutnya: <maks 3>
-->
