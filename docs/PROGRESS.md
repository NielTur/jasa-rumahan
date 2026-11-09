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

<!-- Salin blok ini untuk sesi berikutnya:
### YYYY-MM-DD · judul singkat
- AI: <nama> · Fase: <P?>
- Selesai: <file + status>
- Setengah jadi: <file + kondisinya>
- Keputusan baru: <ID + isi>
- Bug diketahui: <gejala + file>
- Berikutnya: <maks 3>
-->
