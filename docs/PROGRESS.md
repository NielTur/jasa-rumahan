# PROGRESS: Catatan Serah-Terima Antar Sesi

> Ini satu-satunya "ingatan" proyek untuk AI. Ringkas, faktual, **terbaru di atas**.
> Tempel bagian **Sekarang** ke prompt tugas (`PROMPTS.md` §C), atau pakai prompt lanjut sesi (`PROMPTS.md` §E.7). Tambahkan entri baru di paling atas **Log** setiap akhir sesi (`PROMPTS.md` §E.4).
> Rencana kerja berikutnya yang rinci ada di [`PLAN_LANJUTAN.md`](PLAN_LANJUTAN.md).

## Sekarang

| Item | Isi |
|---|---|
| Fase aktif | **P4** (backend). Urutan sisa: tes catch-up B-13 → P4c → P5 → P4b → P6 → Flutter → Manual Book |
| Terakhir diperbarui | 11 Nov 2026 oleh Claude |
| Selesai (backend, dites hijau) | P1 auth + katalog + quote · P2 slots + buat order + matching · P3a-c sisi mitra + simulate-paid · P4a cancel + confirm-completion + GET payment. `php artisan test`: **55 lulus, 239 assertion** |
| Setengah jadi | 1) **34 tes catch-up B-13** (P1/P2) sudah ditulis di chat sebelumnya tapi **belum disimpan dan belum dijalankan**; kalau file-nya hilang, minta tulis ulang (T1). 2) Dokumen ini belum di-commit. 3) Review ChatGPT untuk P3a-P4a belum (ditunda sampai backend selesai; cukup sekali di branch teratas). 4) Belum ada kode Flutter |
| Belum terkonfirmasi | a) `php artisan key:generate` setelah APP_KEY lama sempat bocor di `.env.example` (sudah dikosongkan dan di-push). b) Repo GitHub berstatus **Private**. c) Hasil `SELECT COUNT(*) FROM services;` (harapan: 3). d) `scripts/bundle-chatgpt.ps1` dan `scripts/dump-code.ps1` belum pernah dijalankan di Windows |
| Bug yang diketahui | Dugaan, belum dibuktikan tes: harga bertipe string lolos cast; `quantity` negatif belum ditolak. Cek lewat tes catch-up |
| Keputusan USULAN yang menunggu | D-22, D-23, D-24 (DECISIONS §1); Q-2, Q-4, Q-5, Q-6, Q-8, Q-9 terbuka |
| 3 langkah berikutnya | 1) T0 housekeeping: commit dokumen, key:generate, cek Private, simpan dan jalankan tes catch-up (target 89 tes). 2) T2 P4c: `payments:expire-pending`, `orders:auto-complete`, lazy expiry. 3) T3 P5 admin Blade |
| Cara menjalankan | `cd backend` → `php artisan migrate:fresh --seed` → `php artisan serve --host=0.0.0.0 --port=8000`; terminal lain `php artisan schedule:work`. Tes: `php artisan test` (MySQL DB `jasa_rumahan_test` harus ada, lihat README) |

## Versi terpasang (dari output perintah)

| Alat | Versi |
|---|---|
| PHP | 8.3.32 |
| Laravel | 10.50.4 |
| Laravel Sanctum | 3.3.3 |
| Composer | 2.11.1 |
| MySQL (Laragon) | 8.0.30 |
| PHPUnit | ^10 (dari `composer.json`) |
| HeidiSQL | 12.1.0.6537 |
| Flutter / Dart | 3.41.6 / 3.11.4 |
| Dio, shared_preferences, intl, webview_flutter | belum dipasang (Flutter belum dimulai) |

Lingkungan: Windows, Laragon, VS Code, PowerShell.

## Repo dan branch

GitHub: https://github.com/NielTur/jasa-rumahan (target: Private). Branch ditumpuk (stacked): tiap branch dibuat dari branch sebelumnya, jadi review dan merge dilakukan **sekali di branch teratas**.

| Branch | Commit | Isi | Tes setelah branch ini |
|---|---|---|---|
| `main` | `09a0859` | P1 + P2 (sebelumnya `97e25bc`) + perbaikan `.env.example` (APP_KEY dikosongkan) | belum ada tes otomatis |
| `feat/mitra-setup` | `f9bbefd` | P3a profil, jasa, jadwal mitra + `phpunit.xml` ke DB test | 12 |
| `feat/mitra-offers` | `52991be` | P3b offers accept/reject (B-5) | 24 |
| `feat/mitra-orders` | `3c64ced` | P3c order mitra, progres, simulate-paid | 38 |
| `feat/order-customer-actions` | `c04bcac` (P4a), lalu `adefd70` (perbaikan `.env.example`, fast-forward) dan `ee82fbd` (dokumen) | P4a cancel, confirm-completion, GET payment | **55** |

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

<!-- Salin blok ini untuk sesi berikutnya, tempel DI ATAS entri terbaru:
### YYYY-MM-DD · judul singkat
- AI: <nama> · Fase: <P?>
- Selesai: <file + status>
- Setengah jadi: <file + kondisinya>
- Keputusan baru: <ID + isi>
- Bug diketahui: <gejala + file>
- Berikutnya: <maks 3>
-->
