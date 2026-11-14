# PROGRESS: Catatan Serah-Terima Antar Sesi

> Ini satu-satunya "ingatan" proyek untuk AI. Ringkas, faktual, terbaru di atas.
> Tempel bagian **Sekarang** ke prompt tugas (`PROMPTS.md` §C). Tambahkan entri baru di **Log** setiap akhir sesi (`PROMPTS.md` §E.4).

## Sekarang

| Item | Isi |
|---|---|
| Fase aktif | Backend recovery / stabilisasi sebelum demo |
| Terakhir diperbarui | 14 Nov 2026 oleh ChatGPT berdasarkan output test yang dikirim pemilik |
| Sumber versi | ZIP = baru; GitHub = lama. Jangan restore dari GitHub lama tanpa instruksi |
| AI pengerjaan | ChatGPT mengambil alih backend karena Claude kehabisan credit bulanan |
| Scope | Backend Laravel dan dokumentasi backend. Flutter tidak boleh disentuh tanpa instruksi eksplisit |
| Selesai sebelumnya (berdasarkan catatan) | P0 fondasi; P1 auth/katalog/quote; P2 order/matching; P3 mitra/order lifecycle; P4 pembayaran/expiry; P5 Admin Blade CRUD. Riwayat lengkap dan test historis tetap di log sesi |
| Status test terkini | `php artisan test`: **35 failed, 100 passed**, 135 tests, 768 assertions, 73.74 s (output pemilik, 14 Nov 2026). Suite belum hijau |
| Bug diketahui | (1) Admin middleware alias `admin` tidak terdaftar/ter-resolve (`Target class [admin] does not exist`); (2) authenticated Admin login redirect `/home` bukan `/admin/dashboard`; (3) kategori nonaktif masih bisa terakses pada katalog/quote; (4) endpoint mitra services dan mitra setup jasa HTTP 500; (5) guest track tidak lazy-expire order/payment yang lewat batas; (6) auto-complete belum tercermin pada customer history/status history |
| Artefak ZIP | File Python disebut pemilik sebagai eksperimen/test Qwenn; fungsi belum diketahui, bukan fitur yang diasumsikan |
| Berikutnya | 1) Inspeksi dan perbaiki Admin middleware/redirect. 2) Perbaiki katalog nonaktif dan endpoint mitra jasa. 3) Perbaiki lazy expiry/auto-complete, kemudian jalankan suite lengkap |
| Cara menjalankan | Dari `C:\laragon\www\jasa-rumahan\backend`: `php artisan test` |

## Versi terpasang (isi dari output perintah, jangan menebak)

| Alat                                                            | Versi |
| --------------------------------------------------------------- | ----- |
| PHP (`php -v`)                                                  |       |
| Laravel (`composer show laravel/framework`)                     |       |
| MySQL                                                           |       |
| Flutter / Dart (`flutter --version`)                            |       |
| Dio, shared_preferences, intl, webview_flutter (`pubspec.lock`) |       |

## Log sesi (terbaru di atas)

### 2026-11-14 · Alih pengerjaan ChatGPT dan baseline test recovery

- AI: ChatGPT · Fase: Backend recovery/stabilisasi. Claude tidak tersedia karena credit bulanan habis; pemilik meminta pengerjaan full dengan ChatGPT.
- Sumber versi yang ditegaskan pemilik: ZIP = baru, GitHub = lama. Jangan restore dari GitHub lama. Flutter dilarang disentuh.
- Verifikasi yang dijalankan pemilik: `php artisan test` dari `backend`. Output: 35 failed, 100 passed (135 tests, 768 assertions, 73.74 s). Warning XML schema deprecated juga muncul; warning ini terpisah dari test failures.
- Kegagalan Admin: `Target class [admin] does not exist` pada banyak test; redirect login authenticated Admin aktual `/home`, expected `/admin/dashboard`.
- Kegagalan katalog/mitra: jasa pada kategori nonaktif tidak ditolak (200 dibanding 404/422); endpoint `/api/mitra/services` dan update pilihan jasa menghasilkan 500.
- Kegagalan expiry: guest tracking mengembalikan `waiting_payment` alih-alih `cancelled`; riwayat customer/status history masih `mitra_completed` alih-alih `completed`.
- Belum dilakukan: perubahan kode backend, perbaikan test, atau push GitHub. Angka test di atas baseline dari output pemilik, bukan hasil ChatGPT menjalankan suite.
- Berikutnya: 1) inspeksi route/middleware Admin dan redirect. 2) telusuri exception endpoint mitra services + filter category active. 3) telusuri expiry lazy processing. Jalankan test targeted dan full sesudah perubahan.

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

<!-- Salin blok ini untuk sesi berikutnya:
### YYYY-MM-DD · judul singkat
- AI: <nama> · Fase: <P?>
- Selesai: <file + status>
- Setengah jadi: <file + kondisinya>
- Keputusan baru: <ID + isi>
- Bug diketahui: <gejala + file>
- Berikutnya: <maks 3>
-->
