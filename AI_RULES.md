# AI_RULES.md

Aturan kerja untuk semua AI (Claude, ChatGPT, dll.) yang membantu proyek **Marketplace Jasa Rumahan**.
Setiap aturan punya ID supaya gampang menegur AI: *"kamu melanggar B-4"*.

## 0. Cara pakai (untuk pemilik proyek)

- Claude Code / agent berbasis file: salin file ini jadi `CLAUDE.md` dan `AGENTS.md` di root repo.
- Chat biasa: unggah ke Project (Claude Project dan ChatGPT Project), lalu tempel Prompt Kickoff dari `docs/PROMPTS.md` §B di sesi baru.
- Tiap sesi baru, lampirkan: `AI_RULES.md`, `docs/PROGRESS.md`, bagian `docs/API_CONTRACT.md` yang relevan, dan file yang akan diubah. Jangan tempel seluruh dokumen desain kecuali diminta.
- AI tidak punya ingatan antar chat. Satu-satunya "ingatan" proyek adalah `docs/PROGRESS.md`. Perbarui di akhir setiap sesi.

## 1. Proyek dalam 6 baris

1. Marketplace jasa rumahan (Home Cleaning, Lawn Mowing, Tree Trimming, dll). Role: Customer (boleh guest), Mitra, Admin.
2. Komponen: `customer_app` dan `mitra_app` (Flutter), `backend` (Laravel REST API + Admin Blade), MySQL 16 tabel, Dio, Midtrans (sandbox).
3. Aturan inti: 1 pesanan = 1 jasa utama = 1 mitra. Add-on bagian dari pesanan. Harga dihitung ulang di server. Status pesanan terpisah dari status pembayaran.
4. Ini tugas akhir Mobile Programming II (BSI), dikerjakan mahasiswa dalam ±4 minggu.
5. Dinilai lewat demo, kode, dan dokumen. Kode harus **bisa dipahami dan dijelaskan mahasiswa**; itu lebih penting daripada canggih.
6. Fokus MVP. Fitur backlog dilarang kecuali diminta eksplisit (G-5).

## 2. Sumber kebenaran (urutan prioritas)

1. `docs/API_CONTRACT.md`
2. `docs/DECISIONS.md`
3. `docs/Dokumentasi_Sistem_Marketplace_Jasa_Rumahan.md` (dokumen desain kelompok)
4. `docs/PROGRESS.md` (kondisi terkini)

Jika dua sumber bertentangan: **berhenti dan laporkan**. Jangan memilih diam-diam.

## 3. Pembagian kepemilikan (default, boleh ditukar)

| Area | Penulis | Reviewer |
|---|---|---|
| `backend/`, `docs/API_CONTRACT.md` | Claude | ChatGPT |
| `packages/core/`, `apps/*/lib/ui`, `apps/*/lib/service` | ChatGPT | Claude |

- AI hanya mengubah file di area miliknya. Butuh perubahan di area lain atau di kontrak? Tulis **CHANGE REQUEST** (§9), jangan mengedit.
- Kontrak hanya berubah setelah disetujui pemilik proyek (manusia).

## 4. Aturan umum (G)

- **G-1 Preflight.** Sebelum kode, tulis 3 baris: *Tugas* / *File yang disentuh* / *Asumsi & potensi konflik dengan kontrak*.
- **G-2 Ambigu.** Tanya maksimal 1 pertanyaan. Kalau tidak blocking, pakai default dari `DECISIONS.md` dan tulis asumsinya.
- **G-3 Scope.** Kerjakan hanya yang diminta. Tidak ada refactor di luar scope. Dependency baru butuh izin (sebut nama paket, alasan, dan alternatif tanpa paket).
- **G-4 Tidak mengarang.** Endpoint, tabel, kolom, dan status hanya dari kontrak atau dokumen desain. Butuh yang baru → CHANGE REQUEST.
- **G-5 Backlog dilarang** (dokumen desain §16.2): COD, komplain, rating, verifikasi mitra lengkap, saldo/pencairan, push notification, live chat, pembatalan kompleks, GPS tracking, voucher, loyalty.
- **G-6 Format copy-paste.** Setiap blok kode diawali path file. File ≤ 200 baris ditulis utuh. Lebih panjang: tulis per bagian dengan lokasi jelas dan 3 baris konteks. Dilarang `// ... kode lainnya tetap`.
- **G-7 Jujur soal verifikasi.** Kode yang tidak dijalankan harus ditulis "belum dijalankan". Dilarang bilang "sudah dites" tanpa bukti output.
- **G-8 Versi.** Jangan menebak versi paket/API. Baca `composer.json` / `pubspec.yaml`. Kalau tidak tahu, minta output `php -v`, `composer show laravel/framework`, `flutter --version`. Pakai API yang tidak deprecated (contoh: Dio `DioException`, bukan `DioError`; `ElevatedButton.styleFrom(backgroundColor:)`, bukan `primary:`). Kode di modul kuliah sebagian memakai API lama.
- **G-9 Rahasia.** Jangan pernah menulis kunci Midtrans, `APP_KEY`, atau password asli. Pakai `.env` dan `.env.example`.
- **G-10 Postflight.** Akhiri setiap jawaban dengan: (a) langkah menjalankan/menguji, (b) yang belum terverifikasi, (c) baris siap-tempel untuk `docs/PROGRESS.md`, (d) **Catatan Presentasi** 3-5 baris.
- **G-11 Bisa dijelaskan.** Nama jelas, fungsi pendek, komentar Bahasa Indonesia pada logika bisnis penting (bukan tiap baris). Hindari abstraksi yang tidak perlu.
- **G-12 Berhenti dan lapor** jika: dokumen saling bertentangan, perlu mengubah skema DB yang sudah disepakati, ada risiko keamanan, atau permintaan keluar dari MVP.

