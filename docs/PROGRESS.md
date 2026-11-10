# PROGRESS: Catatan Serah-Terima Antar Sesi

> Ini satu-satunya "ingatan" proyek untuk AI. Ringkas, faktual, terbaru di atas.
> Tempel bagian **Sekarang** ke prompt tugas (`PROMPTS.md` §C). Tambahkan entri baru di **Log** setiap akhir sesi (`PROMPTS.md` §E.4).

## Sekarang

| Item                    | Isi                                                                                                                                                                                              |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Fase aktif              | P0 · Fondasi                                                                                                                                                                                     |
| Terakhir diperbarui     | 7 Nov 2026 oleh `<manusia / Claude / ChatGPT>`                                                                                                                                                   |
| Selesai                 | Dokumen desain + paket dokumen AI (README, AI_RULES, PROMPTS, API_CONTRACT, DECISIONS)                                                                                                           |
| Setengah jadi           | (kosong)                                                                                                                                                                                         |
| Bug yang diketahui      | (kosong)                                                                                                                                                                                         |
| CHANGE REQUEST tertunda | 1)G-01 (kolom cancelled_at, cancel_reason, cancelled_by, completed_at di orders) dan G-02 (gateway_payload, refund_amount, refunded_at di payments): diputuskan nanti, paling lambat sebelum P3. |
| 3 langkah berikutnya    | 1) Jawab Q-1..Q-7 di DECISIONS. 2) Ubah status keputusan D-xx jadi DISEPAKATI. 3) Jalankan P0                                                                                                    |
| Cara menjalankan        | (isi setelah P0: perintah backend dan Flutter yang benar-benar berjalan)                                                                                                                         |

## Versi terpasang (isi dari output perintah, jangan menebak)

| Alat                                                            | Versi |
| --------------------------------------------------------------- | ----- |
| PHP (`php -v`)                                                  |       |
| Laravel (`composer show laravel/framework`)                     |       |
| MySQL                                                           |       |
| Flutter / Dart (`flutter --version`)                            |       |
| Dio, shared_preferences, intl, webview_flutter (`pubspec.lock`) |       |

## Log sesi (terbaru di atas)

### 2026-11-07 · Setup dokumen

- AI: Claude · Fase: pra-P0
- Selesai: menyusun AI_RULES, PROMPTS, API_CONTRACT (draft v0.1), DECISIONS, README.
- Catatan: semua D-xx masih USULAN; kontrak belum dibekukan.

### 2026-11-08 · Backend P0 (migration + model)

- AI: Claude · Fase: P0
- Selesai: 16 migration (migrate:fresh hijau), 14 model + relasi, config/marketplace.php.
- Setengah jadi: seeder demo (MasterDataSeeder, DemoAccountSeeder) kode ada, belum dijalankan.
- Keputusan baru: Laravel 10 dipasang pakai --no-security-blocking (EOL, demo lokal saja).
- Bug diketahui: (kosong)
- Berikutnya: 1) Seeder + tes tinker. 2) Contoh JSON API_CONTRACT (Auth, Katalog, Quote). 3) Skeleton Flutter.

### 2026-11-09 · P1 Auth + Katalog + Quote (backend selesai)

- AI: Claude · Fase: P1
- Selesai: auth (register customer/mitra, login, logout, me; error 401/403/422 dites), katalog publik, PricingService + /orders/quote (dites: 270000, 422 kombinasi tanpa harga, 422 add-on jasa lain). 10 route api.
- Setengah jadi: tes 429 + logout (opsional), contoh JSON kontrak (ditempel di §10), skeleton Flutter.
- Keputusan baru: register mengembalikan token; admin ditolak login API; kombinasi jasa+ukuran tanpa harga = 422; error field bersarang berformat bertitik.
- CHANGE REQUEST tertunda: G-01, G-02; penyeragaman nama field (name vs service_name/size_name).
- Berikutnya: 1) Skeleton Flutter (packages/core, ApiClient, UserInfo). 2) P2: slots + POST /orders. 3) Tanya dosen Q-1.

### 2026-11-09 · CR-01/02 + log perubahan

- AI: Claude · Fase: P1 selesai, masuk P2
- Selesai: DECISIONS §5 (CR-01..CR-08), migration kolom pembatalan orders + payload/refund payments, model Order/Payment diperbarui, kontrak v0.2 (nama field, contoh JSON §10).
- Setengah jadi: tes 429 + logout (opsional); tes "ubah harga di DB" belum dijalankan.
- Keputusan baru: CR-01..CR-08 disetujui.
- Berikutnya: 1) P2a: OrderStatusService + slots + POST /orders. 2) Daftar Midtrans Sandbox. 3) Tanya dosen Q-1.