## 5. Backend Laravel (B)

- **B-1 Struktur.** Controller tipis. Logika di `app/Services/*`. Validasi di FormRequest. Output lewat API Resource. Blade admin memakai Service yang sama (tidak memanggil API sendiri lewat HTTP).
- **B-2 Skema.** Ikuti dokumen desain §13 persis (16 tabel, tipe, FK, UNIQUE, indeks §15.14). Setelah Fase P0 selesai, jangan edit migration lama; perubahan = migration baru + CHANGE REQUEST.
- **B-3 Uang & harga.** DB `DECIMAL(12,2)`; API dan Midtrans memakai **integer rupiah**. Hitung ulang harga di server dari `service_prices` dan `service_addons`; abaikan total dari Flutter. Add-on harus milik jasa yang dipilih, dan semua master harus `is_active`.
- **B-4 Satu pintu status.** Status pesanan hanya berubah lewat `OrderStatusService::transition(order, to, actor, note)`, yang memvalidasi transisi (tabel di kontrak §6) dan menulis `order_status_histories`. Dilarang `$order->update(['order_status' => ...])` di tempat lain.
- **B-5 Accept aman.** Penetapan mitra dalam `DB::transaction` dengan `lockForUpdate` (order dan mitra_profile), lalu cek ulang: status masih `searching_mitra`, `mitra_id` masih NULL, attempt masih `offered` dan belum kedaluwarsa, jadwal tidak bentrok.
- **B-6 Matching.** Sekuensial: satu mitra per attempt, tiap attempt dicatat di `order_mitra_attempts`. Timeout dan batas percobaan dari `config/marketplace.php` (.env), bukan angka literal.
- **B-7 Midtrans.**
  - `gateway_order_id` unik per percobaan bayar (`{order_code}-{n}`).
  - Verifikasi `signature_key` = SHA512(`order_id` + `status_code` + `gross_amount` + ServerKey), memakai string persis seperti diterima.
  - Idempotent: notifikasi berulang atau out-of-order (misal `pending` datang setelah `settlement`) tidak boleh menurunkan status.
  - Cocokkan `gross_amount` dengan `payments.amount`.
  - Balas HTTP 200 untuk notifikasi valid; tolak hanya jika signature salah.
  - Simpan payload mentah.
  - Redirect dari WebView/Flutter **bukan** bukti bayar.
  - Sediakan fallback cek status lewat Midtrans Status API.
- **B-8 Otorisasi.** Per role lewat middleware/policy: customer hanya datanya, mitra hanya offer dan order miliknya, admin lewat guard `web`. Telepon dan alamat lengkap customer tidak dibuka ke mitra sebelum accept (DECISIONS D-12).
- **B-9 Guest.** Tracking dan aksi guest memeriksa `order_code` + `phone`, dengan throttle. Jika tidak cocok, balas 404 generik. `order_code` acak (bukan auto-increment).
- **B-10 Waktu.** `APP_TIMEZONE=Asia/Jakarta`. API mengirim ISO 8601 dengan offset `+07:00`. `day_of_week` mengikuti Carbon: 0 = Minggu … 6 = Sabtu.
- **B-11 Jangan tertukar ID.** `mitra_id` selalu `mitra_profiles.id`, bukan `users.id`. Response mitra menyertakan `user_id` dan `mitra_id` dengan nama jelas.
- **B-12 Jangan hapus master.** Data master yang pernah dipakai transaksi dinonaktifkan (`is_active = false`), bukan dihapus. FK transaksi tanpa cascade delete.
- **B-13 Tes minimal wajib** (feature/unit test): kalkulasi harga; bentrok jadwal; transisi status valid dan invalid; dua mitra accept bersamaan; webhook (signature salah, duplikat, out-of-order, amount beda); guest tracking tidak cocok.
- **B-14 Seeder demo.** Admin, 2 customer, 3 mitra approved, kategori/jasa/ukuran/harga/add-on, supaya demo tidak bergantung input manual.
- **B-15 Expiry ganda.** Kedaluwarsa offer dan pembayaran diproses oleh scheduler (`schedule:work`) **dan** secara lazy saat endpoint baca dipanggil, sehingga demo tetap benar tanpa scheduler.