### 2026-11-09 · P2b matching

- AI: Claude · Fase: P2
- Selesai: P2a hijau semua (slot, order guest/login, anti-dobel, track + 429, riwayat). P2b: MatchingService (attempt sekuensial, D-16), command orders:process-expired-offers + scheduler tiap menit, lazy expiry di endpoint baca. Hasil tes: <isi>.
- Setengah jadi: accept/reject mitra (P3) belum ada, jadi order baru berhenti di offer. Reject nanti memanggil MatchingService::advance().
- Keputusan baru: tidak ada CR baru. D-04, D-05, D-16 sudah terpakai di kode. Attempt `offered` pada order yang sudah tidak mencari mitra ditutup `expired`.
- Bug diketahui: (kosong)
- Berikutnya: 1) P3: profil mitra, pilih jasa, jadwal, offers accept/reject (B-5 lockForUpdate + tes bersamaan), progres status, simulate-paid. 2) Daftar Midtrans Sandbox. 3) Tanya dosen Q-1.

### 2026-11-09 · P1 + P2 selesai (backend)

- AI: Claude · Fase: P2 selesai, berikutnya P3
- Selesai (semua dites hijau, commit 97e25bc):
  - P1: auth (register/login/logout/me), katalog publik, PricingService + /orders/quote.
  - P2a: OrderStatusService (satu pintu status), SchedulingService + /slots, POST /orders (guest/login, snapshot, harga server, anti-dobel), track (429 setelah 5 gagal), status-history, customer/orders, middleware role.
  - P2b: MatchingService (attempt sekuensial D-16), command orders:process-expired-offers + scheduler tiap menit, lazy expiry di endpoint baca.
  - CR-01 s/d CR-11 tercatat di DECISIONS §5 (CR-01/02 = migration kolom cancel + payload/refund).
- Setengah jadi: kontrak v0.2, belum dibekukan v1.0. Skeleton Flutter belum ada. Tes otomatis (B-13) belum ada sama sekali.
- Keputusan baru: tidak ada.
- Bug diketahui: (kosong)
- Berikutnya: 1) P3 backend: profil mitra, pilih jasa, jadwal, offers accept/reject (B-5 lockForUpdate + tes accept bersamaan), progres status, dev/simulate-paid. 2) Daftar Midtrans Sandbox. 3) Tanya dosen Q-1.
- Cara menjalankan: cd backend; php artisan migrate:fresh --seed; php artisan serve --host=0.0.0.0 --port=8000; (terminal lain) php artisan schedule:work
- Versi: PHP 8.3.32, Laravel 10.50.4, Sanctum 3.3.3, Composer 2.11.1, MySQL 8.0.30, Flutter 3.41.6 / Dart 3.11.4
- Port 3306 sempat diblok Windows. Fix di PowerShell admin: net stop winnat, netsh int ipv4 add excludedportrange protocol=tcp startport=3306 numberofports=1, net start winnat.

### 2026-11-10 · P3a mitra setup (backend, hijau)

- AI: Claude · Fase: P3
- Selesai: GET/PUT /mitra/profile, /mitra/services, /mitra/availabilities; MitraSetupService; CurrentMitra; phpunit.xml dipaksa ke DB jasa_rumahan_test (MySQL). php artisan test: 12 lulus (10 tes MitraSetupTest). Branch feat/mitra-setup, commit <hash baru setelah amend>.
- Setengah jadi: P3b offers accept/reject + tes accept bersamaan; P3c order mitra + simulate-paid. Review ChatGPT untuk P3a belum.
- Keputusan baru: body PUT availabilities dibungkus key `availabilities` (kontrak §10.5 belum ditempel). CR-09..11 sudah di DECISIONS §5.
- Bug diketahui: (kosong). .phpunit.result.cache sempat ke-commit, sudah dibuang dan di-gitignore.
- Berikutnya: 1) P3b MitraOfferService (B-5) + tes. 2) P3c. 3) Tanya dosen Q-1.

<!-- Salin blok ini untuk sesi berikutnya:
### YYYY-MM-DD · judul singkat
- AI: <nama> · Fase: <P?>
- Selesai: <file + status>
- Setengah jadi: <file + kondisinya>
- Keputusan baru: <ID + isi>
- Bug diketahui: <gejala + file>
- Berikutnya: <maks 3>
-->