## 6. Flutter (F)

- **F-1 Konvensi modul kuliah:** `lib/ui`, `lib/model`, `lib/service`, `lib/helpers`; file `snake_case`, kelas `PascalCase`. Di monorepo, `model` dan `helpers` bersama ada di `packages/core`; `ui` dan `service` ada di tiap app.
- **F-2 State.** `StatefulWidget` + `FutureBuilder`/`StreamBuilder` + `setState` (gaya modul). Paket state management lain hanya dengan izin.
- **F-3 Alur panggilan.** `ui → service → ApiClient → backend`. UI tidak memanggil Dio langsung. Service mengembalikan model bertipe, bukan `Map` mentah.
- **F-4 ApiClient.** Satu instance Dio. Interceptor menambahkan Bearer token dari `UserInfo` (shared_preferences). `baseUrl` dari `--dart-define=API_BASE_URL`. Tangani `DioException` menjadi pesan Indonesia yang ramah (timeout, 401, 422 per field, 5xx).
- **F-5 Tiga state.** Setiap layar data punya loading, kosong, dan error (+ tombol coba lagi). Tombol submit dinonaktifkan saat proses berjalan (cegah double submit, terutama buat pesanan dan bayar).
- **F-6 Context.** Setelah `await`, cek `mounted` sebelum memakai `BuildContext`.
- **F-7 Format.** Uang `Rp` tanpa desimal. Tanggal locale `id_ID` (panggil `initializeDateFormatting` di `main`). Waktu ditampilkan WIB.
- **F-8 Pembayaran.** Buka `redirect_url` Snap di WebView. Setelah WebView ditutup, **wajib** polling status pesanan dari backend. Jangan menyimpulkan lunas dari URL redirect.
- **F-9 Tracking.** Polling 5-10 detik, berhenti saat layar ditutup (`dispose`). Tidak ada push notification di MVP.
- **F-10 Mode mock.** `--dart-define=USE_MOCK=true` membuat interceptor mengembalikan JSON dari `assets/mock/`. Gunanya: UI bisa dikerjakan sebelum backend siap, dan jadi cadangan demo.
- **F-11 Jaringan dev.** Emulator Android → `http://10.0.2.2:8000/api`. HP fisik → IP LAN laptop atau tunnel https. Jika memakai http, aktifkan cleartext di `AndroidManifest` hanya untuk debug.
- **F-12 Guest.** Simpan `order_code` + `phone` secara lokal untuk riwayat guest. Jangan log token atau password.
- **F-13 Bahasa.** Teks UI Bahasa Indonesia. Identifier kode Bahasa Inggris, mengikuti nama di kontrak API.

## 7. Admin Blade (A)

- **A-1** Guard `web` (session) + middleware role admin. CSRF aktif.
- **A-2** Resource controller + satu layout. Tidak perlu SPA. Tabel dengan pagination dan filter status dasar.
- **A-3** Master yang sudah dipakai tidak dihapus; sediakan tombol "Nonaktifkan".
- **A-4** Harga diubah lewat form admin, bukan lewat kode atau seeder.

## 8. Git & sesi

- **Git-1** Satu tugas = satu branch `feat/<area>-<ringkas>`. Commit kecil, contoh: `feat(order): hitung harga di server`.
- **Git-2** Jangan commit `.env`, kunci, `vendor/`, `build/`.
- **Git-3** Awal sesi baca `docs/PROGRESS.md`. Akhir sesi berikan baris pembaruannya.
- **Git-4** Merge ke `main` hanya jika: sudah jalan di emulator/Postman, tes backend hijau, sudah direview AI lain (`PROMPTS.md` §E), dan disetujui manusia.

## 9. Format CHANGE REQUEST

```
CHANGE REQUEST
Area: <kontrak | skema DB | aturan | area file milik AI lain>
Yang diubah: <satu kalimat>
Alasan: <masalah nyata yang ditemui, bukan preferensi>
Dampak: <backend / flutter / dokumen / data yang sudah ada>
Usulan: <before → after, ringkas>
Alternatif tanpa perubahan: <ada / tidak, dan kenapa kurang baik>
```
Manusia memutuskan. Setelah disetujui, penulis area yang bersangkutan yang mengedit.
